<?php

namespace App\Models;

use App\Enums\SubscriberStatus;
use Database\Factories\NewsletterSubscriberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A newsletter sign-up. Kept apart from leads so subscribers never enter the sales pipeline;
 * marketing exports the list to whichever email service sends the newsletter.
 */
#[Fillable([
    'email', 'name', 'status', 'source_path', 'consent_text', 'consent_given_at',
    'subscribed_at', 'unsubscribed_at', 'ip', 'user_agent',
])]
class NewsletterSubscriber extends Model
{
    /** @use HasFactory<NewsletterSubscriberFactory> */
    use HasFactory;

    /**
     * Keeps the subscribe/unsubscribe timestamps in step with the status, whichever screen changed it.
     */
    protected static function booted(): void
    {
        static::saving(function (NewsletterSubscriber $subscriber): void {
            if (! $subscriber->isDirty('status') && $subscriber->exists) {
                return;
            }

            if ($subscriber->status === SubscriberStatus::Unsubscribed) {
                $subscriber->unsubscribed_at ??= now();

                return;
            }

            $subscriber->unsubscribed_at = null;
            $subscriber->subscribed_at = $subscriber->exists || $subscriber->subscribed_at === null ? now() : $subscriber->subscribed_at;
        });
    }

    protected function casts(): array
    {
        return [
            'status' => SubscriberStatus::class,
            'consent_given_at' => 'datetime',
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function subscribed(Builder $query): Builder
    {
        return $query->where('status', SubscriberStatus::Subscribed);
    }

    public function unsubscribe(): static
    {
        $this->forceFill(['status' => SubscriberStatus::Unsubscribed])->save();

        return $this;
    }

    public function resubscribe(): static
    {
        $this->forceFill(['status' => SubscriberStatus::Subscribed])->save();

        return $this;
    }
}
