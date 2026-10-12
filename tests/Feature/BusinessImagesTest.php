<?php

namespace Tests\Feature;

use App\Livewire\Admin\BusinessSettings;
use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BusinessImagesTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->business = Business::factory()->openDaily()->create(['name' => 'Barbería El Clásico']);
    }

    #[Test]
    public function the_owner_uploads_a_logo_and_a_cover(): void
    {
        Livewire::actingAs($this->business->owner)
            ->test(BusinessSettings::class)
            ->set('logo', UploadedFile::fake()->image('logo.png', 200, 200))
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: 'Logo actualizado.')
            ->set('cover', UploadedFile::fake()->image('portada.jpg', 1600, 500))
            ->assertHasNoErrors();

        $business = $this->business->fresh();
        Storage::disk('public')->assertExists($business->logo_path);
        Storage::disk('public')->assertExists($business->cover_path);
        $this->assertStringStartsWith("businesses/{$business->id}/", $business->logo_path);

        $this->get(route('booking', $business))
            ->assertSee($business->logoUrl(), escape: false)
            ->assertSee($business->coverUrl(), escape: false)
            ->assertDontSee('>BC<', escape: false);
    }

    #[Test]
    public function replacing_or_removing_an_image_deletes_the_old_file(): void
    {
        $component = Livewire::actingAs($this->business->owner)
            ->test(BusinessSettings::class)
            ->set('logo', UploadedFile::fake()->image('uno.png', 200, 200));
        $firstPath = $this->business->fresh()->logo_path;

        $component->set('logo', UploadedFile::fake()->image('dos.png', 200, 200));
        $secondPath = $this->business->fresh()->logo_path;

        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);

        $component->call('removeLogo');

        Storage::disk('public')->assertMissing($secondPath);
        $this->assertNull($this->business->fresh()->logo_path);
        $this->get(route('booking', $this->business))->assertSee('BC');
    }

    #[Test]
    public function only_images_of_a_useful_size_are_accepted(): void
    {
        Livewire::actingAs($this->business->owner)
            ->test(BusinessSettings::class)
            ->set('logo', UploadedFile::fake()->create('menu.pdf', 100, 'application/pdf'))
            ->assertHasErrors('logo')
            ->set('logo', UploadedFile::fake()->image('chico.png', 40, 40))
            ->assertHasErrors('logo')
            ->set('cover', UploadedFile::fake()->image('vertical.jpg', 400, 800))
            ->assertHasErrors('cover');

        $this->assertNull($this->business->fresh()->logo_path);
        $this->assertNull($this->business->fresh()->cover_path);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
