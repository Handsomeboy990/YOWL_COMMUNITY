<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Poll extends Model
{
    protected $fillable = ['review_id', 'question', 'closes_at'];

    protected $casts = ['closes_at' => 'datetime'];

    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    public function options()
    {
        return $this->hasMany(PollOption::class)->orderBy('position');
    }

    public function votes()
    {
        return $this->hasMany(PollVote::class);
    }

    public function isClosed(): bool
    {
        return $this->closes_at !== null && $this->closes_at->isPast();
    }

    public function totalVotes(): int
    {
        return (int) $this->options()->sum('votes');
    }
}
