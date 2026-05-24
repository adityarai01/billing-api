<?php
namespace App\Enums;

enum NotificationSourceTypeEnum: int
{
    case Product         = 1;
    case ProductVariant  = 2;
    case ProductBatch    = 3;
    case Sale            = 4;
    case SalesReturn     = 5;
    case Customer        = 6;
    case Supplier        = 7;
    case CashRegister    = 8;
    case Offer           = 9;
    case Payroll         = 10;
    case Purchase        = 11;
    case StockAdjustment = 12;
    case System          = 13;

    public function label(): string
    {
        return match($this) {
            self::Product         => 'Product',
            self::ProductVariant  => 'Product Variant',
            self::ProductBatch    => 'Product Batch',
            self::Sale            => 'Sale',
            self::SalesReturn     => 'Sales Return',
            self::Customer        => 'Customer',
            self::Supplier        => 'Supplier',
            self::CashRegister    => 'Cash Register',
            self::Offer           => 'Offer',
            self::Payroll         => 'Payroll',
            self::Purchase        => 'Purchase',
            self::StockAdjustment => 'Stock Adjustment',
            self::System          => 'System',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(fn($c) => ['value' => $c->value, 'label' => $c->label()], self::cases());
    }
}
