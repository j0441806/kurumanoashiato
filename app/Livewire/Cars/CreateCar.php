<?php

namespace App\Livewire\Cars;

use App\Actions\Cars\CreateCarWithPhotos;
use App\Enums\CarCategory;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

#[Title('車両登録')]
class CreateCar extends Component
{
    use WithFileUploads;

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

    /** @return array<CarCategory> */
    #[Computed]
    public function categories(): array
    {
        return CarCategory::cases();
    }

    #[Computed]
    public function requiresStation(): bool
    {
        return in_array($this->category, [
            CarCategory::Rental->value,
            CarCategory::CarShare->value,
        ], true);
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

    public function save(CreateCarWithPhotos $createCarWithPhotos): void
    {
        $validated = $this->validate();
        $category = CarCategory::from((int) $validated['category']);

        $validated['category'] = $category;
        $validated['station'] = $category === CarCategory::Owned
            ? null
            : $validated['station'];

        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        try {
            $createCarWithPhotos->handle(
                $user,
                Arr::except($validated, ['photoUploads', 'photoNotes']),
                $this->photoUploads,
                $validated['photoNotes'] ?? [],
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('save', '車両の登録に失敗しました。時間をおいて再度お試しください。');

            return;
        }

        $this->redirectRoute('cars.index', absolute: false, navigate: true);
    }

    public function resetPhotoSelection(): void
    {
        $this->reset('photoUploads', 'photoNotes', 'photoUploadKeys');
        $this->photoInputKey++;
        $this->resetValidation([
            'photoUploads',
            'photoUploads.*',
            'photoNotes',
            'photoNotes.*',
            'save',
        ]);
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
                Rule::excludeIf($this->category === CarCategory::Owned->value),
                Rule::requiredIf($this->requiresStation()),
                'nullable',
                'string',
                'max:255',
            ],
            'memo' => ['nullable', 'string', 'max:10000'],
            'photoUploads' => ['array', 'max:5'],
            'photoUploads.*' => [File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(5120)],
            'photoNotes' => ['array', 'max:5'],
            'photoNotes.*' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
