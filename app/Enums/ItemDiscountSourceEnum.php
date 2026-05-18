<?php
namespace App\Enums;

enum ItemDiscountSourceEnum: int
{
    case Manual       = 1;
    case ProductOffer = 2;
    case CategoryOffer = 3;
    case BrandOffer   = 4;
    case BuyXGetY     = 5;
    case ComboOffer   = 6;

    public function label(): string
    {
        return match($this) {
            self::Manual        => 'Manual',
            self::ProductOffer  => 'Product Offer',
            self::CategoryOffer => 'Category Offer',
            self::BrandOffer    => 'Brand Offer',
            self::BuyXGetY      => 'Buy X Get Y',
            self::ComboOffer    => 'Combo Offer',
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
