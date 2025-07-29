<x-ui-modal size="md" wire:model="modalShow">
    <x-slot name="header">
        Terminal
    </x-slot>

    
    <div class="grid grid-cols-2 gap-4">
        <!-- Name -->
    </div>
    
    <x-slot name="footer">
        <x-ui-button variant="success" wire:click="closeModal">Schließen</x-ui-button>
    </x-slot>
</x-ui-modal>