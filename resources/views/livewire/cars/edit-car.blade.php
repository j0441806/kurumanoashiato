<section class="w-full max-w-5xl">
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

    <div class="mt-12 border-t border-zinc-200 pt-8 dark:border-zinc-700">
        <flux:heading size="lg">写真</flux:heading>
        <flux:subheading>車両の写真を一度に5枚まで追加できます。</flux:subheading>

        <form wire:submit="addPhotos" class="mt-6 space-y-6">
            <div wire:key="photo-input-{{ $photoInputKey }}">
                <flux:input
                    wire:model="photoUploads"
                    label="写真を選択"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                />
            </div>

            @foreach ($photoUploads as $index => $photoUpload)
                <div
                    class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"
                    wire:key="photo-note-{{ $photoUploadKeys[$index] ?? $index }}"
                >
                    <p class="mb-3 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ $photoUpload->getClientOriginalName() }}
                    </p>

                    <flux:textarea
                        wire:model="photoNotes.{{ $index }}"
                        label="写真のメモ（任意）"
                        rows="3"
                        maxlength="1000"
                    />
                </div>
            @endforeach

            <div class="flex flex-wrap items-center gap-3">
                <flux:button variant="primary" type="submit">写真を追加</flux:button>

                @if ($photoUploads !== [])
                    <flux:button type="button" variant="ghost" wire:click="resetPhotoSelection">
                        選択をやり直す
                    </flux:button>
                @endif
            </div>
        </form>

        <div class="mt-10">
            <flux:heading size="lg" class="mb-4">登録済み写真</flux:heading>

            @if ($this->carPhotos->isEmpty())
                <p class="text-sm text-zinc-500 dark:text-zinc-400">まだ写真が登録されていません。</p>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($this->carPhotos as $photo)
                        <figure class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                            <img
                                class="aspect-video w-full object-cover"
                                src="{{ route('cars.photos.show', [$carId, $photo]) }}"
                                alt="車両写真"
                                loading="lazy"
                            />

                            <figcaption class="p-4 text-sm whitespace-pre-wrap text-zinc-700 dark:text-zinc-200">
                                {{ $photo->note ?: 'メモなし' }}
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>
