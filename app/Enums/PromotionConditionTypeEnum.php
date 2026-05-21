<?php
namespace App\Enums;

enum PromotionConditionTypeEnum: int
{
    case MinBillAmount = 1;
    case MinQty        = 2;
    case CustomerGroup = 3;
    case PaymentMode   = 4;
    case FirstPurchase = 5;
    case DateRange     = 6;
    case TimeRange     = 7;
    case DayOfWeek     = 8;

    public function label(): string
    {
        return match($this) {
            self::MinBillAmount => 'Minimum Bill Amount',
            self::MinQty        => 'Minimum Quantity',
            self::CustomerGroup => 'Customer Group',
            self::PaymentMode   => 'Payment Mode',
            self::FirstPurchase => 'First Purchase',
            self::DateRange     => 'Date Range',
            self::TimeRange     => 'Time Range',
            self::DayOfWeek     => 'Day of Week',
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
