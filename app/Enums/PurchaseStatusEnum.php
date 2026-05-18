<?php

namespace App\Enums;

enum PurchaseStatusEnum: int
{
    case Draft     = 1;
    case Completed = 2;
    case Cancelled = 3;

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Draft',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn(self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
