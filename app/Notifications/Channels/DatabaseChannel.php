<?php

namespace App\Notifications\Channels;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;

/**
 * Internal admin notifications. Recipients are user ids; always available.
 */
class DatabaseChannel implements NotificationChannel
{
    public function name(): string
    {
        return 'database';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function status(): string
    {
        return 'Configured (admin notifications table)';
    }

    public function send(NotificationMessage $message): DeliveryResult
    {
        $users = User::query()->whereIn('id', array_filter($message->recipients, 'is_int'))->where('is_active', true)->get();

        if ($users->isEmpty()) {
            return DeliveryResult::skipped('no active recipients');
        }

        try {
            $notification = Notification::make()->title($message->subject)->body(mb_strimwidth($message->body, 0, 300, '…'))->icon('heroicon-o-bolt');

            if ($message->url) {
                $notification->actions([Action::make('open')->label('Open')->url($message->url)]);
            }

            $notification->sendToDatabase($users->all());

            return DeliveryResult::sent($users->count());
        } catch (Throwable $exception) {
            report($exception);

            return DeliveryResult::failed(mb_substr($exception->getMessage(), 0, 200));
        }
    }
}
