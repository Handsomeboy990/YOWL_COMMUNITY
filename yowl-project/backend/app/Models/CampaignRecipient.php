<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignRecipient extends Model
{
    public $timestamps = false;

    protected $fillable = ['campaign_id', 'user_id', 'sent_at', 'error'];

    protected $casts = ['sent_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
