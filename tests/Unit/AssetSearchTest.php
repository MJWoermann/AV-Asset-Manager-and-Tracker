<?php

namespace Tests\Unit;

use App\Support\AssetSearch;
use Tests\TestCase;

class AssetSearchTest extends TestCase
{
    public function test_highlight_wraps_matching_portion_in_mark_tags(): void
    {
        $html = (string) AssetSearch::highlight('TP-ABC-123', 'ABC');

        $this->assertStringContainsString('<mark class="bg-brand/30 text-inherit rounded-sm px-0.5">ABC</mark>', $html);
        $this->assertStringContainsString('TP-', $html);
        $this->assertStringContainsString('-123', $html);
    }

    public function test_highlight_escapes_html_in_source_text(): void
    {
        $html = (string) AssetSearch::highlight('<script>alert(1)</script>', 'alert');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('<mark class="bg-brand/30 text-inherit rounded-sm px-0.5">alert</mark>', $html);
    }

    public function test_score_value_ranks_exact_above_prefix_above_contains(): void
    {
        $tokens = ['abc'];

        $this->assertSame(0, AssetSearch::scoreValue('abc', 'abc', $tokens));
        $this->assertSame(2, AssetSearch::scoreValue('abcdef', 'abc', $tokens));
        $this->assertSame(4, AssetSearch::scoreValue('xxabcxx', 'abc', $tokens));
    }

    public function test_matches_text_requires_all_tokens(): void
    {
        $this->assertTrue(AssetSearch::matchesText('Yamaha Mixer Desk', 'Yamaha Mixer'));
        $this->assertFalse(AssetSearch::matchesText('Yamaha Mixer Desk', 'Yamaha Camera'));
    }
}
