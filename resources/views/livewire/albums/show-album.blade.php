<section class="w-full max-w-5xl">
    <div class="mb-6">
        <flux:heading size="xl">アルバム詳細</flux:heading>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <img
            class="mx-auto max-h-[70vh] max-w-full object-contain"
            src="{{ $photo->car_id !== null
                ? route('cars.photos.show', [$photo->car_id, $photo])
                : route('drive-records.photos.show', [$photo->drive_record_id, $photo]) }}"
            alt=""
        />
    </div>

    <dl class="mt-6 divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
        <div class="grid gap-1 p-4 sm:grid-cols-4 sm:gap-4">
            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">メモ</dt>
            <dd class="whitespace-pre-wrap text-zinc-900 sm:col-span-3 dark:text-zinc-100">{{ $photo->note ?: '未記入' }}</dd>
        </div>
        <div class="grid gap-1 p-4 sm:grid-cols-4 sm:gap-4">
            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">登録年月日</dt>
            <dd class="text-zinc-900 sm:col-span-3 dark:text-zinc-100">{{ $photo->created_at->format('Y-m-d') }}</dd>
        </div>
    </dl>

    <div class="mt-6">
        <flux:button :href="route('albums.index')" variant="ghost" wire:navigate>アルバム一覧へ戻る</flux:button>
    </div>
</section>
