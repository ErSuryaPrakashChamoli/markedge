<?php

use App\Enums\SocialPlatform;
use App\Models\Article;
use App\Models\Page;
use App\Models\SocialVideo;

function resourcesPage(array $blocks): Page
{
    return Page::factory()->published()->create(['blocks' => $blocks]);
}

it('shows blogs, news and videos as tabs in the resources teaser', function () {
    Article::factory()->published()->create(['title' => 'A blog about cloud costs']);
    Article::factory()->published()->news()->create(['title' => 'Markedge opens a new office']);
    SocialVideo::factory()->visible()->instagram()->create(['title' => 'Behind the scenes reel']);
    $page = resourcesPage([['type' => 'resources', 'data' => ['heading' => 'Latest resources', 'more_url' => '/resources']]]);

    $this->get('/'.$page->slug)
        ->assertOk()
        ->assertSee('Latest resources')
        ->assertSeeInOrder(['Blogs', 'News', 'Videos'])
        ->assertSee('A blog about cloud costs')
        ->assertSee('Markedge opens a new office')
        ->assertSee('Behind the scenes reel')
        ->assertSee('/insights?type=news', false);
});

it('leaves out empty resource tabs and the whole teaser when nothing is published', function () {
    Article::factory()->create(['title' => 'Draft blog']);
    SocialVideo::factory()->create(['title' => 'Hidden video']);
    $page = resourcesPage([['type' => 'resources', 'data' => ['heading' => 'Latest resources']]]);

    $this->get('/'.$page->slug)
        ->assertOk()
        ->assertDontSee('Latest resources')
        ->assertDontSee('Draft blog')
        ->assertDontSee('Hidden video');
});

it('filters the article grid to one article type', function () {
    Article::factory()->published()->create(['title' => 'Blog post title']);
    Article::factory()->published()->news()->create(['title' => 'News post title']);
    $page = resourcesPage([['type' => 'article_grid', 'data' => ['heading' => 'News', 'mode' => 'latest', 'article_type' => 'news']]]);

    $this->get('/'.$page->slug)
        ->assertOk()
        ->assertSee('News post title')
        ->assertDontSee('Blog post title')
        ->assertSee('/insights?type=news', false);
});

it('plays embeddable social videos on the page and links out the rest', function () {
    SocialVideo::factory()->visible()->instagram()->create(['title' => 'Instagram reel', 'url' => 'https://www.instagram.com/reel/C1a2B3c4D5e/']);
    SocialVideo::factory()->visible()->create(['title' => 'Post on X', 'platform' => SocialPlatform::X, 'url' => 'https://x.com/markedge/status/1234567890']);
    $page = resourcesPage([['type' => 'social_videos', 'data' => ['heading' => 'Videos', 'mode' => 'latest']]]);

    $this->get('/'.$page->slug)
        ->assertOk()
        ->assertSee('https://www.instagram.com/reel/C1a2B3c4D5e/embed/', false)
        ->assertSee('Play Instagram reel')
        ->assertSee('Watch Post on X on X (Twitter) (opens in a new tab)')
        ->assertSee('Filter videos by platform');
});

it('lists featured social videos first and hides invisible ones', function () {
    SocialVideo::factory()->visible()->create(['title' => 'Ordinary video', 'sort_order' => 0]);
    SocialVideo::factory()->visible()->create(['title' => 'Featured video', 'is_featured' => true, 'sort_order' => 5]);
    SocialVideo::factory()->create(['title' => 'Invisible video']);
    $page = resourcesPage([['type' => 'social_videos', 'data' => ['mode' => 'latest']]]);

    $this->get('/'.$page->slug)
        ->assertOk()
        ->assertSeeInOrder(['Featured video', 'Ordinary video'])
        ->assertDontSee('Invisible video');
});

it('narrows the insights listing to news', function () {
    Article::factory()->published()->create(['title' => 'Blog post title']);
    Article::factory()->published()->news()->create(['title' => 'News post title']);

    $this->get('/insights?type=news')
        ->assertOk()
        ->assertSee('News post title')
        ->assertDontSee('Blog post title')
        ->assertSee('noindex', false);

    $this->get('/insights')
        ->assertOk()
        ->assertSee('News post title')
        ->assertSee('Blog post title');
});
