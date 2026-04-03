<?php

namespace Platform\Notifications;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Platform\Notifications\Channels\NotificationChannel;
use Platform\Notifications\Jobs\SendNotificationJob;
use Platform\Notifications\Models\NotificationPreference;
use Platform\Notifications\Models\NotificationsNotice;
use Symfony\Component\Uid\UuidV7;

class NotificationDispatcher
{
    public function dispatch(string $notificationType, array $data, Collection|array $recipients): void
    {
        $typeConfig = NotificationTypeRegistry::get($notificationType);

        if (! $typeConfig) {
            Log::warning('[NotificationDispatcher] Type not registered, skipping', [
                'type' => $notificationType,
                'registered_types' => array_keys(NotificationTypeRegistry::all()),
            ]);
            return;
        }

        foreach ($recipients as $user) {
            if (! $user) {
                continue;
            }

            $channels = $this->resolveChannels($notificationType, $user, $typeConfig);

            Log::info('[NotificationDispatcher] Resolved channels', [
                'type' => $notificationType,
                'user_id' => $user->id,
                'channels' => array_map(fn ($c) => $c->key(), $channels),
            ]);

            $notice = null;

            foreach ($channels as $channel) {
                if ($channel->key() === 'database') {
                    $notice = $this->createNotice($notificationType, $data, $user);
                    $channel->send($notice, $user);
                } else {
                    // External channels are dispatched async via queue
                    dispatch(new SendNotificationJob(
                        $channel->key(),
                        $notificationType,
                        $data,
                        $user->id,
                    ));
                }
            }
        }
    }

    /**
     * @return NotificationChannel[]
     */
    protected function resolveChannels(string $type, $user, array $typeConfig): array
    {
        // Load user preferences for this notification type
        $preferences = NotificationPreference::forUserAndType($user->id, $type)->get();

        $resolved = [];

        if ($preferences->isEmpty()) {
            // No preferences set — use default_channels from type config
            foreach ($typeConfig['default_channels'] as $channelKey) {
                $channel = NotificationChannelRegistry::get($channelKey);
                if ($channel && $channel->isConfiguredFor($user)) {
                    $resolved[] = $channel;
                }
            }
        } else {
            // Use explicit user preferences
            foreach ($preferences as $pref) {
                if (! $pref->enabled) {
                    continue;
                }
                $channel = NotificationChannelRegistry::get($pref->channel);
                if ($channel && $channel->isConfiguredFor($user)) {
                    $resolved[] = $channel;
                }
            }
        }

        return $resolved;
    }

    protected function createNotice(string $notificationType, array $data, $user): NotificationsNotice
    {
        return NotificationsNotice::create([
            'uuid'           => UuidV7::generate(),
            'notice_type'    => $data['notice_type'] ?? 'toast',
            'title'          => $data['title'] ?? null,
            'message'        => $data['message'] ?? null,
            'description'    => $data['description'] ?? null,
            'properties'     => $data['properties'] ?? null,
            'metadata'       => array_merge($data['metadata'] ?? [], [
                'notification_type' => $notificationType,
            ]),
            'user_id'        => $user->id,
            'team_id'        => $data['team_id'] ?? $user->current_team_id ?? null,
            'noticable_type' => $data['noticable_type'] ?? null,
            'noticable_id'   => $data['noticable_id'] ?? null,
        ]);
    }
}
