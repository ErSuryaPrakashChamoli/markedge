<?php

namespace App\Notifications\Channels;

/**
 * A delivery adapter. isConfigured() must be cheap and side-effect free; send() must never throw.
 */
interface NotificationChannel
{
    public function name(): string;

    public function isConfigured(): bool;

    /**
     * Why the channel is or is not usable, for the operations dashboard.
     */
    public function status(): string;

    public function send(NotificationMessage $message): DeliveryResult;
}
