<section class="w-full max-w-2xl">
    <div class="mb-6">
        <flux:heading size="xl">車両編集</flux:heading>
        <flux:subheading>車両の情報を編集してください。</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:input wire:model="maker" label="メーカー" type="text" required autofocus />

        <flux:input wire:model="model" label="車種" type="text" required />

        <flux:input wire:model="grade" label="グレード" type="text" />

        <flux:select wire:model.live.number="category" label="車両区分" required>
            @foreach ($this->categories as $carCategory)
                <flux:select.option :value="$carCategory->value">
                    {{ $carCategory->label() }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:input
            wire:model="station"
            label="ステーション"
            type="text"
            :required="$this->requiresStation"
            :disabled="$this->isOwned"
        />

        @if ($this->willDeleteStation)
            <flux:callout
                variant="warning"
                icon="exclamation-triangle"
                heading="保存すると、登録済みのステーション情報は削除されます。"
            />
        @endif

        <flux:textarea wire:model="memo" label="メモ" rows="6" />

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">保存する</flux:button>
            <flux:button :href="route('cars.show', $carId)" variant="ghost" wire:navigate>キャンセル</flux:button>
        </div>
    </form>
</section>
