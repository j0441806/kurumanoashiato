<x-layouts::app :title="__('Cars')">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Cars') }}</flux:heading>

        <flux:button :href="route('cars.create')" variant="primary" wire:navigate>
            車両を登録
        </flux:button>
    </div>
</x-layouts::app>
