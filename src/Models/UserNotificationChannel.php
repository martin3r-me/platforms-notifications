<?php

namespace Platform\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Core\Casts\EncryptedJson;

class UserNotificationChannel extends Model
{
    protected $table = 'notification_channels';

    protected $fillable = [
        'user_id',
        'channel',
        'label',
        'credentials',
        'is_active',
    ];

    protected $casts = [
        'credentials'    => EncryptedJson::class,
        'is_active'      => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    protected $hidden = ['credentials'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}
