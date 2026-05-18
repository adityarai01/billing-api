<?php

namespace App\Enums;

enum DebitNoteTypeEnum: int
{
    case PurchaseReturn    = 1;
    case ManualAdjustment  = 2;
    case RateDifference    = 3;
    case Shortage          = 4;

    public function label(): string
    {
        return match($this) {
            self::PurchaseReturn   => 'Purchase Return',
            self::ManualAdjustment => 'Manual Adjustment',
            self::RateDifference   => 'Rate Difference',
            self::Shortage         => 'Shortage',
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
