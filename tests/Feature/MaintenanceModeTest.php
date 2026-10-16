<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    protected function tearDown(): void
    {
        Artisan::call('up');

        parent::tearDown();
    }

    public function test_maintenance_mode_redirects_to_home_and_shows_server_maintenance_page(): void
    {
        Artisan::call('down', [
            '--redirect' => '/',
            '--render' => 'errors.503',
            '--retry' => 60,
            '--refresh' => 15,
        ]);

        $this->get('/login')
            ->assertRedirect('/');

        $this->get('/')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '60')
            ->assertHeader('Refresh', '15')
            ->assertSee('Server maintenance', false)
            ->assertSee('temporarily unavailable while an update is applied', false);
    }

    public function test_503_view_renders_when_maintenance_mode_has_no_prerendered_template(): void
    {
        Artisan::call('down', [
            '--retry' => 60,
        ]);

        $this->get('/login')
            ->assertStatus(503)
            ->assertSee('Server maintenance', false);
    }
}
