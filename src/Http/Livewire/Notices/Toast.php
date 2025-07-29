<?php

namespace Platform\Notifications\Http\Livewire\Notices;

use Livewire\Component;
use Platform\Notifications\Models\NotificationsNotice;

class Toast extends Component
{
    public NotificationsNotice $notice;

    public function mount(NotificationsNotice $notice): void
    {
        $this->notice = $notice;
    }

    public function render()
    {
        return view('notifications::livewire.notices.toast');
    }
}