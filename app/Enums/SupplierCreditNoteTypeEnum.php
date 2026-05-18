<?php

namespace App\Enums;

enum SupplierCreditNoteTypeEnum: int
{
    case SchemeDiscount   = 1;
    case RateDifference   = 2;
    case Shortage         = 3;
    case DamageClaim      = 4;
    case ManualAdjustment = 5;

    public function label(): string
    {
        return match($this) {
            self::SchemeDiscount   => 'Scheme Discount',
            self::RateDifference   => 'Rate Difference',
            self::Shortage         => 'Shortage',
            self::DamageClaim      => 'Damage Claim',
            self::ManualAdjustment => 'Manual Adjustment',
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
