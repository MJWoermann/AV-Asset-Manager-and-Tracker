<?php

namespace Tests\Unit;

use App\Support\TextSearch;
use Tests\TestCase;

class TextSearchTest extends TestCase
{
    public function test_tokens_split_on_whitespace(): void
    {
        $this->assertSame(['Yamaha', '9001'], TextSearch::tokens('  Yamaha   9001 '));
        $this->assertSame([], TextSearch::tokens(''));
        $this->assertSame([], TextSearch::tokens(null));
    }

    public function test_escape_like_escapes_metacharacters(): void
    {
        $this->assertSame('100\\%\\_done', TextSearch::escapeLike('100%_done'));
        $this->assertSame('a\\\\b', TextSearch::escapeLike('a\\b'));
    }

    public function test_matches_text_requires_all_tokens(): void
    {
        $this->assertTrue(TextSearch::matchesText('Yamaha Mixer Desk', 'Yamaha Mixer'));
        $this->assertFalse(TextSearch::matchesText('Yamaha Mixer Desk', 'Yamaha Camera'));
    }
}
