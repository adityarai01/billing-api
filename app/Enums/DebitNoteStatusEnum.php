<?php

namespace App\Enums;

enum DebitNoteStatusEnum: int
{
    case Open               = 1;
    case PartiallyAdjusted  = 2;
    case Adjusted           = 3;
    case Cancelled          = 4;

    public function label(): string
    {
        return match($this) {
            self::Open              => 'Open',
            self::PartiallyAdjusted => 'Partially Adjusted',
            self::Adjusted          => 'Adjusted',
            self::Cancelled         => 'Cancelled',
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
