<?php

namespace App\Enums;

enum StockAdjustmentReasonEnum: int
{
    case Damage       = 1;
    case Lost         = 2;
    case Expired      = 3;
    case Correction   = 4;
    case OpeningStock = 5;
    case Other        = 6;

    public function label(): string
    {
        return match($this) {
            self::Damage       => 'Damage',
            self::Lost         => 'Lost',
            self::Expired      => 'Expired',
            self::Correction   => 'Correction',
            self::OpeningStock => 'Opening Stock',
            self::Other        => 'Other',
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
