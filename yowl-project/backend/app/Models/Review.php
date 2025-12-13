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
    ];

    protected $casts = [
        'medias' => 'array',
    ];

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
}
