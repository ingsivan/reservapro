<?php

namespace App\Livewire\Concerns;

use App\Models\Business;
use Livewire\Attributes\Computed;

/**
 * Admin components only ever read and write the signed-in owner's business.
 */
trait ManagesBusiness
{
    #[Computed]
    public function business(): Business
    {
        return auth()->user()->business()->firstOrFail();
    }
}
