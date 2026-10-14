<?php

namespace App\Livewire\Cars;

use App\Enums\CarCategory;
use App\Models\Car;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('車両編集')]
class EditCar extends Component
{
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
}
