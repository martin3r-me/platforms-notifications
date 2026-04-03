<?php

namespace Platform\Notifications;

use Platform\Notifications\Channels\NotificationChannel;

class NotificationChannelRegistry
{
    protected static array $channels = [];

    public static function register(NotificationChannel $channel): void
    {
        static::$channels[$channel->key()] = $channel;
    }

    /**
     * @return array<string, NotificationChannel>
     */
    public static function all(): array
    {
        return static::$channels;
    }

    public static function get(string $key): ?NotificationChannel
    {
        return static::$channels[$key] ?? null;
    }

    /**
     * Reset registry (for testing).
     */
    public static function flush(): void
    {
        static::$channels = [];
    }
}
