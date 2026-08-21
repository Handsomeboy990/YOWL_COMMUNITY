<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'fullname',
        'email',
        'password',
        'picture',
        'birthdate',
        'email_verification_code',
        'email_verification_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_code',
        'email_verification_expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthdate' => 'date',
            'email_verification_expires_at' => 'datetime',
            'is_active' => 'boolean',
            'digest_optin' => 'boolean',
            'email_optout' => 'boolean',
            'digest_sent_at' => 'datetime',
            'anonymized_at' => 'datetime',
        ];
    }

    /**
     * Get the reviews for the user.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the review reactions (likes/dislikes) for the user.
     */
    public function reviewReactions()
    {
        return $this->hasMany(ReviewReaction::class);
    }

    /**
     * Get the comments for the user.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Members this user follows.
     */
    public function followedUsers()
    {
        return $this->morphedByMany(User::class, 'followable', 'follows')->withTimestamps();
    }

    /**
     * Tags this user follows.
     */
    public function followedTags()
    {
        return $this->morphedByMany(Tag::class, 'followable', 'follows')->withTimestamps();
    }

    /**
     * Members following this user.
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'followable_id', 'user_id')
            ->where('follows.followable_type', self::class)
            ->withTimestamps();
    }

    /**
     * Browser push subscriptions registered by this user.
     */
    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * An account whose personal data has been erased on request.
     */
    public function isAnonymized(): bool
    {
        return $this->anonymized_at !== null;
    }

    /**
     * Get all activities (reviews and comments) for the user.
     */
    public function activity()
    {
        $reviews = $this->reviews()->get();
        $comments = $this->comments()->get();
        $reactions = $this->reviewReactions()->get();
        return $reviews
            ->concat($comments)
            ->concat($reactions)
            ->sortByDesc(function ($item) {
                return $item->created_at;
            })
            ->values();
    }

    /**
     * Où en est ce compte vis-à-vis de la vérification d'adresse.
     *
     * Calculé ici, et non dans chaque contrôleur : la connexion et la lecture
     * du compte courant doivent renvoyer le même décompte, sinon le rappel
     * affiché change de chiffre au rechargement de la page.
     *
     * @return array{verifie: bool, restant: int, seuil: int}
     */
    public function etatDeVerification(): array
    {
        $seuil = (int) (\App\Support\Settings::get('registration.verification_grace') ?? 0);
        $verifie = $this->email_verified_at !== null;

        return [
            'verifie' => $verifie,
            'restant' => $verifie ? 0 : max(0, $seuil - (int) $this->login_count),
            'seuil' => $seuil,
        ];
    }
}
