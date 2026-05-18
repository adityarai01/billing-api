<?php

namespace App\Enums;

enum UserType: int
{
    case SuperAdmin        = 1;
    case ShopOwner         = 2;
    case Cashier           = 3;
    case InventoryManager  = 4;
    case Accountant        = 5;
    case Staff             = 6;

    public function label(): string
    {
        return match($this) {
            self::SuperAdmin       => 'Super Admin',
            self::ShopOwner        => 'Shop Owner',
            self::Cashier          => 'Cashier',
            self::InventoryManager => 'Inventory Manager',
            self::Accountant       => 'Accountant',
            self::Staff            => 'Staff',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
