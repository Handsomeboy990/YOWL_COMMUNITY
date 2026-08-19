<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'review_id',
        'parent_id',
        'content',
        'nb_like',
        'nb_dislike',
        'nb_views',
    ];

    // Relation avec l'utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation avec le review
    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    // Commentaire parent
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Direct replies.
     *
     * This relation used to eager load itself, which walked the whole reply
     * chain with one query per level and no bound on depth. The client builds
     * its threads from parent_id on the flat listing and never reads the
     * nested payload, so the recursion cost nothing but queries.
     */
    public function children()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    // Réactions sur le commentaire
    public function reactions()
    {
        return $this->hasMany(CommentReaction::class);
    }
}
