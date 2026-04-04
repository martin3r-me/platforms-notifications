<?php

namespace Platform\Notifications;

use Illuminate\Support\Collection;
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
            return;
        }

        foreach ($recipients as $user) {
            if (! $user) {
                continue;
            }

            $channels = $this->resolveChannels($notificationType, $user, $typeConfig);

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
        $prefsByChannel = $preferences->keyBy('channel');

        // Check all default channels + any explicitly set channels
        $allChannelKeys = collect($typeConfig['default_channels'])
            ->merge($prefsByChannel->keys())
            ->unique();

        foreach ($allChannelKeys as $channelKey) {
            $pref = $prefsByChannel->get($channelKey);

            // Explicit preference exists → respect it
            if ($pref !== null) {
                if (! $pref->enabled) {
                    continue;
                }
            }
            // No explicit preference → channel is in defaults, keep it

            $channel = NotificationChannelRegistry::get($channelKey);
            if ($channel && $channel->isConfiguredFor($user)) {
                $resolved[] = $channel;
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
