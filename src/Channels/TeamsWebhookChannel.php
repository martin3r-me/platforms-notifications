<?php

namespace Platform\Notifications\Channels;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Platform\Notifications\Models\NotificationsNotice;
use Platform\Notifications\Models\UserNotificationChannel;

class TeamsWebhookChannel implements NotificationChannel
{
    public function key(): string
    {
        return 'teams_webhook';
    }

    public function label(): string
    {
        return 'MS Teams';
    }

    public function send(NotificationsNotice $notice, $recipient): void
    {
        $config = UserNotificationChannel::where('user_id', $recipient->id)
            ->where('channel', 'teams_webhook')
            ->where('is_active', true)
            ->first();

        if (! $config) {
            return;
        }

        $webhookUrl = $config->credentials['webhook_url'] ?? null;

        if (! $webhookUrl) {
            return;
        }

        $card = [
            'type'        => 'message',
            'attachments' => [
                [
                    'contentType' => 'application/vnd.microsoft.card.adaptive',
                    'content'     => [
                        'type'    => 'AdaptiveCard',
                        'version' => '1.4',
                        '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                        'body'    => [
                            [
                                'type'   => 'TextBlock',
                                'text'   => $notice->title,
                                'weight' => 'Bolder',
                                'size'   => 'Medium',
                            ],
                            [
                                'type' => 'TextBlock',
                                'text' => $notice->message ?? $notice->description ?? '',
                                'wrap' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        if ($url = ($notice->metadata['url'] ?? null)) {
            $card['attachments'][0]['content']['actions'] = [
                [
                    'type'  => 'Action.OpenUrl',
                    'title' => 'Öffnen',
                    'url'   => $url,
                ],
            ];
        }

        $response = Http::post($webhookUrl, $card);

        if ($response->failed()) {
            $config->update([
                'last_error' => $response->body(),
            ]);

            Log::warning('Teams webhook delivery failed', [
                'user_id'  => $recipient->id,
                'response' => $response->body(),
            ]);
        }
    }

    public function isConfiguredFor($user): bool
    {
        return UserNotificationChannel::where('user_id', $user->id)
            ->where('channel', 'teams_webhook')
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Send a test message to verify the webhook configuration.
     */
    public static function test(UserNotificationChannel $config): bool
    {
        $webhookUrl = $config->credentials['webhook_url'] ?? null;

        if (! $webhookUrl) {
            $config->update(['last_error' => 'Webhook URL not configured']);

            return false;
        }

        $response = Http::post($webhookUrl, [
            'type'        => 'message',
            'attachments' => [
                [
                    'contentType' => 'application/vnd.microsoft.card.adaptive',
                    'content'     => [
                        'type'    => 'AdaptiveCard',
                        'version' => '1.4',
                        '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                        'body'    => [
                            [
                                'type'   => 'TextBlock',
                                'text'   => 'Testbenachrichtigung',
                                'weight' => 'Bolder',
                            ],
                            [
                                'type' => 'TextBlock',
                                'text' => 'MS Teams Webhook ist erfolgreich verbunden!',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $config->update([
            'last_tested_at' => now(),
            'last_error'     => $response->failed() ? $response->body() : null,
        ]);

        return $response->successful();
    }
}
