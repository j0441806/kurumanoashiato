<?php

namespace App\Livewire\Cars;

use App\Actions\Cars\AddCarPhotos;
use App\Enums\CarCategory;
use App\Models\Car;
use App\Models\Photo;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

#[Title('車両編集')]
class EditCar extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $carId;

    #[Locked]
    public bool $hadPersistedStation;

    public string $maker = '';

    public string $model = '';

    public string $grade = '';

    public ?int $category = null;

    public string $station = '';

    public string $memo = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $photoUploads = [];

    /** @var array<int, string> */
    public array $photoNotes = [];

    /** @var array<int, string> */
    public array $photoUploadKeys = [];

    public int $photoInputKey = 0;

    public function mount(Car $car): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $car = $user->cars()->whereKey($car->getKey())->firstOrFail();

        $this->carId = $car->getKey();
        $this->hadPersistedStation = filled($car->station);
        $this->maker = (string) $car->maker;
        $this->model = (string) $car->model;
        $this->grade = (string) ($car->grade ?? '');
        $this->category = (int) $car->getRawOriginal('category');
        $this->station = (string) ($car->station ?? '');
        $this->memo = (string) ($car->memo ?? '');
    }

    /** @return array<CarCategory> */
    #[Computed]
    public function categories(): array
    {
        return CarCategory::cases();
    }

    #[Computed]
    public function isOwned(): bool
    {
        return $this->category === CarCategory::Owned->value;
    }

    #[Computed]
    public function requiresStation(): bool
    {
        return in_array($this->category, [
            CarCategory::Rental->value,
            CarCategory::CarShare->value,
        ], true);
    }

    #[Computed]
    public function willDeleteStation(): bool
    {
        return $this->isOwned() && $this->hadPersistedStation;
    }

    /** @return Collection<int, Photo> */
    #[Computed]
    public function carPhotos(): Collection
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $car = $user->cars()->whereKey($this->carId)->firstOrFail();

        return $car->photos()->orderBy('id')->get();
    }

    public function updatedPhotoUploads(): void
    {
        $notesByUploadKey = [];

        foreach ($this->photoUploadKeys as $index => $uploadKey) {
            $notesByUploadKey[$uploadKey] = $this->photoNotes[$index] ?? '';
        }

        $this->photoUploadKeys = [];
        $this->photoNotes = [];

        foreach ($this->photoUploads as $index => $upload) {
            $uploadKey = $upload->getFilename();
            $this->photoUploadKeys[$index] = $uploadKey;
            $this->photoNotes[$index] = $notesByUploadKey[$uploadKey] ?? '';
        }
    }

    public function save(): void
    {
        $validated = $this->validate();
        $category = CarCategory::from((int) $validated['category']);

        $validated['category'] = $category;
        $validated['station'] = $category === CarCategory::Owned
            ? null
            : $validated['station'];

        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $car = $user->cars()->whereKey($this->carId)->firstOrFail();
        $car->update($validated);

        $this->redirectRoute('cars.show', $car, absolute: false, navigate: true);
    }

    public function addPhotos(AddCarPhotos $addCarPhotos): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $car = $user->cars()->whereKey($this->carId)->firstOrFail();
        $validated = $this->validate($this->photoRules());

        try {
            $addCarPhotos->handle($car, $this->photoUploads, $validated['photoNotes'] ?? []);
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('photoUploads', '写真の追加に失敗しました。時間をおいて再度お試しください。');

            return;
        }

        $this->clearPhotoSelection();
        unset($this->carPhotos);

        Flux::toast(variant: 'success', text: '写真を追加しました。');
    }

    public function resetPhotoSelection(): void
    {
        $this->clearPhotoSelection();
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'maker' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'grade' => ['nullable', 'string', 'max:100'],
            'category' => ['required', 'integer', Rule::enum(CarCategory::class)],
            'station' => [
                Rule::excludeIf($this->isOwned()),
                Rule::requiredIf($this->requiresStation()),
                'nullable',
                'string',
                'max:255',
            ],
            'memo' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /** @return array<string, mixed> */
    private function photoRules(): array
    {
        return [
            'photoUploads' => ['required', 'array', 'min:1', 'max:5'],
            'photoUploads.*' => [File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(5120)],
            'photoNotes' => ['array', 'max:5'],
            'photoNotes.*' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function clearPhotoSelection(): void
    {
        $this->reset('photoUploads', 'photoNotes', 'photoUploadKeys');
        $this->photoInputKey++;
        $this->resetValidation([
            'photoUploads',
            'photoUploads.*',
            'photoNotes',
            'photoNotes.*',
        ]);
    }
}
