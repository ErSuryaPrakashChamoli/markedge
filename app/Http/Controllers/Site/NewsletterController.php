<?php

namespace App\Http\Controllers\Site;

use App\Attribution\Normaliser;
use App\Enums\SubscriberStatus;
use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Services\Cms\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

/**
 * POST /newsletter. A plain form post (no Livewire) so the sign-up works on every page, including the
 * footer band of pages that ship no Livewire. The visitor always lands back on the page they came from,
 * scrolled to the form; nothing in the request can redirect them elsewhere.
 */
class NewsletterController extends Controller
{
    public const string DEFAULT_CONSENT_TEXT = 'I agree to receive the Markedge newsletter. I can unsubscribe at any time.';

    public function __invoke(Request $request, Settings $settings): RedirectResponse
    {
        $placement = in_array($request->input('placement'), ['footer', 'block'], true) ? $request->input('placement') : 'block';
        $sourcePath = Normaliser::path(is_string($request->input('source')) ? $request->input('source') : null);
        $back = url($sourcePath ?? '/').'#newsletter-'.$placement;

        // Bots fill the hidden field; pretend success without storing anything.
        if (filled($request->input('website'))) {
            return redirect()->to($back)->with('newsletter.subscribed', $placement);
        }

        $consentText = (string) $settings->get('newsletter.consent_text', self::DEFAULT_CONSENT_TEXT);

        $validator = Validator::make($request->only(['email', 'consent']), [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'consent' => filled($consentText) ? ['accepted'] : ['nullable'],
        ], [
            'consent.accepted' => 'Please tick the box to confirm you want the newsletter.',
        ]);

        if ($validator->fails()) {
            return redirect()->to($back)->withErrors($validator, 'newsletter')->withInput($request->only(['email', 'placement']));
        }

        $limiterKey = 'newsletter:'.$request->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, (int) config('markedge.leads.submissions_per_minute', 5))) {
            return redirect()->to($back)
                ->withErrors(['email' => 'Too many sign-ups in a short time. Please wait a minute and try again.'], 'newsletter')
                ->withInput($request->only(['email', 'placement']));
        }

        RateLimiter::hit($limiterKey, 60);

        $this->subscribe(mb_strtolower(trim($validator->validated()['email'])), $request, $sourcePath, filled($consentText) ? $consentText : null);

        return redirect()->to($back)->with('newsletter.subscribed', $placement);
    }

    /**
     * New addresses are stored; a returning unsubscriber is subscribed again; an address that is already
     * subscribed is left untouched, and the visitor sees the same thank-you either way.
     */
    protected function subscribe(string $email, Request $request, ?string $sourcePath, ?string $consentText): void
    {
        $subscriber = NewsletterSubscriber::query()->firstOrNew(['email' => $email]);

        if ($subscriber->exists && $subscriber->status === SubscriberStatus::Subscribed) {
            return;
        }

        $subscriber->fill([
            'status' => SubscriberStatus::Subscribed,
            'source_path' => $sourcePath,
            'consent_text' => $consentText ? mb_substr($consentText, 0, 500) : null,
            'consent_given_at' => $consentText ? now() : null,
            'ip' => config('markedge.privacy.store_ip') ? $request->ip() : null,
            'user_agent' => $request->userAgent() ? mb_substr($request->userAgent(), 0, 500) : null,
        ])->save();
    }
}
