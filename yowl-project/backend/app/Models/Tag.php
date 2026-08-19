<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function reviews()
    {
        return $this->belongsToMany(Review::class, 'review_tag');
    }

    /**
     * Members following this tag.
     */
    public function followers()
    {
        return $this->morphToMany(User::class, 'followable', 'follows')->withTimestamps();
    }
}
