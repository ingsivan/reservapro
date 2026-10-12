<?php

namespace Tests\Feature;

use App\Models\Business;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_seeder_builds_a_bookable_demo_business(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);

        $business = Business::where('slug', DatabaseSeeder::DEMO_BUSINESS_SLUG)->sole();

        $this->assertSame(5, $business->services()->count());
        $this->assertGreaterThan(20, $business->appointments()->count());
        $this->get('/')->assertOk()->assertSee('Reservar en el negocio demo');
        $this->get(route('booking', $business))->assertOk()->assertSee('Corte y barba')->assertSee($business->logoUrl(), escape: false);
        Storage::disk('public')->assertExists([$business->logo_path, $business->cover_path]);
    }

    #[Test]
    public function the_seeded_appointments_never_overlap(): void
    {
        $this->seed(DatabaseSeeder::class);

        $appointments = Business::sole()->appointments()->orderBy('starts_at')->get();

        foreach ($appointments->sliding(2) as $pair) {
            [$first, $second] = $pair->values()->all();
            $this->assertTrue($first->ends_at->lte($second->starts_at), "{$first->code} overlaps {$second->code}");
        }
    }
}
