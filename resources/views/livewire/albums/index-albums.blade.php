<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">アルバム一覧</flux:heading>
    </div>

    @if ($this->photos->isEmpty())
        <div class="rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">まだ写真が登録されていません。</flux:heading>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($this->photos as $photo)
                <a
                    class="relative block overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800"
                    href="{{ route('albums.show', $photo) }}"
                    aria-label="写真の詳細を表示"
                    wire:key="photo-{{ $photo->id }}"
                    wire:navigate
                >
                    <img
                        class="aspect-square w-full object-cover"
                        src="{{ $photo->car_id !== null
                            ? route('cars.photos.show', [$photo->car_id, $photo])
                            : route('drive-records.photos.show', [$photo->drive_record_id, $photo]) }}"
                        alt=""
                    />

                    <div class="absolute top-3 left-3">
                        @if ($photo->car_id !== null)
                            <span class="inline-flex items-center rounded-md bg-blue-800 px-2 py-1 text-sm font-medium whitespace-nowrap text-white">
                                車両写真
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-md bg-amber-800 px-2 py-1 text-sm font-medium whitespace-nowrap text-white">
                                ドライブ記録写真
                            </span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>
