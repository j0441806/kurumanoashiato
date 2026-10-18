<?php

namespace App\Livewire\DriveRecords;

use App\Enums\RoadType;
use App\Models\Car;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('ドライブ記録登録')]
class CreateDriveRecord extends Component
{
    public string $driveDate = '';

    public ?int $carId = null;

    public string $departure = '';

    public string $destination = '';

    public string $spots = '';

    /** @var array<int, string> */
    public array $roadTypes = [];

    public string $distance = '';

    public string $noteTheme = '';

    public string $noteGoodPoints = '';

    public string $noteIssues = '';

    public string $noteNextAction = '';

    public string $noteFree = '';

    public function mount(): void
    {
        $this->driveDate = today()->toDateString();
    }

    /** @return Collection<int, Car> */
    #[Computed]
    public function cars(): Collection
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user->cars()->latest()->get();
    }

    /** @return array<RoadType> */
    #[Computed]
    public function roadTypeOptions(): array
    {
        return RoadType::cases();
    }

    public function save(): void
    {
        $validated = $this->validate();
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $car = $user->cars()->whereKey((int) $validated['carId'])->firstOrFail();
        $roadTypes = array_map(RoadType::from(...), $this->roadTypes);

        $car->driveRecords()->create([
            'drive_date' => $validated['driveDate'],
            'departure' => $validated['departure'],
            'destination' => $validated['destination'],
            'spots' => filled($validated['spots']) ? $validated['spots'] : null,
            'road_types' => $roadTypes === [] ? null : $roadTypes,
            'distance' => $validated['distance'],
            'note_theme' => filled($validated['noteTheme']) ? $validated['noteTheme'] : null,
            'note_good_points' => filled($validated['noteGoodPoints']) ? $validated['noteGoodPoints'] : null,
            'note_issues' => filled($validated['noteIssues']) ? $validated['noteIssues'] : null,
            'note_next_action' => filled($validated['noteNextAction']) ? $validated['noteNextAction'] : null,
            'note_free' => filled($validated['noteFree']) ? $validated['noteFree'] : null,
        ]);

        $this->redirectRoute('drive-records.index', absolute: false, navigate: true);
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return [
            'driveDate' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'carId' => [
                'required',
                'integer',
                Rule::exists('cars', 'id')->where(
                    fn (Builder $query) => $query
                        ->where('user_id', $user->id)
                        ->whereNull('deleted_at'),
                ),
            ],
            'departure' => ['required', 'string', 'max:100'],
            'destination' => ['required', 'string', 'max:100'],
            'spots' => ['nullable', 'string', 'max:255'],
            'roadTypes' => ['nullable', 'array'],
            'roadTypes.*' => ['string', 'distinct', Rule::enum(RoadType::class)],
            'distance' => ['required', 'decimal:0,1', 'gt:0', 'max:999999.9'],
            'noteTheme' => ['nullable', 'string', 'max:10000'],
            'noteGoodPoints' => ['nullable', 'string', 'max:10000'],
            'noteIssues' => ['nullable', 'string', 'max:10000'],
            'noteNextAction' => ['nullable', 'string', 'max:10000'],
            'noteFree' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
