<?php

namespace App\Livewire\Concerns;

/**
 * A one-line success or error message shown inside the component after an action.
 */
trait ShowsNotices
{
    public ?string $notice = null;

    public bool $noticeIsError = false;

    protected function notify(string $message, bool $isError = false): void
    {
        $this->notice = $message;
        $this->noticeIsError = $isError;
    }
}
