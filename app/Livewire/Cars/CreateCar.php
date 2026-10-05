<?php

namespace App\Livewire\Cars;

use App\Enums\CarCategory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('車両登録')]
class CreateCar extends Component
{
    public string $maker = '';

    public string $model = '';

    public string $grade = '';

    public ?int $category = null;

    public string $station = '';

    public string $memo = '';

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

        $user->cars()->create($validated);

        $this->redirectRoute('cars.index', absolute: false, navigate: true);
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
        ];
    }
}
