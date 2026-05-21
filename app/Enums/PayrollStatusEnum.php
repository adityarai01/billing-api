<?php

namespace App\Enums;

enum PayrollStatusEnum: int
{
    case Draft     = 1;
    case Generated = 2;
    case Approved  = 3;
    case Paid      = 4;

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Draft',
            self::Generated => 'Generated',
            self::Approved  => 'Approved',
            self::Paid      => 'Paid',
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
