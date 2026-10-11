<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ManagesBusiness;
use App\Livewire\Concerns\ShowsNotices;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Servicios')]
class Services extends Component
{
    use ManagesBusiness, ShowsNotices;

    public bool $showForm = false;

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public int|string $durationMinutes = 30;

    /** Price in the business currency, e.g. "250.00". */
    public string $price = '';

    public bool $isActive = true;

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $serviceId): void
    {
        $service = $this->findService($serviceId);

        $this->resetValidation();
        $this->editingId = $service->id;
        $this->name = $service->name;
        $this->description = (string) $service->description;
        $this->durationMinutes = $service->duration_minutes;
        $this->price = number_format($service->price_cents / 100, 2, '.', '');
        $this->isActive = $service->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:300'],
            'durationMinutes' => ['required', 'integer', 'min:5', 'max:480'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000'],
            'isActive' => ['boolean'],
        ], attributes: [
            'name' => 'nombre',
            'description' => 'descripción',
            'durationMinutes' => 'duración',
            'price' => 'precio',
        ]);

        $attributes = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'duration_minutes' => (int) $validated['durationMinutes'],
            'price_cents' => (int) round($validated['price'] * 100),
            'is_active' => $validated['isActive'],
        ];

        if ($this->editingId) {
            $this->findService($this->editingId)->update($attributes);
            $this->notify('Servicio actualizado.');
        } else {
            $this->business->services()->create($attributes);
            $this->notify('Servicio creado. Ya aparece en tu página de reservas.');
        }

        $this->resetForm();
        unset($this->services);
    }

    public function toggleActive(int $serviceId): void
    {
        $service = $this->findService($serviceId);
        $service->update(['is_active' => ! $service->is_active]);
        unset($this->services);
    }

    /**
     * Services with appointments are kept for the history; they can only be hidden.
     */
    public function delete(int $serviceId): void
    {
        $service = $this->findService($serviceId);

        if ($service->appointments()->exists()) {
            $service->update(['is_active' => false]);
            $this->notify('Este servicio tiene citas registradas, así que lo ocultamos en lugar de borrarlo.');
        } else {
            $service->delete();
            $this->notify('Servicio eliminado.');
        }

        if ($this->editingId === $serviceId) {
            $this->resetForm();
        }

        unset($this->services);
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    /**
     * @return Collection<int, Service>
     */
    #[Computed]
    public function services(): Collection
    {
        return $this->business->services()
            ->withCount(['appointments as upcoming_count' => fn ($query) => $query->blocking()->where('starts_at', '>=', now())])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.admin.services');
    }

    private function findService(int $serviceId): Service
    {
        return $this->business->services()->findOrFail($serviceId);
    }

    private function resetForm(): void
    {
        $this->reset('showForm', 'editingId', 'name', 'description', 'durationMinutes', 'price', 'isActive');
        $this->resetValidation();
    }
}
