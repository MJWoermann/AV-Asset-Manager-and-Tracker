<?php

namespace Tests\Unit;

use App\Support\SafeReturnUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SafeReturnUrlTest extends TestCase
{
    #[DataProvider('validUrls')]
    public function test_accepts_same_app_relative_paths(string $url, string $expected): void
    {
        $this->assertSame($expected, SafeReturnUrl::sanitize($url));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function validUrls(): array
    {
        return [
            'scan index' => ['/scan', '/scan'],
            'with query' => ['/reports/unknown-scans?status=open&q=foo', '/reports/unknown-scans?status=open&q=foo'],
            'trimmed' => ['  /scan  ', '/scan'],
        ];
    }

    #[DataProvider('invalidUrls')]
    public function test_rejects_open_redirect_and_external_urls(mixed $url): void
    {
        $this->assertNull(SafeReturnUrl::sanitize($url));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidUrls(): array
    {
        return [
            'absolute https' => ['https://evil.example/phish'],
            'protocol relative' => ['//evil.example/phish'],
            'scheme mid-path' => ['/scan?next=https://evil.example'],
            'backslash' => ['/scan\\..\\evil'],
            'empty' => [''],
            'whitespace' => ['   '],
            'null' => [null],
            'integer' => [1],
            'no leading slash' => ['scan'],
            'too long' => ['/'.str_repeat('a', SafeReturnUrl::MAX_LENGTH)],
        ];
    }
}
