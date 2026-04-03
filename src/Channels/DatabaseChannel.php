<?php

namespace Platform\Notifications\Channels;

use Platform\Notifications\Models\NotificationsNotice;

class DatabaseChannel implements NotificationChannel
{
    public function key(): string
    {
        return 'database';
    }

    public function label(): string
    {
        return 'In-App';
    }

    public function send(NotificationsNotice $notice, $recipient): void
    {
        // Notice is already persisted by the dispatcher before calling channels.
        // Nothing extra to do — the in-app UI reads from notifications_notices.
    }

    public function isConfiguredFor($user): bool
    {
        return true; // Always available for every user.
    }
}
