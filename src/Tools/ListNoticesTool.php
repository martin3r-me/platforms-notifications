<?php

namespace Platform\Notifications\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Notifications\Models\NotificationsNotice;

class ListNoticesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'notifications.notices.GET';
    }

    public function getDescription(): string
    {
        return 'GET /notifications/notices - Listet Benachrichtigungen des aktuellen Users. Parameter: unread_only (optional, boolean), team_id (optional), filters/search/sort/limit/offset (optional).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'unread_only' => ['type' => 'boolean', 'description' => 'Nur ungelesene Benachrichtigungen'],
                    'team_id' => ['type' => 'integer'],
                ],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $query = NotificationsNotice::query()
                ->where('user_id', $context->user->id);

            if (isset($arguments['team_id'])) {
                $query->where('team_id', (int) $arguments['team_id']);
            } elseif ($context->team) {
                $query->where('team_id', $context->team->id);
            }

            if (! empty($arguments['unread_only'])) {
                $query->unread();
            }

            $this->applyStandardFilters($query, $arguments, [
                'notice_type', 'title', 'created_at', 'read_at', 'dismissed',
            ]);
            $this->applyStandardSearch($query, $arguments, ['title', 'message', 'description']);
            $this->applyStandardSort($query, $arguments, [
                'created_at', 'read_at',
            ], 'created_at', 'desc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $notices = collect($result['data'])->map(fn (NotificationsNotice $n) => [
                'id'              => $n->id,
                'uuid'            => $n->uuid,
                'notice_type'     => $n->notice_type,
                'title'           => $n->title,
                'message'         => $n->message,
                'description'     => $n->description,
                'metadata'        => $n->metadata,
                'noticable_type'  => $n->noticable_type,
                'noticable_id'    => $n->noticable_id,
                'user_id'         => $n->user_id,
                'team_id'         => $n->team_id,
                'read_at'         => $n->read_at?->toISOString(),
                'dismissed'       => (bool) $n->dismissed,
                'created_at'      => $n->created_at?->toISOString(),
            ])->toArray();

            return ToolResult::success([
                'data'       => $notices,
                'pagination' => $result['pagination'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Benachrichtigungen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only'     => true,
            'category'      => 'read',
            'tags'          => ['notifications', 'notices', 'list'],
            'risk_level'    => 'safe',
            'requires_auth' => true,
            'requires_team' => false,
            'idempotent'    => true,
        ];
    }
}
