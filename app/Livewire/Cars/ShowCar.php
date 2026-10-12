<?php

namespace App\Livewire\Cars;

use App\Models\Car;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('車両詳細')]
class ShowCar extends Component
{
    public Car $car;

    public function mount(Car $car): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $this->car = $user->cars()->whereKey($car->getKey())->firstOrFail();
    }
}
