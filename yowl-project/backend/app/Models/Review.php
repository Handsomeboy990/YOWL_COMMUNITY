<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
        'nb_like',
        'nb_dislike',
        'nb_views',
        'link',
        'medias',
        'scheduled_for',
    ];

    protected $casts = [
        'medias' => 'array',
        'is_published' => 'boolean',
        'link_preview' => 'array',
        'link_preview_at' => 'datetime',
        'scheduled_for' => 'datetime',
    ];

    /**
     * The fingerprint follows the link, always, whoever writes the row.
     *
     * Computing it in the controller would leave the seeder, the console and
     * any future writer producing rows that duplicate detection cannot see.
     */
    protected static function booted(): void
    {
        static::saving(function (self $review) {
            if ($review->isDirty('link')) {
                $review->link_fingerprint = \App\Support\LinkNormaliser::fingerprint($review->link);
            }
        });
    }

    /**
     * Waiting for its hour, as opposed to hidden by a moderator.
     *
     * Both states leave is_published false, and telling the author which one
     * applies is the whole difference between "your text is queued" and
     * "your text was taken down".
     */
    public function isScheduled(): bool
    {
        return ! $this->is_published && $this->scheduled_for && $this->scheduled_for->isFuture();
    }

    // relation avec les autres Model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'review_tag');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function reactions()
    {
        return $this->hasMany(ReviewReaction::class);
    }

    public function poll()
    {
        return $this->hasOne(Poll::class);
    }
}
