<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\GitVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GitVersionDisplayTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_authenticated_pages_show_configured_commit_version(): void
    {
        config(['app.version' => 'abc1234']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('assets.index'))
            ->assertOk()
            ->assertSee('abc1234')
            ->assertSee('text-brand-charcoal', false);
    }
}
