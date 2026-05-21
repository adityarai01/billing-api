<?php

namespace App\Enums;

enum SalaryComponentTypeEnum: int
{
    case Earning   = 1;
    case Deduction = 2;

    public function label(): string
    {
        return match($this) {
            self::Earning   => 'Earning',
            self::Deduction => 'Deduction',
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
