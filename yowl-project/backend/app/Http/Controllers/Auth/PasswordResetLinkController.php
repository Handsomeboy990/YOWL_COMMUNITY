<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetLinkController extends Controller
{
    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        // Un relais injoignable levait une exception jusqu'au 500, où le
        // visiteur lisait « Server Error » sans rien pouvoir en faire.
        $status = null;
        $remis = \App\Support\MailDelivery::attempt(
            function () use ($request, &$status) {
                $status = Password::sendResetLink($request->only('email'));
            },
            ['action' => 'lien de reinitialisation'],
        );

        if (! $remis) {
            return \App\Support\MailDelivery::unavailable('ton lien de réinitialisation');
        }

        if ($status != Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['status' => __($status)]);
    }
}
