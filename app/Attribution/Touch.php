<?php

namespace App\Attribution;

/**
 * One acquisition context: where a visit came from and where it landed. Values are already
 * normalised and bounded; null source means "Direct / Unknown", never a guess.
 */
final readonly class Touch
{
    public function __construct(
        public ?string $source,
        public ?string $medium,
        public ?string $campaign,
        public ?string $term,
        public ?string $content,
        public ?string $referrer,
        public string $landingPage,
        public string $at,
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return ['s' => $this->source, 'm' => $this->medium, 'c' => $this->campaign, 't' => $this->term, 'n' => $this->content, 'r' => $this->referrer, 'l' => $this->landingPage, 'at' => $this->at];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        $string = fn (string $key): ?string => is_string($data[$key] ?? null) ? Normaliser::bound($data[$key]) : null;
        $landing = $string('l');
        $at = $string('at');

        if ($landing === null || ! str_starts_with($landing, '/') || $at === null || strtotime($at) === false) {
            return null;
        }

        return new self($string('s'), $string('m'), $string('c'), $string('t'), $string('n'), $string('r'), $landing, $at);
    }
}
