<section class="w-full max-w-5xl">
    <div class="mb-6">
        <flux:heading size="xl">ダッシュボード</flux:heading>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <flux:button :href="route('cars.index')" variant="primary" class="w-full" wire:navigate>
            車両を見る
        </flux:button>
        <flux:button :href="route('drive-records.index')" variant="primary" class="w-full" wire:navigate>
            ドライブ記録を見る
        </flux:button>
        <flux:button :href="route('albums.index')" variant="primary" class="w-full" wire:navigate>
            アルバムを見る
        </flux:button>
    </div>

    <div class="mt-8 grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <flux:heading size="lg">最近乗った車</flux:heading>

            <div class="mt-4">
                @if ($latestDriveRecord !== null)
                    <p class="text-zinc-900 dark:text-zinc-100">
                        {{ $latestDriveRecord->car->maker }} / {{ $latestDriveRecord->car->model }}
                    </p>
                @else
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">まだドライブ記録がありません。</p>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <flux:heading size="lg">最近のドライブ</flux:heading>

            <div class="mt-4">
                @if ($latestDriveRecord !== null)
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">日付</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">{{ $latestDriveRecord->drive_date->format('Y-m-d') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">出発地 / 目的地</dt>
                            <dd class="text-zinc-900 dark:text-zinc-100">
                                {{ $latestDriveRecord->departure }} / {{ $latestDriveRecord->destination }}
                            </dd>
                        </div>
                    </dl>
                @else
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">まだドライブ記録がありません。</p>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <flux:heading size="lg">登録車両数</flux:heading>
            <p class="mt-4 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $carCount }}台</p>
        </div>
    </div>

    <div class="mt-10">
        <flux:heading size="lg">最近撮った写真</flux:heading>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
                @if ($latestCarPhoto !== null)
                    <img
                        class="aspect-square w-full object-cover"
                        src="{{ route('cars.photos.show', [$latestCarPhoto->car_id, $latestCarPhoto]) }}"
                        alt="車両写真"
                        loading="lazy"
                    />
                @else
                    <div class="flex aspect-square items-center justify-center p-6 text-center text-zinc-500 dark:text-zinc-400">
                        まだ車両写真が登録されていません。
                    </div>
                @endif

                <span class="absolute top-3 left-3 inline-flex items-center rounded-md bg-blue-800 px-2 py-1 text-sm font-medium whitespace-nowrap text-white">
                    車両写真
                </span>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
                @if ($latestDriveRecordPhoto !== null)
                    <img
                        class="aspect-square w-full object-cover"
                        src="{{ route('drive-records.photos.show', [$latestDriveRecordPhoto->drive_record_id, $latestDriveRecordPhoto]) }}"
                        alt="ドライブ写真"
                        loading="lazy"
                    />
                @else
                    <div class="flex aspect-square items-center justify-center p-6 text-center text-zinc-500 dark:text-zinc-400">
                        まだドライブ記録写真が登録されていません。
                    </div>
                @endif

                <span class="absolute top-3 left-3 inline-flex items-center rounded-md bg-amber-800 px-2 py-1 text-sm font-medium whitespace-nowrap text-white">
                    ドライブ記録写真
                </span>
            </div>
        </div>
    </div>
</section>
