<div>
    {{-- Floating Button mit Badge --}}
    @if(config('notifications.show_modal'))
    <div class="position-fixed bottom-4 right-4 z-50">
        <x-ui-button variant="secondary-outline" size="lg" icon-only @click="$dispatch('open-modal-terminal')">
            <x-heroicon-o-command-line />
        </x-ui-button>

        {{-- Badge zeigt ungelesene Notices --}}
        @if($unreadCount > 0)
            <span 
                class="position-absolute top--1 right--1 bg-danger text-on-danger rounded-full text-xs px-2 py-1 shadow-md"
                style="transform: translate(50%, -50%);"
            >
                {{ $unreadCount }}
            </span>
        @endif
    </div>
    @endif

    {{-- Toast Stack --}}
    <div class="position-fixed @if(config('notifications.show_modal')) bottom-20 @else bottom-4 @endif right-4 z-40">
        <div class="d-flex flex-col items-end gap-2">
            @foreach($activeNotices as $notice)
                <livewire:notifications.notices.toast 
                    :notice="$notice" 
                    :key="'toast-'.$notice->uuid"
                />
            @endforeach
        </div>
    </div>
</div>