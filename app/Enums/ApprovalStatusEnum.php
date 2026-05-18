<?php

namespace App\Enums;

enum ApprovalStatusEnum: int
{
    case Pending  = 1;
    case Approved = 2;
    case Rejected = 3;

    public function label(): string
    {
        return match($this) {
            self::Pending  => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
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
