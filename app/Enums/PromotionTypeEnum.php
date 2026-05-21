<?php
namespace App\Enums;

enum PromotionTypeEnum: int
{
    case ProductOffer    = 1;
    case CategoryOffer   = 2;
    case BrandOffer      = 3;
    case Coupon          = 4;
    case BuyXGetY        = 5;
    case ComboOffer      = 6;
    case FreeItem        = 7;
    case BillOffer       = 8;
    case CustomerOffer   = 9;
    case PaymentModeOffer = 10;
    case Loyalty         = 11;

    public function label(): string
    {
        return match($this) {
            self::ProductOffer    => 'Product Offer',
            self::CategoryOffer   => 'Category Offer',
            self::BrandOffer      => 'Brand Offer',
            self::Coupon          => 'Coupon Code',
            self::BuyXGetY        => 'Buy X Get Y',
            self::ComboOffer      => 'Combo Offer',
            self::FreeItem        => 'Free Item Offer',
            self::BillOffer       => 'Bill Offer',
            self::CustomerOffer   => 'Customer Offer',
            self::PaymentModeOffer => 'Payment Mode Offer',
            self::Loyalty         => 'Loyalty Discount',
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

    public function isItemLevel(): bool
    {
        return in_array($this, [
            self::ProductOffer,
            self::CategoryOffer,
            self::BrandOffer,
            self::BuyXGetY,
            self::ComboOffer,
            self::FreeItem,
        ]);
    }

    public function isInvoiceLevel(): bool
    {
        return !$this->isItemLevel();
    }
}
