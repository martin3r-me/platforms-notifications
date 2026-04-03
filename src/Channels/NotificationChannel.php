<?php

namespace Platform\Notifications\Channels;

use Platform\Notifications\Models\NotificationsNotice;

interface NotificationChannel
{
    public function key(): string;

    public function label(): string;

    public function send(NotificationsNotice $notice, $recipient): void;

    public function isConfiguredFor($user): bool;
}
