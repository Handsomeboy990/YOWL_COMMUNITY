<?php

namespace App\Http\Requests\Auth;

use App\Support\Settings;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use RuntimeException;

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
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
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
            $this->refuser('compte_desactive', __('auth.banned'));
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
        //
        // validate() lève une RuntimeException, et non un simple faux, quand le
        // hachage stocké n'est pas dans un format que PHP reconnaît. Un compte
        // créé hors de Laravel en est la cause habituelle : pgcrypto préfixe
        // ses hachages bcrypt par $2a$, que password_get_info rapporte comme
        // « unknown », alors que Laravel n'accepte que $2y$. C'est le même
        // algorithme, seul le marqueur diffère.
        //
        // Sans ce filet, toute connexion sur un tel compte répondait 500, y
        // compris avec un mauvais mot de passe, puisque l'exception précède la
        // comparaison. Le refus se comporte désormais comme n'importe quel
        // autre échec d'identifiants, et la vraie cause part au journal : elle
        // n'est devinable depuis aucune réponse HTTP.
        try {
            $identifiantsValides = Auth::validate($credentials);
        } catch (RuntimeException $e) {
            Log::error('Hachage de mot de passe illisible, connexion refusée.', [
                'user_id' => $user?->id,
                'prefixe' => $user ? substr($user->password, 0, 4) : null,
                'raison' => $e->getMessage(),
            ]);
            $identifiantsValides = false;
        }

        if (! $identifiantsValides) {
            RateLimiter::hit($this->throttleKey());
            $this->refuser('identifiants_invalides', __('auth.failed'));
        }
        RateLimiter::clear($this->throttleKey());

        // validate() ne pose pas d'utilisateur courant : le contrôleur en a
        // besoin pour émettre le jeton, on le lui donne sans session.
        Auth::setUser($user);

        // Vérifier si le user a vérifié son compte
        // Vérification d'adresse : rappelée, puis exigée.
        //
        // L'exiger dès la première connexion transforme une panne de relais en
        // porte close pour tout le monde, y compris pour ceux dont le code
        // n'est jamais parti. Le compte entre donc, avec un rappel visible,
        // pendant les premières connexions ; passé ce nombre, il faut vérifier.
        //
        // Le seuil vit dans les réglages : le jour où le mail ne part plus,
        // l'ouvrir depuis la console est plus rapide qu'un déploiement.
        if ($user->email_verified_at === null) {
            $tolerance = (int) (Settings::get('registration.verification_grace') ?? 0);

            if ($user->login_count >= $tolerance) {
                $this->refuser('email_non_verifie', __('auth.unverified'));
            }
        }
    }

    /**
     * Refuse la connexion en nommant la cause.
     *
     * Le code compte autant que la phrase. Le navigateur doit distinguer un
     * compte non vérifié, qui ouvre la fenêtre de saisie du code, d'un simple
     * mauvais mot de passe : il le faisait jusqu'ici en comparant le texte
     * anglais du message, si bien que traduire ce message suffisait à casser
     * la vérification, sans erreur nulle part.
     *
     * La forme de la réponse reste celle d'une erreur de validation, errors
     * compris, pour que rien de ce qui lit déjà ce format n'ait à changer.
     */
    private function refuser(string $code, string $message): never
    {
        throw new HttpResponseException(response()->json([
            'message' => $message,
            'errors' => ['email' => [$message]],
            'code' => $code,
        ], 422));
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        $this->refuser('trop_de_tentatives', trans('auth.throttle', [
            'seconds' => $seconds,
            'minutes' => ceil($seconds / 60),
        ]));
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('email')) . '|' . $this->ip());
    }
}
