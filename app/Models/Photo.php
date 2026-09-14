<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['file_path', 'note'])]
class Photo extends Model
{
    use HasFactory;

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

    /** @return BelongsTo<DriveRecord, $this> */
    public function driveRecord(): BelongsTo
    {
        return $this->belongsTo(DriveRecord::class);
    }
}
