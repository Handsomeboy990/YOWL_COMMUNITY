<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appeal extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_UPHELD = 'upheld';      // La modération avait raison

    public const STATUS_GRANTED = 'granted';    // Le recours est accepté

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_UPHELD, self::STATUS_GRANTED];

    protected $fillable = ['user_id', 'message', 'status', 'response', 'handled_by', 'handled_at'];

    protected $casts = ['handled_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function appealable()
    {
        return $this->morphTo();
    }
}
