<section class="w-full max-w-2xl">
    <div class="mb-6 flex items-center justify-between gap-4">
        <flux:heading size="xl">車両詳細</flux:heading>

        <flux:button :href="route('cars.edit', $car)" variant="primary" wire:navigate>
            編集
        </flux:button>
    </div>

    <dl class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
        <div class="grid gap-1 p-4 sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">メーカー</dt>
            <dd class="text-zinc-900 sm:col-span-2 dark:text-zinc-100">{{ $car->maker }}</dd>
        </div>
        <div class="grid gap-1 p-4 sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">車種</dt>
            <dd class="text-zinc-900 sm:col-span-2 dark:text-zinc-100">{{ $car->model }}</dd>
        </div>
        <div class="grid gap-1 p-4 sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">グレード</dt>
            <dd class="text-zinc-900 sm:col-span-2 dark:text-zinc-100">{{ $car->grade ?: '-' }}</dd>
        </div>
        <div class="grid gap-1 p-4 sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">車両区分</dt>
            <dd class="text-zinc-900 sm:col-span-2 dark:text-zinc-100">{{ $car->category->label() }}</dd>
        </div>
        <div class="grid gap-1 p-4 sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">ステーション</dt>
            <dd class="text-zinc-900 sm:col-span-2 dark:text-zinc-100">{{ $car->station ?: '-' }}</dd>
        </div>
        <div class="grid gap-1 p-4 sm:grid-cols-3 sm:gap-4">
            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">メモ</dt>
            <dd class="whitespace-pre-wrap text-zinc-900 sm:col-span-2 dark:text-zinc-100">{{ $car->memo ?: '-' }}</dd>
        </div>
    </dl>

    <div class="mt-6">
        <flux:button :href="route('cars.index')" variant="ghost" wire:navigate>車両一覧へ戻る</flux:button>
    </div>
</section>
