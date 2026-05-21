<?php

namespace App\Enums;

enum EmploymentTypeEnum: int
{
    case FullTime   = 1;
    case PartTime   = 2;
    case Contract   = 3;
    case DailyWage  = 4;
    case Intern     = 5;

    public function label(): string
    {
        return match($this) {
            self::FullTime  => 'Full Time',
            self::PartTime  => 'Part Time',
            self::Contract  => 'Contract',
            self::DailyWage => 'Daily Wage',
            self::Intern    => 'Intern',
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
