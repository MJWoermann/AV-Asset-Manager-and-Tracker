<?php

namespace Tests\Unit;

use App\Support\GitVersion;
use Tests\TestCase;

class GitVersionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        GitVersion::flush();
    }

    protected function tearDown(): void
    {
        GitVersion::flush();

        parent::tearDown();
    }

    public function test_uses_configured_app_version_override(): void
    {
        config(['app.version' => 'deadbee']);

        $this->assertSame('deadbee', GitVersion::short());
    }

    public function test_reads_short_sha_from_git_when_version_unset(): void
    {
        config(['app.version' => null]);

        $version = GitVersion::short();

        $this->assertNotNull($version);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{7}$/', $version);
    }
}
