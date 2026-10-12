<?php

namespace App\Livewire\Concerns;

/**
 * A short success or error message shown as a toast after an action.
 */
trait ShowsNotices
{
    protected function notify(string $message, bool $isError = false): void
    {
        $this->dispatch('toast', message: $message, type: $isError ? 'error' : 'success');
    }
}
