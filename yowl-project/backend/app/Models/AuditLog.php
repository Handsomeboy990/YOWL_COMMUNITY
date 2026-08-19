<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'context',
        'ip_address',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    /**
     * Record an administrative action.
     *
     * Writing the log never blocks the action it describes: a journal that
     * fails must not turn a successful moderation into an error.
     */
    public static function record(
        string $action,
        ?Model $subject = null,
        array $context = [],
        ?Request $request = null
    ): void {
        try {
            $request ??= request();

            self::create([
                'user_id' => $request?->user()?->id,
                'action' => $action,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'context' => $context ?: null,
                'ip_address' => $request?->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
