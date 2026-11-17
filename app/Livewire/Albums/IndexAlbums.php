<?php

namespace App\Livewire\Albums;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('アルバム一覧')]
class IndexAlbums extends Component
{
    /** @return Collection<int, Photo> */
    #[Computed]
    public function photos(): Collection
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return Photo::query()
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->whereHas('car', fn (Builder $query) => $query->where('user_id', $user->id))
                    ->orWhereHas(
                        'driveRecord.car',
                        fn (Builder $query) => $query->where('user_id', $user->id),
                    );
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }
}
