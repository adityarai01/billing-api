<?php

namespace App\Enums;

enum StockTransactionTypeEnum: int
{
    case OpeningStock    = 1;
    case Purchase        = 2;
    case Sale            = 3;
    case SaleReturn      = 4;
    case PurchaseReturn  = 5;
    case StockAdjustment = 6;
    case Damage          = 7;
    case Expired         = 8;

    public function label(): string
    {
        return match($this) {
            self::OpeningStock    => 'Opening Stock',
            self::Purchase        => 'Purchase',
            self::Sale            => 'Sale',
            self::SaleReturn      => 'Sale Return',
            self::PurchaseReturn  => 'Purchase Return',
            self::StockAdjustment => 'Stock Adjustment',
            self::Damage          => 'Damage',
            self::Expired         => 'Expired',
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
