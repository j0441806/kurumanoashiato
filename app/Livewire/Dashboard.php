<?php

namespace App\Livewire;

use App\Models\DriveRecord;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('ダッシュボード')]
class Dashboard extends Component
{
    public ?DriveRecord $latestDriveRecord = null;

    public int $carCount = 0;

    public ?Photo $latestCarPhoto = null;

    public ?Photo $latestDriveRecordPhoto = null;

    public function mount(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $this->latestDriveRecord = DriveRecord::query()
            ->whereHas('car', fn (Builder $query) => $query->where('user_id', $user->id))
            ->with('car')
            ->orderByDesc('drive_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $this->carCount = $user->cars()->count();

        $this->latestCarPhoto = Photo::query()
            ->whereHas('car', fn (Builder $query) => $query->where('user_id', $user->id))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $this->latestDriveRecordPhoto = Photo::query()
            ->whereHas('driveRecord.car', fn (Builder $query) => $query->where('user_id', $user->id))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }
}
