<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProgressiveWebAppTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_manifest_describes_an_installable_app(): void
    {
        $response = $this->get('/manifest.webmanifest');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJson([
                'name' => 'ReservaPro',
                'start_url' => '/app',
                'scope' => '/',
                'display' => 'standalone',
            ]);

        foreach ($response->json('icons') as $icon) {
            $this->assertFileExists(public_path($icon['src']));
        }
        $this->assertContains('maskable', array_column($response->json('icons'), 'purpose'));
    }

    #[Test]
    public function the_service_worker_and_offline_page_are_published(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertStringContainsString("'/offline.html'", file_get_contents(public_path('sw.js')));
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('icons/apple-touch-icon.png'));
    }

    #[Test]
    public function pages_link_the_manifest_and_the_owner_panel_offers_to_install(): void
    {
        $business = Business::factory()->create();

        $this->get('/')->assertOk()->assertSee('<link rel="manifest" href="'.route('manifest').'">', false);
        $this->actingAs($business->owner)->get(route('admin.agenda'))
            ->assertOk()
            ->assertSee('<link rel="manifest"', false)
            ->assertSee('Instalar la app');
    }

    #[Test]
    public function the_installed_app_opens_where_each_person_works(): void
    {
        $business = Business::factory()->create();
        $client = User::factory()->create(['role' => Role::Client]);

        $this->get('/app')->assertRedirect(route('login'));
        $this->actingAs($business->owner)->get('/app')->assertRedirect(route('admin.agenda'));
        $this->actingAs($client)->get('/app')->assertRedirect(route('my-appointments'));
    }
}
