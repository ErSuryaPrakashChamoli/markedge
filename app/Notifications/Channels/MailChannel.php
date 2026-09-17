<?php

namespace App\Notifications\Channels;

use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Plain-text e-mail. Configured only when a real mailer and a from address exist; the log and
 * array mailers count as NOT CONFIGURED so nothing pretends to have been sent.
 */
class MailChannel implements NotificationChannel
{
    public function name(): string
    {
        return 'mail';
    }

    public function isConfigured(): bool
    {
        return ! in_array(config('mail.default'), ['log', 'array', null, ''], true) && filled(config('mail.from.address'));
    }

    public function status(): string
    {
        return $this->isConfigured() ? 'Configured (mailer: '.config('mail.default').')' : 'NOT CONFIGURED (MAIL_MAILER is '.(config('mail.default') ?: 'unset').' or MAIL_FROM_ADDRESS missing)';
    }

    public function send(NotificationMessage $message): DeliveryResult
    {
        if (! $this->isConfigured()) {
            return DeliveryResult::skipped('mail channel not configured');
        }

        $recipients = array_values(array_filter($message->recipients, fn ($r) => is_string($r) && filter_var($r, FILTER_VALIDATE_EMAIL)));

        if ($recipients === []) {
            return DeliveryResult::skipped('no valid e-mail recipients');
        }

        try {
            Mail::raw($message->body.($message->url ? "\n\n{$message->url}" : ''), function ($mail) use ($message, $recipients): void {
                $mail->to($recipients)->subject($message->subject);
            });

            return DeliveryResult::sent(count($recipients));
        } catch (Throwable $exception) {
            report($exception);

            return DeliveryResult::failed(mb_substr($exception->getMessage(), 0, 200));
        }
    }
}
