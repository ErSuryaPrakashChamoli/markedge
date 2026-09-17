<?php

namespace App\Notifications\Channels;

final readonly class DeliveryResult
{
    private function __construct(public string $status, public ?string $reason = null, public int $delivered = 0) {}

    public static function sent(int $delivered = 1): self
    {
        return new self('sent', null, $delivered);
    }

    public static function skipped(string $reason): self
    {
        return new self('skipped', $reason);
    }

    public static function failed(string $reason): self
    {
        return new self('failed', $reason);
    }

    public function ok(): bool
    {
        return $this->status === 'sent';
    }
}
