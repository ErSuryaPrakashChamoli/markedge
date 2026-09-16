<?php

namespace App\Attribution;

use Illuminate\Support\Str;

/**
 * The visitor's attribution state as carried in the first-party cookie: an anonymous visitor id,
 * an immutable first touch, a replaceable last touch and the most recent tracked CTA click.
 */
final class Attribution
{
    public const int VERSION = 1;

    public function __construct(
        public string $visitorId,
        public ?Touch $first = null,
        public ?Touch $last = null,
        public int $visits = 0,
        public ?int $lastCtaId = null,
        public ?string $lastCtaAt = null,
    ) {}

    public static function fresh(): self
    {
        return new self((string) Str::uuid());
    }

    /**
     * Rebuilds from cookie data, discarding anything malformed. Tampered cookies are already
     * rejected by Laravel's encryption/signing; this guards the shape.
     */
    public static function fromArray(mixed $data): ?self
    {
        if (! is_array($data) || ($data['v'] ?? null) !== self::VERSION || ! is_string($data['id'] ?? null) || ! Str::isUuid($data['id'])) {
            return null;
        }

        $ctaAt = is_string($data['cta_at'] ?? null) && strtotime($data['cta_at']) !== false ? $data['cta_at'] : null;

        return new self(
            visitorId: $data['id'],
            first: is_array($data['first'] ?? null) ? Touch::fromArray($data['first']) : null,
            last: is_array($data['last'] ?? null) ? Touch::fromArray($data['last']) : null,
            visits: max(0, min(100000, (int) ($data['visits'] ?? 0))),
            lastCtaId: is_int($data['cta'] ?? null) && $data['cta'] > 0 ? $data['cta'] : null,
            lastCtaAt: $ctaAt,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'v' => self::VERSION,
            'id' => $this->visitorId,
            'first' => $this->first?->toArray(),
            'last' => $this->last?->toArray(),
            'visits' => $this->visits,
            'cta' => $this->lastCtaId,
            'cta_at' => $this->lastCtaAt,
        ], fn ($value) => $value !== null);
    }

    /**
     * First touch is written once; last touch is replaced only by a real touch.
     */
    public function recordVisit(?Touch $touch, string $landingPage): void
    {
        $this->visits++;

        if ($this->first === null) {
            $this->first = $touch ?? new Touch(null, null, null, null, null, null, $landingPage, now()->toIso8601String());
            $this->last = $this->first;

            return;
        }

        if ($touch !== null) {
            $this->last = $touch;
        } elseif (config('markedge.attribution.direct_overwrites_last_touch')) {
            $this->last = new Touch(null, null, null, null, null, null, $landingPage, now()->toIso8601String());
        }
    }

    public function recordCtaClick(int $ctaId): void
    {
        $this->lastCtaId = $ctaId;
        $this->lastCtaAt = now()->toIso8601String();
    }

    /**
     * The CTA to credit for a conversion happening now, if the click was recent enough.
     */
    public function creditedCtaId(): ?int
    {
        if ($this->lastCtaId === null || $this->lastCtaAt === null) {
            return null;
        }

        $window = (int) config('markedge.attribution.cta_window_minutes', 60);

        return now()->diffInMinutes($this->lastCtaAt, true) <= $window ? $this->lastCtaId : null;
    }
}
