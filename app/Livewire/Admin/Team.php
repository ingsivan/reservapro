<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ManagesBusiness;
use App\Livewire\Concerns\ShowsNotices;
use App\Models\Service;
use App\Models\StaffMember;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The people who attend customers, each with their own agenda.
 */
#[Layout('layouts::admin')]
#[Title('Equipo')]
class Team extends Component
{
    use ManagesBusiness, ShowsNotices;

    public bool $showForm = false;

    #[Locked]
    public ?int $editingId = null;

    public string $name = '';

    public string $title = '';

    public string $color = 'teal';

    /** @var list<int|string> */
    public array $serviceIds = [];

    public function create(): void
    {
        $this->resetForm();
        $this->color = $this->nextColor();
        $this->serviceIds = $this->services->where('is_active', true)->pluck('id')->all();
        $this->showForm = true;
    }

    public function edit(int $staffMemberId): void
    {
        $staffMember = $this->findStaffMember($staffMemberId);

        $this->resetValidation();
        $this->editingId = $staffMember->id;
        $this->name = $staffMember->name;
        $this->title = (string) $staffMember->title;
        $this->color = $staffMember->color;
        $this->serviceIds = $staffMember->services()->pluck('services.id')->all();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'title' => ['nullable', 'string', 'max:60'],
            'color' => ['required', Rule::in(array_keys(StaffMember::COLORS))],
            'serviceIds' => ['array'],
            'serviceIds.*' => ['integer', Rule::in($this->services->pluck('id'))],
        ], attributes: [
            'name' => 'nombre',
            'title' => 'puesto',
        ]);

        $attributes = [
            'name' => $validated['name'],
            'title' => $validated['title'] ?: null,
            'color' => $validated['color'],
        ];

        if ($this->editingId) {
            $staffMember = $this->findStaffMember($this->editingId);
            $staffMember->update($attributes);
            $this->notify('Cambios guardados.');
        } else {
            $isFirst = ! $this->business->staffMembers()->exists();
            $staffMember = $this->business->staffMembers()->create($attributes);

            // The business worked alone until now: its agenda becomes this person's.
            if ($isFirst) {
                $this->business->appointments()->whereNull('staff_member_id')->update(['staff_member_id' => $staffMember->id]);
            }

            $this->notify($isFirst
                ? "Agregamos a {$staffMember->firstName()}. Las citas que ya tenías quedaron en su agenda."
                : "Agregamos a {$staffMember->firstName()}. Tus clientes ya pueden elegirle al reservar.");
        }

        $staffMember->services()->sync(array_map('intval', $validated['serviceIds']));

        $this->resetForm();
        unset($this->staffMembers);
    }

    public function toggleActive(int $staffMemberId): void
    {
        $staffMember = $this->findStaffMember($staffMemberId);
        $staffMember->update(['is_active' => ! $staffMember->is_active]);
        $upcoming = $this->upcomingCount($staffMember);

        $this->notify(match (true) {
            $staffMember->is_active => "{$staffMember->firstName()} vuelve a recibir reservas.",
            $upcoming > 0 => "{$staffMember->firstName()} ya no recibe reservas. Sus {$upcoming} citas próximas siguen en la agenda.",
            default => "{$staffMember->firstName()} ya no recibe reservas.",
        });
        unset($this->staffMembers);
    }

    /**
     * People with appointments are kept for the history; they can only be deactivated.
     */
    public function delete(int $staffMemberId): void
    {
        $staffMember = $this->findStaffMember($staffMemberId);

        if ($staffMember->appointments()->exists()) {
            $staffMember->update(['is_active' => false]);
            $this->notify("{$staffMember->firstName()} tiene citas registradas, así que solo dejó de recibir reservas.");
        } else {
            $staffMember->delete();
            $this->notify('Persona eliminada del equipo.');
        }

        if ($this->editingId === $staffMemberId) {
            $this->resetForm();
        }

        unset($this->staffMembers);
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    /**
     * @return Collection<int, StaffMember>
     */
    #[Computed]
    public function staffMembers(): Collection
    {
        return $this->business->staffMembers()
            ->with('services')
            ->withCount('schedules')
            ->withCount(['appointments as upcoming_count' => fn ($query) => $query->blocking()->where('starts_at', '>=', now())])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Service>
     */
    #[Computed]
    public function services(): Collection
    {
        return $this->business->services()->orderBy('name')->get();
    }

    public function render(): View
    {
        return view('livewire.admin.team');
    }

    private function upcomingCount(StaffMember $staffMember): int
    {
        return $staffMember->appointments()->blocking()->where('starts_at', '>=', now())->count();
    }

    /**
     * The first color nobody on the team uses yet, so each person stands out.
     */
    private function nextColor(): string
    {
        $used = $this->business->staffMembers()->pluck('color')->all();

        return collect(array_keys(StaffMember::COLORS))->first(fn (string $color) => ! in_array($color, $used, true)) ?? 'teal';
    }

    private function findStaffMember(int $staffMemberId): StaffMember
    {
        return $this->business->staffMembers()->findOrFail($staffMemberId);
    }

    private function resetForm(): void
    {
        $this->reset('showForm', 'editingId', 'name', 'title', 'color', 'serviceIds');
        $this->resetValidation();
    }
}
