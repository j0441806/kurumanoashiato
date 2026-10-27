<section class="w-full max-w-5xl">
    <div class="mb-6 flex items-center justify-between gap-4">
        <flux:heading size="xl">ドライブ記録詳細</flux:heading>

        <flux:button :href="route('drive-records.edit', $driveRecord)" variant="primary" wire:navigate>
            編集
        </flux:button>
    </div>

    <div class="space-y-12">
        <div>
            <flux:heading size="lg" class="mb-5">ドライブ情報</flux:heading>

            <dl class="grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">日付</dt>
                    <dd class="mt-2 text-zinc-900 dark:text-zinc-100">{{ $driveRecord->drive_date->format('Y-m-d') }}</dd>
                </div>
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">車両</dt>
                    <dd class="mt-2 text-zinc-900 dark:text-zinc-100">{{ $driveRecord->car->maker }} / {{ $driveRecord->car->model }}</dd>
                </div>
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">出発地</dt>
                    <dd class="mt-2 text-zinc-900 dark:text-zinc-100">{{ $driveRecord->departure }}</dd>
                </div>
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">目的地</dt>
                    <dd class="mt-2 text-zinc-900 dark:text-zinc-100">{{ $driveRecord->destination }}</dd>
                </div>
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">立ち寄りポイント</dt>
                    <dd class="mt-2 text-zinc-900 dark:text-zinc-100">{{ $driveRecord->spots ?: '未記入' }}</dd>
                </div>
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">道路の種類</dt>
                    <dd class="mt-2 flex flex-wrap gap-2 text-zinc-900 dark:text-zinc-100">
                        @forelse ($driveRecord->road_types ?? [] as $roadType)
                            <flux:badge size="sm">{{ $roadType->label() }}</flux:badge>
                        @empty
                            未設定
                        @endforelse
                    </dd>
                </div>
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">距離</dt>
                    <dd class="mt-2 text-zinc-900 dark:text-zinc-100">
                        {{ str_ends_with($driveRecord->distance, '.0') ? substr($driveRecord->distance, 0, -2) : $driveRecord->distance }} km
                    </dd>
                </div>
            </dl>
        </div>

        <div>
            <flux:heading size="lg" class="mb-5">振り返り</flux:heading>

            <dl class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                <div class="grid gap-1 p-4 sm:grid-cols-4 sm:gap-4">
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">今回のテーマ</dt>
                    <dd class="whitespace-pre-wrap text-zinc-900 sm:col-span-3 dark:text-zinc-100">{{ $driveRecord->note_theme ?: '未記入' }}</dd>
                </div>
                <div class="grid gap-1 p-4 sm:grid-cols-4 sm:gap-4">
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">良かったこと</dt>
                    <dd class="whitespace-pre-wrap text-zinc-900 sm:col-span-3 dark:text-zinc-100">{{ $driveRecord->note_good_points ?: '未記入' }}</dd>
                </div>
                <div class="grid gap-1 p-4 sm:grid-cols-4 sm:gap-4">
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">課題</dt>
                    <dd class="whitespace-pre-wrap text-zinc-900 sm:col-span-3 dark:text-zinc-100">{{ $driveRecord->note_issues ?: '未記入' }}</dd>
                </div>
                <div class="grid gap-1 p-4 sm:grid-cols-4 sm:gap-4">
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">次回やること</dt>
                    <dd class="whitespace-pre-wrap text-zinc-900 sm:col-span-3 dark:text-zinc-100">{{ $driveRecord->note_next_action ?: '未記入' }}</dd>
                </div>
                <div class="grid gap-1 p-4 sm:grid-cols-4 sm:gap-4">
                    <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">メモ</dt>
                    <dd class="whitespace-pre-wrap text-zinc-900 sm:col-span-3 dark:text-zinc-100">{{ $driveRecord->note_free ?: '未記入' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="mt-6">
        <flux:button :href="route('drive-records.index')" variant="ghost" wire:navigate>ドライブ記録一覧へ戻る</flux:button>
    </div>
</section>
