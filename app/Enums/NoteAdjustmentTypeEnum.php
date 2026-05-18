<?php

namespace App\Enums;

enum NoteAdjustmentTypeEnum: int
{
    case AgainstPurchase = 1;
    case DueAdjustment   = 2;
    case CashReceived    = 3;

    public function label(): string
    {
        return match($this) {
            self::AgainstPurchase => 'Against Purchase',
            self::DueAdjustment   => 'Due Adjustment',
            self::CashReceived    => 'Cash Received',
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
