<?php

namespace App\Enums;

/**
 * ドライブで走行した道路の種別を表す。
 */
enum RoadType: string
{
    case GeneralUrban = 'general_urban';
    case GeneralResidential = 'general_residential';
    case Expressway = 'expressway';
    case MetropolitanExpressway = 'metropolitan_expressway';
    case Winding = 'winding';
    case RoughRoad = 'rough_road';

    public function label(): string
    {
        return match ($this) {
            self::GeneralUrban => '一般道（市街地）',
            self::GeneralResidential => '一般道（生活道路）',
            self::Expressway => '高速道路',
            self::MetropolitanExpressway => '首都高',
            self::Winding => 'ワインディング',
            self::RoughRoad => '酷道',
        };
    }
}
