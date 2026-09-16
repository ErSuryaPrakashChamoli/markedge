<?php

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\LandingPage;

it('is running only while active and inside its date range', function () {
    expect(Campaign::factory()->active()->create()->isRunning())->toBeTrue()
        ->and(Campaign::factory()->ended()->create()->isRunning())->toBeFalse()
        ->and(Campaign::factory()->create(['status' => CampaignStatus::Active, 'starts_at' => now()->addDay()])->isRunning())->toBeFalse();
});

it('matches utm campaign codes case-insensitively', function () {
    $campaign = Campaign::factory()->create(['utm_campaign' => 'LMS-Launch-Q4']);

    expect(Campaign::matchingUtmCampaign('lms-launch-q4')->first()?->id)->toBe($campaign->id);
});

it('keeps the campaign when its default landing page is deleted', function () {
    $landingPage = LandingPage::factory()->create();
    $campaign = Campaign::factory()->create(['landing_page_id' => $landingPage->id]);

    $landingPage->forceDelete();

    expect($campaign->fresh()->landing_page_id)->toBeNull();
});
