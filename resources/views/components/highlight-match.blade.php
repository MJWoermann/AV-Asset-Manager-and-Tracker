@props([
    'text' => '',
    'term' => null,
])

{!! \App\Support\AssetSearch::highlight(is_scalar($text) ? (string) $text : '', $term) !!}
