<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_DISMISSED = 'dismissed';

    public const STATUS_ACTIONED = 'actioned';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_DISMISSED,
        self::STATUS_ACTIONED,
    ];

    public const REASONS = [
        'spam',
        'harassment',
        'hate',
        'sexual',
        'violence',
        'misinformation',
        'other',
    ];

    protected $fillable = [
        'user_id',
        'reason',
        'details',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
    ];

    /**
     * The user who filed the report.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The administrator who processed the report.
     */
    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * The reported content: a review or a comment.
     */
    public function reportable()
    {
        return $this->morphTo();
    }
}
