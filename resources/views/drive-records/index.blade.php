<x-layouts::app :title="__('Drive records')">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">{{ __('Drive records') }}</flux:heading>

        <flux:button :href="route('drive-records.create')" variant="primary" wire:navigate>
            ドライブ記録を登録
        </flux:button>
    </div>
</x-layouts::app>
