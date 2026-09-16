<?php

namespace App\View\Components\Layout;

use App\Enums\AnnouncementDisplay;
use App\Models\Announcement;
use App\Services\Cms\ContentVersion;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;

class AnnouncementBar extends Component
{
    public function __construct(private readonly ContentVersion $version) {}

    public function render(): View
    {
        return view('components.layout.announcement-bar', [
            'announcement' => Cache::remember(
                $this->version->key('announcement:bar'),
                now()->addMinutes(10),
                fn (): ?Announcement => Announcement::query()
                    ->current()
                    ->where('display', AnnouncementDisplay::Bar)
                    ->latest('id')
                    ->first(),
            ),
        ]);
    }
}
