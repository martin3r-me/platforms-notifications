<?php

namespace Platform\Notifications\Channels;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Platform\Notifications\Models\NotificationsNotice;
use Platform\Notifications\Models\UserNotificationChannel;

class PushoverChannel implements NotificationChannel
{
    public function key(): string
    {
        return 'pushover';
    }

    public function label(): string
    {
        return 'Pushover';
    }

    public function send(NotificationsNotice $notice, $recipient): void
    {
        $config = UserNotificationChannel::where('user_id', $recipient->id)
            ->where('channel', 'pushover')
            ->where('is_active', true)
            ->first();

        if (! $config) {
            return;
        }

        $appToken = config('notifications.pushover.app_token');

        if (! $appToken) {
            Log::warning('Pushover app_token not configured');

            return;
        }

        $response = Http::post('https://api.pushover.net/1/messages.json', [
            'token'    => $appToken,
            'user'     => $config->credentials['user_key'],
            'title'    => $notice->title,
            'message'  => $notice->message ?? $notice->description ?? $notice->title,
            'url'      => $notice->metadata['url'] ?? null,
            'device'   => $config->credentials['device'] ?? null,
            'priority' => $config->credentials['priority'] ?? 0,
        ]);

        if ($response->failed()) {
            $config->update([
                'last_error' => $response->body(),
            ]);

            Log::warning('Pushover delivery failed', [
                'user_id'  => $recipient->id,
                'response' => $response->body(),
            ]);
        }
    }

    public function isConfiguredFor($user): bool
    {
        return UserNotificationChannel::where('user_id', $user->id)
            ->where('channel', 'pushover')
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Send a test notification to verify the user's Pushover configuration.
     */
    public static function test(UserNotificationChannel $config): bool
    {
        $appToken = config('notifications.pushover.app_token');

        if (! $appToken) {
            $config->update(['last_error' => 'App token not configured']);

            return false;
        }

        $response = Http::post('https://api.pushover.net/1/messages.json', [
            'token'   => $appToken,
            'user'    => $config->credentials['user_key'],
            'title'   => 'Testbenachrichtigung',
            'message' => 'Pushover ist erfolgreich verbunden!',
        ]);

        $config->update([
            'last_tested_at' => now(),
            'last_error'     => $response->failed() ? $response->body() : null,
        ]);

        return $response->successful();
    }
}
