<?php

namespace Platform\Notifications\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Notifications\Models\NotificationPreference;
use Platform\Notifications\NotificationChannelRegistry;
use Platform\Notifications\NotificationTypeRegistry;

class ListNotificationPreferencesTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'notifications.preferences.GET';
    }

    public function getDescription(): string
    {
        return 'GET /notifications/preferences - Zeigt die Benachrichtigungs-Einstellungen des aktuellen Users: Welche Typen über welche Kanäle aktiviert sind.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'group' => ['type' => 'string', 'description' => 'Filter nach Gruppe (z.B. "planner", "helpdesk")'],
            ],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $prefs = NotificationPreference::where('user_id', $context->user->id)->get();
            $channels = NotificationChannelRegistry::all();

            if (! empty($arguments['group'])) {
                $types = NotificationTypeRegistry::forGroup($arguments['group']);
            } else {
                $types = NotificationTypeRegistry::all();
            }

            $data = [];
            foreach ($types as $typeKey => $typeConfig) {
                $channelPrefs = [];
                foreach ($channels as $channelKey => $channel) {
                    $pref = $prefs->first(fn ($p) => $p->notification_type === $typeKey && $p->channel === $channelKey);

                    $channelPrefs[$channelKey] = [
                        'enabled'    => $pref ? $pref->enabled : in_array($channelKey, $typeConfig['default_channels']),
                        'is_default' => $pref === null,
                    ];
                }

                $data[] = [
                    'type'        => $typeKey,
                    'label'       => $typeConfig['label'],
                    'group'       => $typeConfig['group'],
                    'channels'    => $channelPrefs,
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
            'tags'          => ['notifications', 'preferences', 'settings'],
            'risk_level'    => 'safe',
            'requires_auth' => true,
            'requires_team' => false,
            'idempotent'    => true,
        ];
    }
}
