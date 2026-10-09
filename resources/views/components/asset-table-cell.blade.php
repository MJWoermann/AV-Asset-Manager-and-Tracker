@props([
    'asset',
    'column',
    'item' => null,
    'indent' => false,
    'term' => null,
    'selectedColumns' => [],
])

@php
    use App\Support\AssetSearch;
    use App\Support\AssetTableColumns;

    $value = AssetTableColumns::value($asset, $column, $item);
    $mono = AssetTableColumns::isMono($column);
    $match = $term ? AssetSearch::bestMatch($asset, $term) : null;
    $showMatchHint = $column === 'name'
        && $match
        && $match['field'] !== 'name'
        && ! in_array($match['field'], $selectedColumns, true);
@endphp

<td {{ $attributes->class(['px-3 py-2', 'font-mono' => $mono, 'pl-10' => $indent && $column === 'name']) }}>
    @if($column === 'name')
        <a class="text-brand hover:underline" href="{{ route('assets.show', $asset) }}">
            <x-highlight-match :text="$value" :term="$term" />
        </a>
        @if($showMatchHint)
            <div class="text-xs text-brand-charcoal dark:text-brand-silver mt-0.5 font-sans">
                {{ $match['label'] }}:
                <span class="font-mono"><x-highlight-match :text="$match['value']" :term="$term" /></span>
            </div>
        @endif
    @elseif($column === 'parent' && $asset->parent)
        <a class="text-brand hover:underline" href="{{ route('assets.show', $asset->parent) }}">
            <x-highlight-match :text="$value" :term="$term" />
        </a>
    @elseif($value !== '')
        <x-highlight-match :text="$value" :term="$term" />
    @else
        —
    @endif
</td>
