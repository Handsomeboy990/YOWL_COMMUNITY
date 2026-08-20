<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';

    public const AUDIENCES = ['all', 'selected', 'segment'];

    /**
     * Segments the console can target, each a question about behaviour rather
     * than a stored label: a list somebody has to maintain by hand goes stale.
     */
    public const SEGMENTS = [
        'active_30' => 'Membres actifs dans les trente derniers jours',
        'never_published' => "Membres qui n'ont jamais publié",
        'authors' => 'Membres ayant publié au moins un avis',
        'digest' => 'Membres inscrits au résumé hebdomadaire',
        'newcomers' => 'Membres inscrits dans les trente derniers jours',
    ];

    protected $fillable = [
        'subject', 'body', 'purpose', 'audience', 'segment', 'user_ids',
        'status', 'recipients_count', 'sent_count', 'failed_count',
        'created_by', 'sent_at',
    ];

    protected $casts = [
        'user_ids' => 'array',
        'sent_at' => 'datetime',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients()
    {
        return $this->hasMany(CampaignRecipient::class);
    }
}
