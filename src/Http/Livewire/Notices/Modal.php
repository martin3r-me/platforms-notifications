<?php

namespace Platform\Notifications\Http\Livewire\Notices;

use Livewire\Component;
use Livewire\Attributes\On;
use Platform\Notifications\Models\NotificationsNotice;
use Illuminate\Support\Facades\Auth;

class Modal extends Component
{
    public $modalShow;

    /**
     * Lauscht auf ein Event "notifications:store" und speichert die Benachrichtigung.
     */
    #[On('open-modal-terminal')] 
    public function openModalTerminal()
    {
        $this->modalShow = true;
    }

    public function openModal()
    {
        $this->modalShow = true;
    }

    public function closeModal()
    {
        $this->modalShow = false;
    }

    public function render()
    {
        return view('notifications::livewire.notices.modal');
    }
}