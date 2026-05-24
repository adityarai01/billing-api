<?php

namespace App\Services;

use App\Models\ProductVariantUnit;
use App\Models\ProductVariant;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductVariantUnitService
{
    public function createUnit(array $data): ProductVariantUnit
    {
        return DB::transaction(function () use ($data) {
            $this->validateBusinessRules($data);

            if (!empty($data['is_base_unit'])) {
                $this->enforceOnlyOneBaseUnit($data['organization_id'], $data['product_variant_id']);
            }
            if (!empty($data['is_default_sale_unit'])) {
                $this->enforceOnlyOneDefaultSaleUnit($data['organization_id'], $data['product_variant_id']);
            }
            if (!empty($data['is_default_purchase_unit'])) {
                $this->enforceOnlyOneDefaultPurchaseUnit($data['organization_id'], $data['product_variant_id']);
            }

            $unit = Unit::find($data['unit_id']);
            $variantUnit = ProductVariantUnit::create([
                'organization_id'          => $data['organization_id'],
                'product_id'               => $data['product_id'],
                'product_variant_id'       => $data['product_variant_id'],
                'unit_id'                  => $data['unit_id'],
                'unit_name_snapshot'       => $unit?->name,
                'conversion_qty'           => $data['conversion_qty'],
                'purchase_price'           => $data['purchase_price'] ?? 0,
                'mrp'                      => $data['mrp'] ?? 0,
                'selling_price'            => $data['selling_price'] ?? 0,
                'wholesale_price'          => $data['wholesale_price'] ?? 0,
                'barcode'                  => $data['barcode'] ?? null,
                'is_base_unit'             => $data['is_base_unit'] ?? 0,
                'is_default_purchase_unit' => $data['is_default_purchase_unit'] ?? 0,
                'is_default_sale_unit'     => $data['is_default_sale_unit'] ?? 0,
                'status'                   => $data['status'] ?? 1,
                'deleted'                  => 0,
                'created_by'               => $data['created_by'] ?? null,
            ]);

            if (!empty($data['is_base_unit'])) {
                $this->syncBaseUnitOnVariant($data['product_variant_id'], $data['unit_id'], $unit?->name);
            }

            return $variantUnit->load('unit');
        });
    }

    public function updateUnit(int $id, array $data): ProductVariantUnit
    {
        return DB::transaction(function () use ($id, $data) {
            $variantUnit = ProductVariantUnit::where('id', $id)
                ->where('organization_id', $data['organization_id'])
                ->where('deleted', 0)
                ->firstOrFail();

            $this->validateBusinessRules($data, $id);

            if (!empty($data['is_base_unit']) && !$variantUnit->is_base_unit) {
                $this->enforceOnlyOneBaseUnit($data['organization_id'], $variantUnit->product_variant_id, $id);
            }
            if (!empty($data['is_default_sale_unit']) && !$variantUnit->is_default_sale_unit) {
                $this->enforceOnlyOneDefaultSaleUnit($data['organization_id'], $variantUnit->product_variant_id, $id);
            }
            if (!empty($data['is_default_purchase_unit']) && !$variantUnit->is_default_purchase_unit) {
                $this->enforceOnlyOneDefaultPurchaseUnit($data['organization_id'], $variantUnit->product_variant_id, $id);
            }

            $unit = Unit::find($data['unit_id'] ?? $variantUnit->unit_id);
            $variantUnit->update([
                'unit_id'                  => $data['unit_id'] ?? $variantUnit->unit_id,
                'unit_name_snapshot'       => $unit?->name ?? $variantUnit->unit_name_snapshot,
                'conversion_qty'           => $data['conversion_qty'] ?? $variantUnit->conversion_qty,
                'purchase_price'           => $data['purchase_price'] ?? $variantUnit->purchase_price,
                'mrp'                      => $data['mrp'] ?? $variantUnit->mrp,
                'selling_price'            => $data['selling_price'] ?? $variantUnit->selling_price,
                'wholesale_price'          => $data['wholesale_price'] ?? $variantUnit->wholesale_price,
                'barcode'                  => array_key_exists('barcode', $data) ? $data['barcode'] : $variantUnit->barcode,
                'is_base_unit'             => $data['is_base_unit'] ?? $variantUnit->is_base_unit,
                'is_default_purchase_unit' => $data['is_default_purchase_unit'] ?? $variantUnit->is_default_purchase_unit,
                'is_default_sale_unit'     => $data['is_default_sale_unit'] ?? $variantUnit->is_default_sale_unit,
                'status'                   => $data['status'] ?? $variantUnit->status,
                'updated_by'               => $data['updated_by'] ?? null,
            ]);

            if (!empty($data['is_base_unit'])) {
                $this->syncBaseUnitOnVariant($variantUnit->product_variant_id, $variantUnit->unit_id, $unit?->name);
            }

            return $variantUnit->fresh()->load('unit');
        });
    }

    public function deleteUnit(int $id, int $organizationId): void
    {
        $variantUnit = ProductVariantUnit::where('id', $id)
            ->where('organization_id', $organizationId)
            ->where('deleted', 0)
            ->firstOrFail();

        if ($variantUnit->is_base_unit) {
            throw ValidationException::withMessages([
                'unit' => ['Cannot delete the base unit. Set another unit as base unit first.'],
            ]);
        }

        $variantUnit->update(['deleted' => 1, 'status' => 0]);
    }

    public function listUnitsByVariant(int $productVariantId, int $organizationId): \Illuminate\Support\Collection
    {
        return ProductVariantUnit::where('product_variant_id', $productVariantId)
            ->where('organization_id', $organizationId)
            ->where('deleted', 0)
            ->with('unit:id,name,short_name')
            ->orderByDesc('is_base_unit')
            ->orderBy('conversion_qty')
            ->get()
            ->map(fn($vu) => $this->formatVariantUnit($vu));
    }

    public function setBaseUnit(int $productVariantId, int $unitId, int $organizationId): void
    {
        $variantUnit = ProductVariantUnit::where('product_variant_id', $productVariantId)
            ->where('unit_id', $unitId)
            ->where('organization_id', $organizationId)
            ->where('deleted', 0)
            ->firstOrFail();

        if ((float) $variantUnit->conversion_qty !== 1.0) {
            throw ValidationException::withMessages([
                'unit' => ['Base unit must have conversion_qty = 1.'],
            ]);
        }

        DB::transaction(function () use ($productVariantId, $organizationId, $variantUnit) {
            ProductVariantUnit::where('product_variant_id', $productVariantId)
                ->where('organization_id', $organizationId)
                ->update(['is_base_unit' => 0]);

            $variantUnit->update(['is_base_unit' => 1]);
            $this->syncBaseUnitOnVariant($productVariantId, $variantUnit->unit_id, $variantUnit->unit_name_snapshot);
        });
    }

    public function setDefaultSaleUnit(int $productVariantId, int $unitId, int $organizationId): void
    {
        $variantUnit = ProductVariantUnit::where('product_variant_id', $productVariantId)
            ->where('unit_id', $unitId)
            ->where('organization_id', $organizationId)
            ->where('deleted', 0)
            ->firstOrFail();

        DB::transaction(function () use ($productVariantId, $organizationId, $variantUnit) {
            ProductVariantUnit::where('product_variant_id', $productVariantId)
                ->where('organization_id', $organizationId)
                ->update(['is_default_sale_unit' => 0]);

            $variantUnit->update(['is_default_sale_unit' => 1]);
        });
    }

    public function setDefaultPurchaseUnit(int $productVariantId, int $unitId, int $organizationId): void
    {
        $variantUnit = ProductVariantUnit::where('product_variant_id', $productVariantId)
            ->where('unit_id', $unitId)
            ->where('organization_id', $organizationId)
            ->where('deleted', 0)
            ->firstOrFail();

        DB::transaction(function () use ($productVariantId, $organizationId, $variantUnit) {
            ProductVariantUnit::where('product_variant_id', $productVariantId)
                ->where('organization_id', $organizationId)
                ->update(['is_default_purchase_unit' => 0]);

            $variantUnit->update(['is_default_purchase_unit' => 1]);
        });
    }

    public function calculateBaseQty(float $qty, float $conversionQty): float
    {
        return round($qty * $conversionQty, 3);
    }

    public function getSalePriceByUnit(int $productVariantId, int $unitId): float
    {
        $vu = ProductVariantUnit::where('product_variant_id', $productVariantId)
            ->where('unit_id', $unitId)
            ->where('deleted', 0)
            ->first();

        if ($vu) return (float) $vu->selling_price;

        $variant = ProductVariant::find($productVariantId);
        return (float) ($variant?->selling_price ?? 0);
    }

    public function getPurchasePriceByUnit(int $productVariantId, int $unitId): float
    {
        $vu = ProductVariantUnit::where('product_variant_id', $productVariantId)
            ->where('unit_id', $unitId)
            ->where('deleted', 0)
            ->first();

        if ($vu) return (float) $vu->purchase_price;

        $variant = ProductVariant::find($productVariantId);
        return (float) ($variant?->purchase_price ?? 0);
    }

    public function getAvailableStockInUnit(int $productVariantId, int $unitId): float
    {
        $variant = ProductVariant::find($productVariantId);
        if (!$variant) return 0;

        $baseQty = (float) ($variant->available_stock_base_qty ?: $variant->stock_qty);

        $vu = ProductVariantUnit::where('product_variant_id', $productVariantId)
            ->where('unit_id', $unitId)
            ->where('deleted', 0)
            ->first();

        $conversion = $vu ? (float) $vu->conversion_qty : 1;
        if ($conversion <= 0) return 0;

        return floor(($baseQty / $conversion) * 1000) / 1000;
    }

    public function getVariantUnitsForPos(int $productVariantId, int $organizationId): array
    {
        $units = ProductVariantUnit::where('product_variant_id', $productVariantId)
            ->where('organization_id', $organizationId)
            ->where('status', 1)
            ->where('deleted', 0)
            ->with('unit:id,name,short_name')
            ->orderByDesc('is_base_unit')
            ->orderBy('conversion_qty')
            ->get();

        $variant = ProductVariant::find($productVariantId);
        $baseQty = (float) ($variant?->available_stock_base_qty ?: $variant?->stock_qty ?? 0);

        return $units->map(function ($vu) use ($baseQty) {
            $conversion = (float) $vu->conversion_qty;
            $stockInUnit = $conversion > 0 ? floor(($baseQty / $conversion) * 1000) / 1000 : 0;
            return [
                'id'                       => $vu->id,
                'unit_id'                  => $vu->unit_id,
                'unit_name'                => $vu->unit_name_snapshot ?? $vu->unit?->name,
                'unit_short_name'          => $vu->unit?->short_name,
                'conversion_qty'           => $conversion,
                'selling_price'            => (float) $vu->selling_price,
                'purchase_price'           => (float) $vu->purchase_price,
                'mrp'                      => (float) $vu->mrp,
                'wholesale_price'          => (float) $vu->wholesale_price,
                'barcode'                  => $vu->barcode,
                'is_base_unit'             => (bool) $vu->is_base_unit,
                'is_default_sale_unit'     => (bool) $vu->is_default_sale_unit,
                'is_default_purchase_unit' => (bool) $vu->is_default_purchase_unit,
                'available_stock'          => $stockInUnit,
                'meaning'                  => $this->buildMeaning($vu),
            ];
        })->toArray();
    }

    public function buildMeaning(ProductVariantUnit $vu): string
    {
        $unitName = $vu->unit_name_snapshot ?? $vu->unit?->name ?? 'Unit';
        $baseUnit = $this->getBaseUnitName($vu->product_variant_id);
        $qty = (float) $vu->conversion_qty;

        if ($qty == 1) return "1 {$unitName} = 1 {$baseUnit}";
        $display = $qty == floor($qty) ? (int) $qty : $qty;
        return "1 {$unitName} = {$display} {$baseUnit}";
    }

    private function getBaseUnitName(int $productVariantId): string
    {
        $baseVU = ProductVariantUnit::where('product_variant_id', $productVariantId)
            ->where('is_base_unit', 1)
            ->where('deleted', 0)
            ->first();

        if ($baseVU) return $baseVU->unit_name_snapshot ?? $baseVU->unit?->name ?? 'Base Unit';

        $variant = ProductVariant::find($productVariantId);
        return $variant?->base_unit_name ?? 'Base Unit';
    }

    private function validateBusinessRules(array $data, ?int $excludeId = null): void
    {
        $conversionQty = (float) ($data['conversion_qty'] ?? 0);
        if (!empty($data['is_base_unit']) && $conversionQty !== 1.0) {
            throw ValidationException::withMessages([
                'conversion_qty' => ['Base unit conversion quantity must be 1.'],
            ]);
        }

        $query = ProductVariantUnit::where('product_variant_id', $data['product_variant_id'])
            ->where('unit_id', $data['unit_id'])
            ->where('organization_id', $data['organization_id'])
            ->where('deleted', 0);

        if ($excludeId) $query->where('id', '!=', $excludeId);

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'unit_id' => ['This unit is already added to this product variant.'],
            ]);
        }

        if (!empty($data['barcode'])) {
            $barcodeQuery = ProductVariantUnit::where('organization_id', $data['organization_id'])
                ->where('barcode', $data['barcode'])
                ->where('deleted', 0);

            if ($excludeId) $barcodeQuery->where('id', '!=', $excludeId);

            if ($barcodeQuery->exists()) {
                throw ValidationException::withMessages([
                    'barcode' => ['This barcode is already used by another unit in this organization.'],
                ]);
            }
        }
    }

    private function enforceOnlyOneBaseUnit(int $orgId, int $variantId, ?int $excludeId = null): void
    {
        ProductVariantUnit::where('organization_id', $orgId)
            ->where('product_variant_id', $variantId)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->update(['is_base_unit' => 0]);
    }

    private function enforceOnlyOneDefaultSaleUnit(int $orgId, int $variantId, ?int $excludeId = null): void
    {
        ProductVariantUnit::where('organization_id', $orgId)
            ->where('product_variant_id', $variantId)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->update(['is_default_sale_unit' => 0]);
    }

    private function enforceOnlyOneDefaultPurchaseUnit(int $orgId, int $variantId, ?int $excludeId = null): void
    {
        ProductVariantUnit::where('organization_id', $orgId)
            ->where('product_variant_id', $variantId)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->update(['is_default_purchase_unit' => 0]);
    }

    private function syncBaseUnitOnVariant(int $productVariantId, int $unitId, ?string $unitName): void
    {
        ProductVariant::where('id', $productVariantId)->update([
            'base_unit_id'   => $unitId,
            'base_unit_name' => $unitName,
        ]);
    }

    private function formatVariantUnit(ProductVariantUnit $vu): array
    {
        return [
            'id'                       => $vu->id,
            'product_variant_id'       => $vu->product_variant_id,
            'unit_id'                  => $vu->unit_id,
            'unit_name'                => $vu->unit_name_snapshot ?? $vu->unit?->name,
            'unit_short_name'          => $vu->unit?->short_name,
            'conversion_qty'           => (float) $vu->conversion_qty,
            'purchase_price'           => (float) $vu->purchase_price,
            'mrp'                      => (float) $vu->mrp,
            'selling_price'            => (float) $vu->selling_price,
            'wholesale_price'          => (float) $vu->wholesale_price,
            'barcode'                  => $vu->barcode,
            'is_base_unit'             => (bool) $vu->is_base_unit,
            'is_default_purchase_unit' => (bool) $vu->is_default_purchase_unit,
            'is_default_sale_unit'     => (bool) $vu->is_default_sale_unit,
            'status'                   => $vu->status,
            'meaning'                  => $this->buildMeaning($vu),
        ];
    }
}
