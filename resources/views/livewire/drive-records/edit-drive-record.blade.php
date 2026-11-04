<section class="w-full max-w-5xl">
    <div class="mb-6">
        <flux:heading size="xl">ドライブ記録編集</flux:heading>
        <flux:subheading>ドライブの記録を編集してください。</flux:subheading>
    </div>

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
            <flux:button variant="primary" type="submit">保存する</flux:button>
            <flux:button :href="route('drive-records.show', $driveRecordId)" variant="ghost" wire:navigate>キャンセル</flux:button>
        </div>
    </form>

    <div class="mt-12 border-t border-zinc-200 pt-8 dark:border-zinc-700">
        <flux:heading size="lg">写真</flux:heading>
        <flux:subheading>ドライブの写真を一度に5枚まで追加できます。</flux:subheading>

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

            @if ($this->driveRecordPhotos->isEmpty())
                <p class="text-sm text-zinc-500 dark:text-zinc-400">まだ写真が登録されていません。</p>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($this->driveRecordPhotos as $photo)
                        <figure class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                            <img
                                class="aspect-video w-full object-cover"
                                src="{{ route('drive-records.photos.show', [$driveRecordId, $photo]) }}"
                                alt="ドライブ写真"
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
