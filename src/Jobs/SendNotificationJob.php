<?php

namespace Platform\Notifications\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Platform\Notifications\NotificationChannelRegistry;
use Platform\Notifications\Models\NotificationsNotice;
use Symfony\Component\Uid\UuidV7;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public string $channelKey,
        public string $notificationType,
        public array $data,
        public int $userId,
    ) {
        $this->queue = 'default';
    }

    public function handle(): void
    {
        $channel = NotificationChannelRegistry::get($this->channelKey);

        if (! $channel) {
            Log::warning("Notification channel '{$this->channelKey}' not found");

            return;
        }

        $userModel = config('auth.providers.users.model');
        $user = $userModel::find($this->userId);

        if (! $user) {
            return;
        }

        if (! $channel->isConfiguredFor($user)) {
            return;
        }

        // Create a transient notice for the channel to format from.
        // External channels don't necessarily need a DB record, but we build
        // a model instance so the channel interface stays consistent.
        $notice = new NotificationsNotice([
            'uuid'           => UuidV7::generate(),
            'notice_type'    => $this->data['notice_type'] ?? 'toast',
            'title'          => $this->data['title'] ?? null,
            'message'        => $this->data['message'] ?? null,
            'description'    => $this->data['description'] ?? null,
            'properties'     => $this->data['properties'] ?? null,
            'metadata'       => array_merge($this->data['metadata'] ?? [], [
                'notification_type' => $this->notificationType,
            ]),
            'user_id'        => $this->userId,
            'noticable_type' => $this->data['noticable_type'] ?? null,
            'noticable_id'   => $this->data['noticable_id'] ?? null,
        ]);

        $channel->send($notice, $user);
    }
}
