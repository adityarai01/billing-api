<?php
namespace App\Enums;

enum InvoiceDiscountSourceEnum: int
{
    case ManualInvoiceDiscount = 1;
    case Coupon                = 2;
    case BillOffer             = 3;
    case CustomerOffer         = 4;
    case PaymentModeOffer      = 5;
    case Loyalty               = 6;

    public function label(): string
    {
        return match($this) {
            self::ManualInvoiceDiscount => 'Manual Invoice Discount',
            self::Coupon                => 'Coupon',
            self::BillOffer             => 'Bill Offer',
            self::CustomerOffer         => 'Customer Offer',
            self::PaymentModeOffer      => 'Payment Mode Offer',
            self::Loyalty               => 'Loyalty',
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
