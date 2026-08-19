<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Suggestion extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';

    public const STATUS_READ = 'read';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_READ,
        self::STATUS_ARCHIVED,
    ];

    /**
     * What a suggestion can be about.
     *
     * A fixed list rather than free text: it lets the moderation queue be
     * filtered, and it tells the sender what kind of message is expected here.
     */
    public const SUBJECTS = [
        'feature',
        'improvement',
        'bug',
        'content',
        'other',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'subject',
        'message',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
    ];

    /**
     * The author of the suggestion, when it was sent by an authenticated user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The administrator who processed the suggestion.
     */
    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
