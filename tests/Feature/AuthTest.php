<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Business;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_customer_can_register(): void
    {
        $this->post('/register', [
            'account_type' => 'client',
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ])->assertRedirect(route('my-appointments'));

        $user = User::sole();
        $this->assertSame(Role::Client, $user->role);
        $this->assertNull($user->business);
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function a_business_owner_registers_with_a_ready_to_use_business(): void
    {
        $this->post('/register', [
            'account_type' => 'admin',
            'business_name' => 'Estética Luna',
            'name' => 'Laura',
            'email' => 'laura@example.com',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ])->assertRedirect(route('admin.agenda'));

        $business = Business::sole();
        $this->assertSame('estetica-luna', $business->slug);
        $this->assertSame(6, $business->schedules()->count());
        $this->assertTrue($business->owner->isAdmin());
    }

    #[Test]
    public function business_slugs_stay_unique(): void
    {
        Business::factory()->create(['slug' => 'estetica-luna']);

        $this->post('/register', [
            'account_type' => 'admin',
            'business_name' => 'Estética Luna',
            'name' => 'Laura',
            'email' => 'laura@example.com',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ]);

        $this->assertTrue(Business::where('slug', 'estetica-luna-2')->exists());
    }

    #[Test]
    public function a_business_account_needs_a_business_name(): void
    {
        $this->post('/register', [
            'account_type' => 'admin',
            'name' => 'Laura',
            'email' => 'laura@example.com',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ])->assertSessionHasErrors('business_name');

        $this->assertGuest();
    }

    #[Test]
    public function users_land_on_their_own_area_after_signing_in(): void
    {
        $admin = Business::factory()->create()->owner;
        $client = User::factory()->create(['role' => Role::Client]);

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect(route('admin.agenda'));
        $this->post('/logout');
        $this->post('/login', ['email' => $client->email, 'password' => 'password'])->assertRedirect(route('my-appointments'));
    }

    #[Test]
    public function wrong_credentials_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function the_demo_button_signs_in_as_the_demo_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('demo.login'))->assertRedirect(route('admin.agenda'));

        $this->assertAuthenticatedAs(User::where('email', DatabaseSeeder::DEMO_ADMIN_EMAIL)->sole());
    }

    #[Test]
    public function the_demo_button_explains_when_there_is_no_demo_data(): void
    {
        $this->from('/')->post(route('demo.login'))->assertRedirect('/')->assertSessionHas('error');

        $this->assertGuest();
    }
}
