{{--
    Renders the prepared block list from BlockRenderer. Each entry maps to components/blocks/{key}.blade.php;
    nothing here evaluates data as code, and unknown blocks only appear as labelled notices in preview.
--}}
@props(['blocks' => [], 'host' => null, 'preview' => false])
@foreach ($blocks as $block)
    <x-dynamic-component :component="$block['component']" :data="$block['data']" :host="$host" :preview="$preview" />
@endforeach
