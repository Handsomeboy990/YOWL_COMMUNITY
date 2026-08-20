<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'error' => $validator->errors(),
        ], 422));
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->only('email', 'password');
        $user = \App\Models\User::where('email', $credentials['email'])->first();

        // Un compte desactive par la moderation, ou par son propre titulaire,
        // ne se reconnecte pas. L'age n'entre plus dans cette decision : la
        // borne s'applique a l'inscription, elle n'expulse pas un membre
        // legitime le jour de son anniversaire.
        if ($user && $user->is_active == false) {
            throw ValidationException::withMessages([
                'email' => 'This account has been banned.'
            ]);
        }

        // Auth::validate() et non Auth::attempt().
        //
        // attempt() ouvre une session : c'est le garde « web », qui écrit dans
        // le magasin de sessions. Sur une API qui ne délivre que des jetons,
        // cette session ne sert à rien et n'est jamais relue, mais elle rend
        // la connexion dépendante d'un magasin que l'hébergement ne fournit
        // pas toujours. Une table sessions absente faisait répondre 500 à
        // toute connexion, avec pour seul indice le mot « Server Error ».
        //
        // validate() vérifie les mêmes identifiants par le même fournisseur,
        // sans rien écrire nulle part.
        if (! Auth::validate($credentials)) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }
        RateLimiter::clear($this->throttleKey());

        // validate() ne pose pas d'utilisateur courant : le contrôleur en a
        // besoin pour émettre le jeton, on le lui donne sans session.
        Auth::setUser($user);

        // Vérifier si le user a vérifié son compte
        if ($user->email_verified_at == null) {
            throw ValidationException::withMessages([
                'email' => 'This account has not been verified yet.'
            ]);
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('email')) . '|' . $this->ip());
    }
}
