<?php

namespace App\Livewire\DriveRecords;

use App\Models\DriveRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('ドライブ記録詳細')]
class ShowDriveRecord extends Component
{
    public DriveRecord $driveRecord;

    public function mount(DriveRecord $driveRecord): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $this->driveRecord = DriveRecord::query()
            ->whereKey($driveRecord->getKey())
            ->whereHas('car', fn (Builder $query) => $query->where('user_id', $user->id))
            ->with('car')
            ->firstOrFail();
    }
}
