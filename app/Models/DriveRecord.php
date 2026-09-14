<?php

namespace App\Models;

use App\Enums\RoadType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'drive_date', 'departure', 'destination', 'spots', 'road_types', 'distance',
    'note_theme', 'note_good_points', 'note_issues', 'note_next_action', 'note_free',
])]
class DriveRecord extends Model
{
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'drive_date' => 'date',
            'distance' => 'decimal:1',
            'road_types' => AsEnumCollection::of(RoadType::class),
        ];
    }

    /** @return BelongsTo<Car, $this> */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /** @return BelongsTo<Car, $this> */
    public function carWithTrashed(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'car_id')->withTrashed();
    }

    /** @return HasMany<Photo, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }
}
