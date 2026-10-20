<section class="w-full">
    <div class="mb-6 flex items-center justify-between gap-4">
        <flux:heading size="xl">ドライブ記録</flux:heading>

        <flux:button :href="route('drive-records.create')" variant="primary" wire:navigate>
            ドライブ記録を登録
        </flux:button>
    </div>

    @if ($this->driveRecords->isEmpty())
        <div class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">まだドライブ記録が登録されていません。</flux:heading>
        </div>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>日付</flux:table.column>
                <flux:table.column>車両</flux:table.column>
                <flux:table.column>出発地</flux:table.column>
                <flux:table.column>目的地</flux:table.column>
                <flux:table.column>道路の種類</flux:table.column>
                <flux:table.column>距離</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->driveRecords as $driveRecord)
                    <flux:table.row :key="$driveRecord->id">
                        <flux:table.cell>{{ $driveRecord->drive_date->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell variant="strong">
                            {{ $driveRecord->car->maker }} / {{ $driveRecord->car->model }}
                        </flux:table.cell>
                        <flux:table.cell>{{ $driveRecord->departure }}</flux:table.cell>
                        <flux:table.cell>{{ $driveRecord->destination }}</flux:table.cell>
                        <flux:table.cell>
                            @forelse ($driveRecord->road_types ?? [] as $roadType)
                                <flux:badge size="sm">{{ $roadType->label() }}</flux:badge>
                            @empty
                                未設定
                            @endforelse
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ str_ends_with($driveRecord->distance, '.0') ? substr($driveRecord->distance, 0, -2) : $driveRecord->distance }} km
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</section>
