<section class="w-full max-w-2xl">
    <div class="mb-6">
        <flux:heading size="xl">車両登録</flux:heading>
        <flux:subheading>車両の情報を入力してください。</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:input wire:model="maker" label="メーカー" type="text" required autofocus />

        <flux:input wire:model="model" label="車種" type="text" required />

        <flux:input wire:model="grade" label="グレード" type="text" />

        <flux:select wire:model.live.number="category" label="車両区分" required>
            <flux:select.option value="">選択してください</flux:select.option>

            @foreach ($this->categories as $carCategory)
                <flux:select.option :value="$carCategory->value">
                    {{ $carCategory->label() }}
                </flux:select.option>
            @endforeach
        </flux:select>

        @if ($this->requiresStation)
            <flux:input wire:model="station" label="ステーション" type="text" required />
        @endif

        <flux:textarea wire:model="memo" label="メモ" rows="6" />

        <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700">
            <flux:heading size="lg">写真</flux:heading>
            <flux:subheading>車両の写真を5枚まで選択できます。</flux:subheading>
        </div>

        <div wire:key="photo-input-{{ $photoInputKey }}">
            <flux:input
                wire:model="photoUploads"
                label="写真を選択（任意）"
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

        @if ($photoUploads !== [])
            <div>
                <flux:button type="button" variant="ghost" wire:click="resetPhotoSelection">
                    選択をやり直す
                </flux:button>
            </div>
        @endif

        <flux:error name="save" />

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit">登録する</flux:button>
            <flux:button :href="route('cars.index')" variant="ghost" wire:navigate>キャンセル</flux:button>
        </div>
    </form>
</section>
