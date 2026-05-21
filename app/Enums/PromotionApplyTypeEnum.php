<?php
namespace App\Enums;

enum PromotionApplyTypeEnum: int
{
    case AutoApply     = 1;
    case ManualApply   = 2;
    case CouponRequired = 3;

    public function label(): string
    {
        return match($this) {
            self::AutoApply     => 'Auto Apply',
            self::ManualApply   => 'Manual Apply',
            self::CouponRequired => 'Coupon Required',
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
