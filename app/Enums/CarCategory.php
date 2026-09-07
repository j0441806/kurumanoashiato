<?php

namespace App\Enums;

/**
 * 車両の利用形態を表す。
 */
enum CarCategory: int
{
    case Owned = 1;
    case Rental = 2;
    case CarShare = 3;

    public function label(): string
    {
        return match ($this) {
            self::Owned => 'マイカー',
            self::Rental => 'レンタカー',
            self::CarShare => 'カーシェア',
        };
    }
}
