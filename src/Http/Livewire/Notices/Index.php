<?php

namespace Platform\Notifications\Http\Livewire\Notices;

use Livewire\Component;
use Livewire\Attributes\On;
use Platform\Notifications\Models\NotificationsNotice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class Index extends Component
{
    public $modalShow = false;

    /** Ungelesene Notices (Eloquent-Collection) */
    public Collection $activeNotices;

    /** Anzahl ungelesener Notices (für Badge) */
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->loadUnreadNotices();
    }

    protected function loadUnreadNotices(): void
    {
        $user = Auth::user();

        if (!$user) {
            $this->activeNotices = new \Illuminate\Database\Eloquent\Collection();
            $this->unreadCount = 0;
            return;
        }

        $this->activeNotices = NotificationsNotice::unread()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('team_id', $user->currentTeam?->id);
            })
            ->latest()
            ->get();

        $this->unreadCount = $this->activeNotices->count();
    }

    /** Event: Neue Notice speichern und Liste aktualisieren */
    #[On('notifications:store')]
    public function storeNotice(array $payload): void
    {
        $user = Auth::user();

        NotificationsNotice::create([
            'notice_type'    => $payload['notice_type'] ?? 'toast',
            'title'          => $payload['title'] ?? null,
            'message'        => $payload['message'] ?? null,
            'description'    => $payload['description'] ?? null,
            'properties'     => $payload['properties'] ?? [],
            'metadata'       => $payload['metadata'] ?? [],
            'user_id'        => $user?->id,
            'team_id'        => $user?->currentTeam?->id,
            'noticable_type' => $payload['noticable_type'] ?? null,
            'noticable_id'   => $payload['noticable_id'] ?? null,
            'read_at'        => $payload['read_at'] ?? null,
            'dismissed'      => $payload['dismissed'] ?? false,
        ]);

        $this->loadUnreadNotices();
    }

    /** Wird von Toast-Kindern aufgerufen, wenn sie automatisch oder manuell entfernt wurden */
    #[On('toast-dismissed')]
    public function dismissToast(int $noticeId): void
    {
        $notice = NotificationsNotice::find($noticeId);

        if ($notice) {
            $notice->update(['read_at' => Carbon::now()]);
        }

        // Collection ohne diesen Toast aktualisieren
        $this->activeNotices = $this->activeNotices->reject(fn($n) => $n->id === $noticeId);
        $this->unreadCount = max(0, $this->activeNotices->count());
    }

    public function openModal(): void { $this->modalShow = true; }
    public function closeModal(): void { $this->modalShow = false; }

    public function render()
    {
        return view('notifications::livewire.notices.index');
    }
}