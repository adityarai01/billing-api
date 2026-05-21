<?php

namespace App\Enums;

enum LeaveStatusEnum: int
{
    case Pending   = 1;
    case Approved  = 2;
    case Rejected  = 3;
    case Cancelled = 4;

    public function label(): string
    {
        return match($this) {
            self::Pending   => 'Pending',
            self::Approved  => 'Approved',
            self::Rejected  => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(fn($e) => ['value' => $e->value, 'label' => $e->label()], self::cases());
    }
}
