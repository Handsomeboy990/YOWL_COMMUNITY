<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalPage extends Model
{
    /**
     * The pages the platform is required to publish.
     *
     * A fixed list: an administrator edits these, and cannot invent a new
     * legal page from the console, which would sit outside every route.
     */
    public const SLUGS = [
        'charte' => 'Charte de la communauté',
        'confidentialite' => 'Politique de confidentialité',
        'conditions' => "Conditions d'utilisation",
        'mentions-legales' => 'Mentions légales',
    ];

    protected $fillable = ['slug', 'title', 'body', 'draft_body', 'updated_by', 'published_at'];

    protected $casts = ['published_at' => 'datetime'];

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function hasUnpublishedDraft(): bool
    {
        return $this->draft_body !== null && $this->draft_body !== $this->body;
    }
}
