{{-- Public search. GET state only; every outcome renders here (architecture §33, Phase 7). --}}
<x-layouts.app :meta="$meta">
    <x-ui.section theme="dark" pattern="grid" spacing="sm">
        <x-layout.breadcrumbs :items="[['label' => 'Search', 'url' => null]]" class="mb-8" />
        <div class="max-w-3xl">
            <x-ui.eyebrow>Search</x-ui.eyebrow>
            <h1 class="mt-4 text-h1">Search Markedge</h1>
            <p class="mt-4 text-body-lg text-fg-secondary">Find services, products, solutions, industries, case studies and insights.</p>
        </div>

        <form action="{{ route('search') }}" method="get" role="search" class="mt-8 max-w-3xl">
            <label for="search-q" class="sr-only">Search term</label>
            <div class="flex flex-col gap-3 sm:flex-row">
                <x-forms.input id="search-q" type="search" name="q" :value="$query->term" placeholder="e.g. software development, IT AMC, lead management" autocomplete="off" :maxlength="\App\Search\QueryNormalizer::MAX_LENGTH" class="flex-1" :invalid="$tooShort" />
                <x-ui.button type="submit" size="lg">Search</x-ui.button>
            </div>
            @if ($tooShort)
                <p class="mt-2 text-caption text-danger" role="alert">Enter at least {{ \App\Search\QueryNormalizer::MIN_LENGTH }} characters.</p>
            @endif
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="search-type" class="mb-1.5 block text-body-sm font-medium text-fg">Content type</label>
                    <x-forms.select id="search-type" name="type" placeholder="All content">
                        @foreach ($types as $key => $label)
                            <option value="{{ $key }}" @selected($query->type === $key)>{{ $label }}</option>
                        @endforeach
                    </x-forms.select>
                </div>
                @if ($categories !== [])
                    <div>
                        <label for="search-category" class="mb-1.5 block text-body-sm font-medium text-fg">Category</label>
                        <x-forms.select id="search-category" name="category" placeholder="All categories">
                            @foreach ($categories as $group => $options)
                                <optgroup label="{{ $group }}">
                                    @foreach ($options as $slug => $name)
                                        <option value="{{ $slug }}" @selected($query->category === $slug)>{{ $name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </x-forms.select>
                    </div>
                @endif
            </div>
        </form>
    </x-ui.section>

    <x-ui.section spacing="sm" container="narrow" as="div">
        @if ($failed)
            <div role="alert" class="rounded-card border border-line bg-surface card-p">
                <h2 class="text-h3">Search is temporarily unavailable</h2>
                <p class="mt-2 text-body text-fg-secondary">We could not run your search just now. Please try again in a moment, or browse our <a href="{{ url('/services') }}" class="text-fg underline decoration-brand underline-offset-4">services</a> and <a href="{{ url('/products') }}" class="text-fg underline decoration-brand underline-offset-4">products</a>.</p>
            </div>
        @elseif (! $query->hasTerm())
            <div class="stack-text">
                <h2 class="text-h3">Start with what you need</h2>
                <ul class="flex flex-wrap gap-2">
                    @foreach (['software development', 'IT AMC', 'cloud', 'SEO', 'lead management', 'recruitment'] as $suggestion)
                        <li><a href="{{ route('search', ['q' => $suggestion]) }}" class="inline-flex h-9 items-center rounded-pill border border-line-strong px-4 text-body-sm font-medium text-fg-secondary hover:border-fg hover:text-fg">{{ $suggestion }}</a></li>
                    @endforeach
                </ul>
                <p class="text-body-sm text-fg-muted">Or browse <a href="{{ url('/services') }}" class="underline decoration-brand underline-offset-4">services</a>, <a href="{{ url('/products') }}" class="underline decoration-brand underline-offset-4">products</a>, <a href="{{ url('/solutions') }}" class="underline decoration-brand underline-offset-4">solutions</a> and <a href="{{ url('/insights') }}" class="underline decoration-brand underline-offset-4">insights</a>.</p>
            </div>
        @elseif ($tooShort)
            <p class="text-body text-fg-secondary">Please enter a longer search term.</p>
        @elseif ($results !== null && $results->isEmpty())
            <div class="stack-text">
                <h2 class="text-h3">No results for “{{ $query->term }}”</h2>
                <p class="text-body text-fg-secondary">Try different words, remove filters, or browse the site.</p>
                <div class="flex flex-wrap gap-3">
                    @if ($query->type || $query->category)
                        <x-ui.button :href="route('search', ['q' => $query->term])" variant="outline">Search all content</x-ui.button>
                    @endif
                    <x-ui.button href="{{ url('/services') }}" variant="outline">Explore services</x-ui.button>
                    <x-ui.button href="{{ url('/products') }}" variant="outline">View products</x-ui.button>
                </div>
            </div>
        @elseif ($results !== null && $results->items() === [])
            <div class="stack-text">
                <h2 class="text-h3">Nothing on this page</h2>
                <p class="text-body text-fg-secondary">This page is beyond the last page of results.</p>
                <x-ui.button :href="route('search', array_filter(['q' => $query->term, 'type' => $query->type, 'category' => $query->category]))" variant="outline">Back to the first page</x-ui.button>
            </div>
        @elseif ($results !== null)
            <p class="text-body-sm text-fg-muted" role="status" aria-live="polite">
                {{ $results->total() }} {{ \Illuminate\Support\Str::plural('result', $results->total()) }} for “{{ $query->term }}”@if ($query->type) in {{ $types[$query->type] }}@endif
                @if ($results->usedAnyTermFallback)<span> — showing pages that match any of your words</span>@endif
            </p>
            <ul class="mt-4">
                @foreach ($results->items() as $result)
                    <x-cards.search-result :result="$result" />
                @endforeach
            </ul>
            @if ($results->paginator->hasPages())
                <div class="mt-10">{{ $results->paginator->onEachSide(1)->links('pagination.site') }}</div>
            @endif
        @endif
    </x-ui.section>

    @if ($cta)
        <x-cta.band :cta="$cta" />
    @endif
</x-layouts.app>
