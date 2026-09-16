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

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label, 'url' => $this->url, 'description' => $this->description, 'icon' => $this->icon,
            'badge' => $this->badge, 'openInNewTab' => $this->openInNewTab, 'isHeading' => $this->isHeading,
            'settings' => $this->settings, 'children' => array_map(fn (MenuNode $child): array => $child->toArray(), $this->children),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            label: $data['label'], url: $data['url'] ?? null,
            children: array_map(fn (array $child): self => self::fromArray($child), $data['children'] ?? []),
            description: $data['description'] ?? null, icon: $data['icon'] ?? null, badge: $data['badge'] ?? null,
            openInNewTab: (bool) ($data['openInNewTab'] ?? false), isHeading: (bool) ($data['isHeading'] ?? false),
            settings: $data['settings'] ?? [],
        );
    }

    public function withActive(bool $isActive, array $children): self
    {
        return new self(
            $this->label, $this->url, $children, $this->description, $this->icon, $this->badge,
            $this->openInNewTab, $this->isHeading, $isActive, $this->settings,
        );
    }
}
