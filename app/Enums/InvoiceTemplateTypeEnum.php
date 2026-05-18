<?php

namespace App\Enums;

enum InvoiceTemplateTypeEnum: int
{
    case Thermal80mm = 1;
    case Thermal58mm = 2;
    case A4GST = 3;
    case SimpleRetail = 4;

    public function label(): string
    {
        return match ($this) {
            self::Thermal80mm => 'Thermal 80mm',
            self::Thermal58mm => 'Thermal 58mm',
            self::A4GST => 'A4 GST',
            self::SimpleRetail => 'Simple Retail',
        };
    }

    public function slug(): string
    {
        return match ($this) {
            self::Thermal80mm => 'thermal_80mm',
            self::Thermal58mm => 'thermal_58mm',
            self::A4GST => 'a4_gst',
            self::SimpleRetail => 'simple_retail',
        };
    }

    public function view(): string
    {
        return match ($this) {
            self::Thermal80mm => 'invoices.thermal-80mm',
            self::Thermal58mm => 'invoices.thermal-58mm',
            self::A4GST => 'invoices.a4-gst',
            self::SimpleRetail => 'invoices.simple-retail',
        };
    }

    public function paperSize(): InvoicePaperSizeEnum
    {
        return match ($this) {
            self::Thermal80mm => InvoicePaperSizeEnum::Thermal80mm,
            self::Thermal58mm => InvoicePaperSizeEnum::Thermal58mm,
            self::A4GST, self::SimpleRetail => InvoicePaperSizeEnum::A4,
        };
    }

    public static function options(): array
    {
        return array_map(
            fn(self $case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'slug' => $case->slug(),
            ],
            self::cases()
        );
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
