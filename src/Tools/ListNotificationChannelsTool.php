<?php

namespace Platform\Notifications\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Notifications\Models\UserNotificationChannel;
use Platform\Notifications\NotificationChannelRegistry;

class ListNotificationChannelsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'notifications.channels.GET';
    }

    public function getDescription(): string
    {
        return 'GET /notifications/channels - Listet verfügbare Benachrichtigungs-Kanäle und deren Konfigurationsstatus für den aktuellen User.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $registeredChannels = NotificationChannelRegistry::all();

            $userChannels = UserNotificationChannel::where('user_id', $context->user->id)
                ->get()
                ->keyBy('channel');

            $data = [];
            foreach ($registeredChannels as $key => $channel) {
                $userConfig = $userChannels->get($key);

                $data[] = [
                    'key'            => $key,
                    'label'          => $channel->label(),
                    'configured'     => $channel->isConfiguredFor($context->user),
                    'is_active'      => $userConfig?->is_active ?? false,
                    'last_tested_at' => $userConfig?->last_tested_at?->toISOString(),
                    'last_error'     => $userConfig?->last_error,
                ];
            }

            return ToolResult::success([
                'data'  => $data,
                'count' => count($data),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only'     => true,
            'category'      => 'read',
            'tags'          => ['notifications', 'channels', 'config'],
            'risk_level'    => 'safe',
            'requires_auth' => true,
            'requires_team' => false,
            'idempotent'    => true,
        ];
    }
}
