<?php

namespace App\Enums;

enum AttendanceStatusEnum: int
{
    case Present    = 1;
    case Absent     = 2;
    case HalfDay    = 3;
    case Late       = 4;
    case OnLeave    = 5;
    case Holiday    = 6;
    case WeeklyOff  = 7;

    public function label(): string
    {
        return match($this) {
            self::Present   => 'Present',
            self::Absent    => 'Absent',
            self::HalfDay   => 'Half Day',
            self::Late      => 'Late',
            self::OnLeave   => 'On Leave',
            self::Holiday   => 'Holiday',
            self::WeeklyOff => 'Weekly Off',
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
