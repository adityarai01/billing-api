<?php
namespace App\Enums;

enum PromotionTargetTypeEnum: int
{
    case Product     = 1;
    case ProductVariant = 2;
    case Category    = 3;
    case Brand       = 4;
    case Customer    = 5;
    case PaymentMode = 6;

    public function label(): string
    {
        return match($this) {
            self::Product        => 'Product',
            self::ProductVariant => 'Product Variant',
            self::Category       => 'Category',
            self::Brand          => 'Brand',
            self::Customer       => 'Customer',
            self::PaymentMode    => 'Payment Mode',
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
