<?php

namespace Platform\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $table = 'notification_preferences';

    protected $fillable = [
        'user_id',
        'notification_type',
        'channel',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function scopeForUserAndType($query, int $userId, string $type)
    {
        return $query->where('user_id', $userId)->where('notification_type', $type);
    }
}
