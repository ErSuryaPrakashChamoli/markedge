<?php

namespace App\Notifications\Channels;

/**
 * Channel-neutral message. Recipients are channel-specific (user ids, e-mail addresses or none).
 *
 * @param  array<int, int|string>  $recipients
 * @param  array<string, mixed>  $data
 */
final readonly class NotificationMessage
{
    public function __construct(
        public string $subject,
        public string $body,
        public array $recipients = [],
        public ?string $url = null,
        public ?string $source = null,
        public ?string $idempotencyKey = null,
        public array $data = [],
    ) {}
}
