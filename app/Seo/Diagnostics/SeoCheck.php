<?php

namespace App\Seo\Diagnostics;

final readonly class SeoCheck
{
    public const string REQUIRED = 'required';

    public const string RECOMMENDED = 'recommended';

    public const string INFO = 'informational';

    public const string PASS = 'pass';

    public const string WARN = 'warn';

    public const string FAIL = 'fail';

    public const string NOTE = 'note';

    public function __construct(
        public string $key,
        public string $label,
        public string $level,
        public string $status,
        public ?string $detail = null,
    ) {}
}
