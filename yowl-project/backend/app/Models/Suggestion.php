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

    protected $fillable = [
        'user_id',
        'name',
        'email',
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
