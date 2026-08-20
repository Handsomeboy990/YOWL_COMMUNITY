<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        if (! Settings::get('registration.open')) {
            return response()->json([
                'success' => false,
                'message' => 'Les inscriptions sont momentanément fermées.',
            ], 403);
        }

        try {
            $request->validate([
                'username' => ['required', 'string', 'max:255', 'min:3', 'unique:users,username'],
                'fullname' => ['required', 'string', 'max:255', 'min:5'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'birthdate' => $this->birthdateRules(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'error' => $e->errors(),
            ], 422);
        }

        $user = User::create([
            'username' => $request->username,
            'fullname' => $request->fullname,
            'email' => $request->email,
            'password' => Hash::make($request->string('password')),
            'birthdate' => $request->birthdate,
        ]);

        // assigner le role
        $user->assignRole('client');

        // Génération OTP (6 chiffres)
        $code = (string) random_int(100000, 999999);
        $user->email_verification_code = $code;
        $user->email_verification_expires_at = now()->addMinutes(15);
        $user->save();

        // Envoi de l'email de vérification. Le compte est déjà enregistré :
        // un relais SMTP injoignable ne doit pas faire perdre l'inscription,
        // seulement le code, qui se redemande.
        $remis = \App\Support\MailDelivery::attempt(
            fn () => Mail::to($user->email)->send(new \App\Mail\EmailVerificationCode($code)),
            ['action' => 'verification a l inscription', 'user' => $user->id],
        );

        event(new Registered($user));

        if (! $remis) {
            return response()->json([
                'success' => true,
                'delivered' => false,
                'message' => "Ton compte est créé, mais le code de vérification n'a pas pu partir. "
                    .'Demande un nouveau code dans quelques minutes.',
            ], 202);
        }

        return response()->json([
            'success' => true,
            'delivered' => true,
            'message' => 'Code de vérification envoyé à votre email.'
        ]);
    }

    /**
     * Birthdate rules, taken from the settings rather than from constants.
     *
     * The bounds used to be written into this method, so moving them meant a
     * deployment. Either bound can now be cleared from the administration, in
     * which case it stops applying.
     *
     * @return array<int, string>
     */
    private function birthdateRules(): array
    {
        $rules = ['required', 'date'];

        $minimumAge = Settings::get('registration.age_min');
        if ($minimumAge !== null) {
            // Etre plus vieux que l'age minimum, c'est etre ne avant cette date.
            $rules[] = 'before_or_equal:'.now()->subYears($minimumAge)->format('Y-m-d');
        }

        $maximumAge = Settings::get('registration.age_max');
        if ($maximumAge !== null) {
            $rules[] = 'after:'.now()->subYears($maximumAge + 1)->format('Y-m-d');
        }

        return $rules;
    }
}
