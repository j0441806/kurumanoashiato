<?php

namespace App\Livewire\DriveRecords;

use App\Models\DriveRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('ドライブ記録')]
class IndexDriveRecords extends Component
{
    /** @return Collection<int, DriveRecord> */
    #[Computed]
    public function driveRecords(): Collection
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return DriveRecord::query()
            ->whereHas('car', fn (Builder $query) => $query->where('user_id', $user->id))
            ->with('car')
            ->orderByDesc('drive_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }
}
