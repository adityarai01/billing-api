<?php

namespace App\Enums;

enum SalaryCalculationTypeEnum: int
{
    case Fixed             = 1;
    case PercentageOfBasic = 2;
    case PerDay            = 3;

    public function label(): string
    {
        return match($this) {
            self::Fixed             => 'Fixed Amount',
            self::PercentageOfBasic => 'Percentage of Basic',
            self::PerDay            => 'Per Day',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(fn($e) => ['value' => $e->value, 'label' => $e->label()], self::cases());
    }
}
