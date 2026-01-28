<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionEvent extends Model
{
    protected $fillable = [
        'session_id',
        'event_type',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(IdeSession::class, 'session_id');
    }
}