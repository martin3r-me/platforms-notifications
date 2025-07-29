<div
    x-data="{
        show: true,
        timeout: null,
        startTimeout() {
            this.timeout = setTimeout(() => {
                this.show = false;
                @this.dispatch('toast-dismissed', { noticeId: {{ $notice->id }} });
            }, 10000);
        }
    }"
    x-init="startTimeout()"
    x-show="show"
    x-transition:enter="transition ease-out duration-400"
    x-transition:enter-start="opacity-0 translate-y-4 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 translate-y-4 scale-95"
    class="
        toast 
        @if($notice->notice_type === 'success') bg-success text-on-success
        @elseif($notice->notice_type === 'error' || $notice->notice_type === 'danger') bg-danger text-on-danger
        @elseif($notice->notice_type === 'warning') bg-warning text-on-warning
        @elseif($notice->notice_type === 'info') bg-info text-on-info
        @else bg-primary text-on-primary
        @endif
        rounded-md shadow-md p-4 min-w-80 w-80 d-flex flex-col gap-2 mb-2"
    style="will-change: transform, opacity;"
>
    <div class="d-flex justify-between items-center">
        <div>
            @if($notice->title)
                <strong>{{ $notice->title }}</strong>
            @endif
            @if($notice->message)
                <div>{{ $notice->message }}</div>
            @endif
        </div>
        <button
            @click="show = false; $dispatch('toast-dismissed',{ noticeId: {{ $notice->id }} });"
            class="ml-4 text-xl font-bold cursor-pointer opacity-60 hover:opacity-100"
            aria-label="Schließen"
        >&times;</button>
    </div>
    {{-- Progressbar --}}
    <div class="toast-progress-container position-relative w-full h-1 bg-black-20 rounded-full overflow-hidden mt-1">
        <div
            class="toast-progress position-absolute left-0 top-0 h-full bg-white opacity-60"
            x-init="$el.style.width = '100%'; $el.animate([{width:'100%'},{width:'0%'}], {duration:10000, fill:'forwards'});"
        ></div>
    </div>
    <style>
.toast:hover {
    filter: brightness(1.05);
    transform: translateX(-2px);
}
    </style>
</div>