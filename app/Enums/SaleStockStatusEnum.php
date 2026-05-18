<?php
namespace App\Enums;

enum SaleStockStatusEnum: int
{
    case StockDeducted = 1;
    case NotDeducted   = 2;
    case Reversed      = 3;

    public function label(): string
    {
        return match($this) {
            self::StockDeducted => 'Stock Deducted',
            self::NotDeducted   => 'Not Deducted',
            self::Reversed      => 'Reversed',
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
