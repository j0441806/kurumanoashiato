<?php

namespace App\Livewire\Cars;

use App\Models\Car;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('車両')]
class IndexCars extends Component
{
    /** @return Collection<int, Car> */
    #[Computed]
    public function cars(): Collection
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user->cars()->latest()->get();
    }
}
