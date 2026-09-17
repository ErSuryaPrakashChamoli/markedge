<?php

namespace App\Notifications\Channels;

use App\Models\NotificationDelivery;
use Illuminate\Database\QueryException;

/**
 * Resolves channels by name and records every delivery outcome. Unknown or unconfigured channels
 * degrade to a logged skip; nothing here throws into the caller.
 */
class ChannelRegistry
{
    /** @var array<string, NotificationChannel> */
    private array $channels = [];

    public function __construct(DatabaseChannel $database, MailChannel $mail, WebhookChannel $webhook)
    {
        foreach ([$database, $mail, $webhook] as $channel) {
            $this->channels[$channel->name()] = $channel;
        }
    }

    /**
     * @return array<string, NotificationChannel>
     */
    public function all(): array
    {
        return $this->channels;
    }

    public function get(string $name): ?NotificationChannel
    {
        return $this->channels[$name] ?? null;
    }

    public function deliver(string $channel, NotificationMessage $message): DeliveryResult
    {
        $adapter = $this->get($channel);

        if ($message->idempotencyKey !== null && NotificationDelivery::query()->where('idempotency_key', $message->idempotencyKey)->exists()) {
            return DeliveryResult::skipped('already delivered');
        }

        $result = $adapter === null ? DeliveryResult::skipped("unknown channel {$channel}") : $adapter->send($message);

        try {
            NotificationDelivery::query()->create([
                'channel' => $channel,
                'status' => $result->status,
                'subject' => mb_substr($message->subject, 0, 200),
                'recipient' => $message->recipients === [] ? null : mb_substr(implode(',', array_map('strval', $message->recipients)), 0, 200),
                'idempotency_key' => $result->ok() ? $message->idempotencyKey : null,
                'error' => $result->reason ? mb_substr($result->reason, 0, 500) : null,
                'source' => $message->source ? mb_substr($message->source, 0, 80) : null,
                'created_at' => now(),
            ]);
        } catch (QueryException $exception) {
            report($exception);
        }

        return $result;
    }
}
