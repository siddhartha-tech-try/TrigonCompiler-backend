<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdeSession extends Model
{
    protected $table = 'ide_sessions';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'is_anonymous',
        'status',
        'workspace_path',
        'started_at',
        'last_activity_at',
        'ended_at',
    ];

    protected $casts = [
        'is_anonymous'     => 'boolean',
        'started_at'       => 'datetime',
        'last_activity_at' => 'datetime',
        'ended_at'         => 'datetime',
    ];

    /**
     * Events associated with this IDE session
     */
    public function events(): HasMany
    {
        return $this->hasMany(SessionEvent::class, 'session_id');
    }
}