<?php

namespace App\Cms\Blocks;

use App\Cms\Blocks\Types\ArticleGrid;
use App\Cms\Blocks\Types\CapabilityIntro;
use App\Cms\Blocks\Types\CaseStudyGrid;
use App\Cms\Blocks\Types\Comparison;
use App\Cms\Blocks\Types\ContactForm;
use App\Cms\Blocks\Types\Cta;
use App\Cms\Blocks\Types\Faq;
use App\Cms\Blocks\Types\FeatureGrid;
use App\Cms\Blocks\Types\Hero;
use App\Cms\Blocks\Types\ImageContent;
use App\Cms\Blocks\Types\IndustryGrid;
use App\Cms\Blocks\Types\LeadForm;
use App\Cms\Blocks\Types\LogoCloud;
use App\Cms\Blocks\Types\Process;
use App\Cms\Blocks\Types\ProductShowcase;
use App\Cms\Blocks\Types\RelatedContent;
use App\Cms\Blocks\Types\RelatedProducts;
use App\Cms\Blocks\Types\RelatedServices;
use App\Cms\Blocks\Types\RichText;
use App\Cms\Blocks\Types\ServiceGrid;
use App\Cms\Blocks\Types\SolutionGrid;
use App\Cms\Blocks\Types\SplitContent;
use App\Cms\Blocks\Types\Stats;
use App\Cms\Blocks\Types\TechnologyGrid;
use App\Cms\Blocks\Types\Testimonials;
use App\Cms\Blocks\Types\Timeline;
use App\Cms\Blocks\Types\Video;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

/**
 * The whitelist of block types (architecture §6.3). Anything not listed here
 * can never be stored, rendered or executed.
 */
class BlockRegistry
{
    /** @var array<int, class-string<Block>> */
    public const array TYPES = [
        Hero::class,
        CapabilityIntro::class,
        SplitContent::class,
        RichText::class,
        ImageContent::class,
        Video::class,
        FeatureGrid::class,
        Stats::class,
        ServiceGrid::class,
        ProductShowcase::class,
        SolutionGrid::class,
        IndustryGrid::class,
        TechnologyGrid::class,
        LogoCloud::class,
        Process::class,
        Timeline::class,
        CaseStudyGrid::class,
        Testimonials::class,
        Faq::class,
        Comparison::class,
        Cta::class,
        LeadForm::class,
        ContactForm::class,
        ArticleGrid::class,
        RelatedContent::class,
        RelatedServices::class,
        RelatedProducts::class,
    ];

    /** @var array<string, Block>|null */
    private ?array $instances = null;

    /**
     * @return array<string, Block>
     */
    public function all(): array
    {
        if ($this->instances === null) {
            $this->instances = [];

            foreach (self::TYPES as $class) {
                $block = new $class;
                $this->instances[$block->key()] = $block;
            }
        }

        return $this->instances;
    }

    /**
     * @return array<string, Block>
     */
    public function forHost(string $host): array
    {
        return array_filter($this->all(), fn (Block $block): bool => $block->allowedOn($host));
    }

    public function find(string $key): ?Block
    {
        return $this->all()[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return $this->find($key) !== null;
    }

    /**
     * Validates a stored block tree. Returns a message bag; empty means valid.
     *
     * @param  array<int, mixed>|null  $blocks
     */
    public function validate(?array $blocks, string $host): MessageBag
    {
        $errors = new MessageBag;

        foreach ($blocks ?? [] as $index => $entry) {
            $position = is_int($index) ? $index + 1 : (string) $index;

            if (! is_array($entry) || ! is_string($entry['type'] ?? null)) {
                $errors->add("blocks.{$index}", "Block {$position} is malformed.");

                continue;
            }

            $block = $this->find($entry['type']);

            if ($block === null) {
                $errors->add("blocks.{$index}", "Block {$position} has an unknown type \"{$entry['type']}\".");

                continue;
            }

            if (! $block->allowedOn($host)) {
                $errors->add("blocks.{$index}", "Block {$position} ({$block->label()}) cannot be used here.");

                continue;
            }

            $data = is_array($entry['data'] ?? null) ? $entry['data'] : [];
            $validator = Validator::make($data, $block->allRules());

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $message) {
                    $errors->add("blocks.{$index}", "Block {$position} ({$block->label()}): {$message}");
                }
            }
        }

        return $errors;
    }
}
