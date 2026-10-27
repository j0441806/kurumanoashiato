<section class="w-full max-w-5xl">
    <div class="mb-6">
        <flux:heading size="xl">ドライブ記録登録</flux:heading>
        <flux:subheading>ドライブの記録を入力してください。</flux:subheading>
    </div>

    @if ($this->cars->isEmpty())
        <div class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">ドライブ記録を登録するには、先に車両を登録してください。</flux:heading>

            <flux:button class="mt-4" :href="route('cars.create')" variant="primary" wire:navigate>
                車両を登録
            </flux:button>
        </div>
    @else
        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="driveDate" label="日付" type="date" :max="today()->toDateString()" required />

            <flux:select wire:model.number="carId" label="車両" required>
                <flux:select.option value="">選択してください</flux:select.option>

                @foreach ($this->cars as $car)
                    <flux:select.option :value="$car->id">
                        {{ $car->maker }} / {{ $car->model }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="departure" label="出発地" type="text" required />

            <flux:input wire:model="destination" label="目的地" type="text" required />

            <flux:input wire:model="spots" label="立ち寄りポイント" type="text" />

            <flux:checkbox.group wire:model="roadTypes" label="道路の種類">
                @foreach ($this->roadTypeOptions as $roadType)
                    <flux:checkbox :value="$roadType->value" :label="$roadType->label()" />
                @endforeach
            </flux:checkbox.group>

            <flux:input wire:model="distance" label="距離" type="text" inputmode="decimal" suffix="km" required />

            <flux:textarea wire:model="noteTheme" label="今回のテーマ" rows="4" />

            <flux:textarea wire:model="noteGoodPoints" label="良かったこと" rows="4" />

            <flux:textarea wire:model="noteIssues" label="課題" rows="4" />

            <flux:textarea wire:model="noteNextAction" label="次回やること" rows="4" />

            <flux:textarea wire:model="noteFree" label="メモ" rows="6" />

            <div class="flex items-center gap-3">
                <flux:button variant="primary" type="submit">登録する</flux:button>
                <flux:button :href="route('drive-records.index')" variant="ghost" wire:navigate>キャンセル</flux:button>
            </div>
        </form>
    @endif
</section>
