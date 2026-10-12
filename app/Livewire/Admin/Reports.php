<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ManagesBusiness;
use App\Services\BusinessReport;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Income and appointments per month and per service, with a CSV export.
 */
#[Layout('layouts::admin')]
#[Title('Reportes')]
class Reports extends Component
{
    use ManagesBusiness;

    /** Months shown, counting the current one. */
    #[Url(as: 'meses', except: 6)]
    public int $months = 6;

    public function mount(): void
    {
        $this->setPeriod($this->months);
    }

    public function setPeriod(int $months): void
    {
        $this->months = in_array($months, BusinessReport::PERIODS, true) ? $months : 6;
        unset($this->report);
    }

    #[Computed]
    public function report(): BusinessReport
    {
        return new BusinessReport($this->business, $this->months);
    }

    public function render(): View
    {
        return view('livewire.admin.reports');
    }
}
