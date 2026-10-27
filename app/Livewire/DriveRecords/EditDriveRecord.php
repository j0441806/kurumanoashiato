<?php

namespace App\Livewire\DriveRecords;

use App\Enums\RoadType;
use App\Models\Car;
use App\Models\DriveRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('ドライブ記録編集')]
class EditDriveRecord extends Component
{
    #[Locked]
    public int $driveRecordId;

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

    public function mount(DriveRecord $driveRecord): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $driveRecord = $this->ownedDriveRecord($driveRecord->getKey(), $user);
        $roadTypes = $driveRecord->getAttribute('road_types');

        $this->driveRecordId = $driveRecord->getKey();
        $this->driveDate = (string) $driveRecord->getRawOriginal('drive_date');
        $this->carId = $driveRecord->car_id;
        $this->departure = $driveRecord->departure;
        $this->destination = $driveRecord->destination;
        $this->spots = (string) ($driveRecord->spots ?? '');
        $this->roadTypes = $roadTypes instanceof SupportCollection
            ? $roadTypes->map(fn (RoadType $roadType): string => $roadType->value)->all()
            : [];
        $this->distance = (string) $driveRecord->distance;
        $this->noteTheme = (string) ($driveRecord->note_theme ?? '');
        $this->noteGoodPoints = (string) ($driveRecord->note_good_points ?? '');
        $this->noteIssues = (string) ($driveRecord->note_issues ?? '');
        $this->noteNextAction = (string) ($driveRecord->note_next_action ?? '');
        $this->noteFree = (string) ($driveRecord->note_free ?? '');
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
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $driveRecord = $this->ownedDriveRecord($this->driveRecordId, $user);
        $validated = $this->validate();
        $car = $user->cars()->whereKey((int) $validated['carId'])->firstOrFail();
        $roadTypes = array_map(RoadType::from(...), $this->roadTypes);

        $driveRecord->car()->associate($car);
        $driveRecord->fill([
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
        $driveRecord->save();

        $this->redirectRoute('drive-records.show', $driveRecord, absolute: false, navigate: true);
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
                    fn (QueryBuilder $query) => $query
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

    private function ownedDriveRecord(int $driveRecordId, User $user): DriveRecord
    {
        return DriveRecord::query()
            ->whereKey($driveRecordId)
            ->whereHas('car', fn (EloquentBuilder $query) => $query->where('user_id', $user->id))
            ->firstOrFail();
    }
}
