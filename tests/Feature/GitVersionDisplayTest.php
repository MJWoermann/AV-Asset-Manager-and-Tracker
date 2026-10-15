<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\GitVersion;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GitVersionDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        GitVersion::flush();
    }

    protected function tearDown(): void
    {
        GitVersion::flush();

        parent::tearDown();
    }

    protected function scanner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        return $user;
    }

    public function test_authenticated_pages_show_configured_commit_version(): void
    {
        config(['app.version' => 'abc1234']);

        $user = $this->scanner();

        $this->actingAs($user)
            ->get(route('assets.index'))
            ->assertOk()
            ->assertSee('abc1234')
            ->assertSee('text-brand-charcoal', false);
    }
}
