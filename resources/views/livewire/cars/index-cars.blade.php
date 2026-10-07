<section class="w-full">
    <div class="mb-6 flex items-center justify-between gap-4">
        <flux:heading size="xl">車両</flux:heading>

        <flux:button :href="route('cars.create')" variant="primary" wire:navigate>
            車両を登録
        </flux:button>
    </div>

    @if ($this->cars->isEmpty())
        <div class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">まだ車両が登録されていません。</flux:heading>
            <flux:text class="mt-2">車両を登録して、ドライブの記録を始めましょう。</flux:text>
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>車両</flux:table.column>
                <flux:table.column>車両区分</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->cars as $car)
                    <flux:table.row :key="$car->id">
                        <flux:table.cell variant="strong">
                            {{ $car->maker }} / {{ $car->model }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $car->category->label() }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</section>
