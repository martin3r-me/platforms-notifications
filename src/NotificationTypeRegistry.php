<?php

namespace Platform\Notifications;

class NotificationTypeRegistry
{
    protected static array $types = [];

    public static function register(string $key, array $config): void
    {
        static::$types[$key] = array_merge([
            'label'            => $key,
            'description'      => '',
            'group'            => 'general',
            'default_channels' => ['database'],
        ], $config);
    }

    public static function all(): array
    {
        return static::$types;
    }

    public static function get(string $key): ?array
    {
        return static::$types[$key] ?? null;
    }

    public static function forGroup(string $group): array
    {
        return array_filter(static::$types, fn (array $config) => $config['group'] === $group);
    }

    /**
     * Returns types grouped by their group key.
     */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (static::$types as $key => $config) {
            $grouped[$config['group']][$key] = $config;
        }

        return $grouped;
    }

    /**
     * Reset registry (for testing).
     */
    public static function flush(): void
    {
        static::$types = [];
    }
}
