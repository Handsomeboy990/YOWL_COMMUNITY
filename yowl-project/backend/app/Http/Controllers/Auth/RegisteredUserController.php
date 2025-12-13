<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        try {
            $request->validate([
                'username' => ['required', 'string', 'max:255', 'min:3'],
                'fullname' => ['required', 'string', 'max:255', 'min:5'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'birthdate' => ['required', 'date', 'before:'.\Carbon\Carbon::now()->subYears(13)->format('Y-m-d'), 'after:'.\Carbon\Carbon::now()->subYears(35)->format('Y-m-d')],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->errors(),
            ], 401);
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

        // Envoi de l'email (à remplacer par mailable si besoin)
        Mail::raw("Votre code de vérification : $code", function($message) use ($user) {
            $message->to($user->email)->subject('Code de vérification YOWL');
        });

        event(new Registered($user));

        return response()->json([
            'success' => true,
            'message' => 'Code de vérification envoyé à votre email.'
        ]);
    }
}
