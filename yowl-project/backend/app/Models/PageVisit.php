<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Une consultation de page, anonyme par construction.
 *
 * Voir la migration pour ce que la table refuse délibérément de stocker.
 */
class PageVisit extends Model
{
    /** Aucune ligne n'est jamais modifiée après coup. */
    public $timestamps = false;

    protected $fillable = [
        'path',
        'referrer_host',
        'device',
        'is_member',
        'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'is_member' => 'boolean',
            'visited_at' => 'datetime',
        ];
    }
}
