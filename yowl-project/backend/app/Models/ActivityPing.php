<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityPing extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'pinged_at'];

    protected $casts = ['pinged_at' => 'datetime'];
}
