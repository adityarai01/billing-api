<?php
namespace App\Enums;

enum NotificationTypeEnum: int
{
    case LowStock        = 1;
    case OutOfStock      = 2;
    case NearExpiry      = 3;
    case ExpiredStock    = 4;
    case CustomerDue     = 5;
    case SupplierDue     = 6;
    case CashMismatch    = 7;
    case OfferExpiry     = 8;
    case PayrollDue      = 9;
    case SaleCancelled   = 10;
    case StockAdjustment = 11;
    case PurchaseDue     = 12;
    case SystemAlert     = 13;

    public function label(): string
    {
        return match($this) {
            self::LowStock        => 'Low Stock',
            self::OutOfStock      => 'Out of Stock',
            self::NearExpiry      => 'Near Expiry',
            self::ExpiredStock    => 'Expired Stock',
            self::CustomerDue     => 'Customer Due',
            self::SupplierDue     => 'Supplier Due',
            self::CashMismatch    => 'Cash Mismatch',
            self::OfferExpiry     => 'Offer Expiry',
            self::PayrollDue      => 'Payroll Due',
            self::SaleCancelled   => 'Sale Cancelled',
            self::StockAdjustment => 'Stock Adjustment',
            self::PurchaseDue     => 'Purchase Due',
            self::SystemAlert     => 'System Alert',
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
