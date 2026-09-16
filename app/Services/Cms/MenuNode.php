<?php

namespace App\Services\Cms;

/**
 * Render-ready menu entry. Built once by MenuBuilder and cached; the active flag
 * is applied per request.
 */
final readonly class MenuNode
{
    /**
     * @param  array<int, MenuNode>  $children
     * @param  array<string, mixed>  $settings
     */
    public function __construct(
        public string $label,
        public ?string $url = null,
        public array $children = [],
        public ?string $description = null,
        public ?string $icon = null,
        public ?string $badge = null,
        public bool $openInNewTab = false,
        public bool $isHeading = false,
        public bool $isActive = false,
        public array $settings = [],
    ) {}

    public function hasChildren(): bool
    {
        return $this->children !== [];
    }

    public function isMegaMenu(): bool
    {
        return (bool) ($this->settings['mega_menu'] ?? false);
    }

    public function withActive(bool $isActive, array $children): self
    {
        return new self(
            $this->label, $this->url, $children, $this->description, $this->icon, $this->badge,
            $this->openInNewTab, $this->isHeading, $isActive, $this->settings,
        );
    }
}
