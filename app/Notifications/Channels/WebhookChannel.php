<?php

namespace App\Notifications\Channels;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * JSON POST to a configured endpoint (chat tools, iPaaS, custom receivers). Signed with an
 * optional shared secret. NOT CONFIGURED until MARKEDGE_WEBHOOK_URL is set.
 */
class WebhookChannel implements NotificationChannel
{
    public function name(): string
    {
        return 'webhook';
    }

    public function isConfigured(): bool
    {
        $url = config('markedge.notifications.webhook_url');

        return is_string($url) && str_starts_with($url, 'https://');
    }

    public function status(): string
    {
        return $this->isConfigured() ? 'Configured' : 'NOT CONFIGURED (MARKEDGE_WEBHOOK_URL, https only)';
    }

    public function send(NotificationMessage $message): DeliveryResult
    {
        if (! $this->isConfigured()) {
            return DeliveryResult::skipped('webhook channel not configured');
        }

        $payload = [
            'subject' => $message->subject,
            'body' => $message->body,
            'url' => $message->url,
            'source' => $message->source,
            'data' => $message->data,
            'sent_at' => now()->toIso8601String(),
        ];

        try {
            $request = Http::timeout((int) config('markedge.notifications.webhook_timeout', 5))->retry(2, 500, throw: false)->acceptJson();
            $secret = config('markedge.notifications.webhook_secret');

            if (is_string($secret) && $secret !== '') {
                $request = $request->withHeaders(['X-Markedge-Signature' => hash_hmac('sha256', json_encode($payload), $secret)]);
            }

            $response = $request->post(config('markedge.notifications.webhook_url'), $payload);

            return $response->successful() ? DeliveryResult::sent() : DeliveryResult::failed('HTTP '.$response->status());
        } catch (Throwable $exception) {
            report($exception);

            return DeliveryResult::failed(mb_substr($exception->getMessage(), 0, 200));
        }
    }
}
