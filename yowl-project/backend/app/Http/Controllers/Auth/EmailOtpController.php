<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationCode;
use App\Models\User;
use App\Support\MailDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EmailOtpController extends Controller
{
    public function resend(Request $request)
    {
        $data = $request->only('email');
        $validator = Validator::make($data, ['email' => ['required', 'email']]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Utilisateur introuvable'], 404);
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json(['success' => true, 'message' => 'Email déjà vérifié']);
        }
        $code = (string) random_int(100000, 999999);
        $user->email_verification_code = $code;
        $user->email_verification_expires_at = now()->addMinutes(15);
        $user->save();

        // L'envoi passe par le filet, comme à l'inscription.
        //
        // Sans lui, un relais qui refuse le message faisait sortir la requête
        // en 500, et la réponse annonçait « Code envoyé » dans tous les autres
        // cas, y compris quand rien n'était parti. Le code enregistré juste
        // au-dessus donnait en plus l'impression que tout s'était bien passé.
        $remis = MailDelivery::attempt(
            fn () => Mail::to($user->email)->send(new EmailVerificationCode($code)),
            ['action' => 'renvoi du code de verification', 'user' => $user->id],
        );

        if (! $remis) {
            return response()->json([
                'success' => true,
                'delivered' => false,
                'message' => "Le code n'a pas pu être envoyé. Réessayez dans quelques minutes, "
                    ."ou vérifiez vos indésirables si un code précédent vous est parvenu.",
            ], 202);
        }

        return response()->json([
            'success' => true,
            'delivered' => true,
            'message' => 'Code envoyé.',
        ]);
    }
    public function verify(Request $request)
    {
        $data = $request->only('email', 'code');
        $validator = Validator::make($data, ['email' => ['required', 'email'], 'code' => ['required', 'digits:6']]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Utilisateur introuvable'], 404);
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json(['success' => true, 'message' => 'Déjà vérifié']);
        }
        if (!$user->email_verification_code || !$user->email_verification_expires_at) {
            return response()->json(['success' => false, 'message' => 'Aucun code actif']);
        }
        if ($user->email_verification_expires_at->isPast()) {
            return response()->json(['success' => false, 'message' => 'Code expiré']);
        }
        if ($user->email_verification_code !== $data['code']) {
            return response()->json(['success' => false, 'message' => 'Code invalide']);
        }
        $user->email_verified_at = now();
        $user->email_verification_code = null;
        $user->email_verification_expires_at = null;
        $user->save();
        return response()->json(['success' => true, 'message' => 'Email vérifié']);
    }
}
