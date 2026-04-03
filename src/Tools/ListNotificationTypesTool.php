<?php

namespace Platform\Notifications\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Notifications\NotificationTypeRegistry;

class ListNotificationTypesTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'notifications.types.GET';
    }

    public function getDescription(): string
    {
        return 'GET /notifications/types - Listet alle registrierten Benachrichtigungstypen, gruppiert nach Modul. Zeigt Label, Beschreibung und Default-Kanäle.';
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
            if (! empty($arguments['group'])) {
                $types = NotificationTypeRegistry::forGroup($arguments['group']);
            } else {
                $types = NotificationTypeRegistry::all();
            }

            $data = [];
            foreach ($types as $key => $config) {
                $data[] = [
                    'key'              => $key,
                    'label'            => $config['label'],
                    'description'      => $config['description'],
                    'group'            => $config['group'],
                    'default_channels' => $config['default_channels'],
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
            'tags'          => ['notifications', 'types', 'registry'],
            'risk_level'    => 'safe',
            'requires_auth' => true,
            'requires_team' => false,
            'idempotent'    => true,
        ];
    }
}
