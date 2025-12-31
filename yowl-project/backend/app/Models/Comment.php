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

    // Sous-commentaires
    public function children()
    {
        return $this->hasMany(Comment::class, 'parent_id')->with('children');
    }

    // Réactions sur le commentaire
    public function reactions()
    {
        return $this->hasMany(CommentReaction::class);
    }
}
