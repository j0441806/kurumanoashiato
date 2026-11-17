<?php

namespace App\Livewire\Albums;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('アルバム詳細')]
class ShowAlbum extends Component
{
    public Photo $photo;

    public function mount(Photo $photo): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $this->photo = Photo::query()
            ->whereKey($photo->getKey())
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->whereHas('car', fn (Builder $query) => $query->where('user_id', $user->id))
                    ->orWhereHas(
                        'driveRecord.car',
                        fn (Builder $query) => $query->where('user_id', $user->id),
                    );
            })
            ->firstOrFail();
    }
}
