<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductMedicalDetail;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttributeValue;
use App\Models\Unit;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    use ApiResponseTrait;

    public function meta(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId($request);

        $categories = Category::query()
            ->select('id', 'name')
            ->where('deleted', 0)
            ->where(function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId)
                    ->orWhereNull('organization_id');
            })
            ->orderBy('name')
            ->get();

        $brands = Brand::query()
            ->select('id', 'name')
            ->where('deleted', 0)
            ->where(function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId)
                    ->orWhereNull('organization_id');
            })
            ->orderBy('name')
            ->get();

        $units = Unit::query()
            ->select('id', 'name', 'short_name')
            ->where('deleted', 0)
            ->where(function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId)
                    ->orWhereNull('organization_id');
            })
            ->orderBy('name')
            ->get();

        return $this->successResponse([
            'shop_type'  => $this->shopType($request),
            'categories' => $categories,
            'brands'     => $brands,
            'units'      => $units,
        ], 'Product meta fetched successfully');
    }

    public function index(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId($request);

        [$perPage] = $this->resolvePagination($request);

        $query = Product::query()
            ->with([
                'category:id,name',
                'brand:id,name',
                'medicalDetail' => fn ($medicalQuery) => $medicalQuery
                    ->where('deleted', 0)
                    ->select('id', 'product_id', 'generic_name', 'manufacturer'),
                'variants' => fn ($variantQuery) => $variantQuery
                    ->where('deleted', 0)
                    ->orderBy('id'),
            ])
            ->where('organization_id', $organizationId)
            ->where('deleted', 0)
            ->orderByDesc('id');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('hsn_code', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', (int) $request->input('brand_id'));
        }

        $products = $query->paginate($perPage);

        return $this->successResponse($products, 'Products fetched successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $shopType = $this->shopType($request);

        $validated = $this->validatePayload($request, $organizationId, $shopType);
        $this->validateUniqueVariantFields($this->variantsForValidation($validated), $organizationId);

        $product = DB::transaction(function () use ($validated, $organizationId, $shopType) {
            $product = Product::create([
                'organization_id' => $organizationId,
                'category_id'     => $validated['category_id'] ?? null,
                'brand_id'        => $validated['brand_id'] ?? null,
                'name'            => $validated['name'],
                'slug'            => $this->makeSlug($validated['name'], $organizationId),
                'product_type'    => $validated['product_type'] ?? 'goods',
                'description'     => $validated['description'] ?? null,
                'image'           => $validated['image'] ?? null,
                'hsn_code'        => $validated['hsn_code'] ?? null,
                'gst_percent'     => $validated['gst_percent'] ?? 0,
                'status'          => $validated['status'] ?? 1,
                'deleted'         => 0,
            ]);

            $createdVariants = $this->replaceVariants($product, $validated, $organizationId);
            $this->syncMedicalDetail($product, $validated, $organizationId, $shopType);
            $this->syncBatches($product, $validated, $createdVariants, $organizationId, $shopType);

            return $this->loadProduct($product->fresh());
        });

        return $this->successResponse($product, 'Product created successfully', 201);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $file = $request->file('image');
        $path = $file->store('products', 'public');
        $url  = asset('storage/' . $path);

        return $this->successResponse(['path' => $path, 'url' => $url], 'Image uploaded successfully');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $product = $this->findProduct($id, $this->organizationId($request));

        return $this->successResponse($this->loadProduct($product), 'Product fetched successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $shopType = $this->shopType($request);
        $product = $this->findProduct($id, $organizationId);

        $validated = $this->validatePayload($request, $organizationId, $shopType);
        $this->validateUniqueVariantFields($this->variantsForValidation($validated), $organizationId, $product->id);

        $updatedProduct = DB::transaction(function () use ($product, $validated, $organizationId, $shopType) {
            $product->update([
                'category_id'  => $validated['category_id'] ?? null,
                'brand_id'     => $validated['brand_id'] ?? null,
                'name'         => $validated['name'],
                'slug'         => $this->makeSlug($validated['name'], $organizationId, $product->id),
                'product_type' => $validated['product_type'] ?? 'goods',
                'description'  => $validated['description'] ?? null,
                'image'        => $validated['image'] ?? null,
                'hsn_code'     => $validated['hsn_code'] ?? null,
                'gst_percent'  => $validated['gst_percent'] ?? 0,
                'status'       => $validated['status'] ?? 1,
            ]);

            $createdVariants = $this->replaceVariants($product, $validated, $organizationId);
            $this->syncMedicalDetail($product, $validated, $organizationId, $shopType);
            $this->syncBatches($product, $validated, $createdVariants, $organizationId, $shopType);

            return $this->loadProduct($product->fresh());
        });

        return $this->successResponse($updatedProduct, 'Product updated successfully');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $product = $this->findProduct($id, $this->organizationId($request));

        DB::transaction(function () use ($product) {
            ProductBatch::where('product_id', $product->id)->delete();
            ProductVariantAttributeValue::where('product_id', $product->id)->delete();
            ProductVariant::where('product_id', $product->id)->delete();
            ProductMedicalDetail::where('product_id', $product->id)->delete();

            $product->update([
                'deleted' => 1,
                'status'  => 0,
            ]);
        });

        return $this->successResponse(null, 'Product deleted successfully');
    }

    private function validatePayload(Request $request, int $organizationId, int $shopType): array
    {
        $validator = Validator::make($request->all(), [
            'category_id'              => ['nullable', $this->existsForOrganization('categories', $organizationId)],
            'brand_id'                 => ['nullable', $this->existsForOrganization('brands', $organizationId)],
            'name'                     => ['required', 'string', 'max:255'],
            'product_type'             => ['nullable', 'string', 'max:50'],
            'description'              => ['nullable', 'string'],
            'image'                    => ['nullable', 'string', 'max:255'],
            'hsn_code'                 => ['nullable', 'string', 'max:20'],
            'gst_percent'              => ['nullable', 'numeric', 'min:0'],
            'status'                   => ['nullable', 'integer', Rule::in([0, 1])],
            'base_unit_id'             => ['nullable', $this->existsForOrganization('units', $organizationId)],
            'default_sku'              => ['nullable', 'string', 'max:100'],
            'default_barcode'          => ['nullable', 'string', 'max:100'],
            'default_purchase_price'   => ['nullable', 'numeric', 'min:0'],
            'default_selling_price'    => ['nullable', 'numeric', 'min:0'],
            'default_wholesale_price'  => ['nullable', 'numeric', 'min:0'],
            'default_mrp'              => ['nullable', 'numeric', 'min:0'],
            'default_stock_qty'        => ['nullable', 'numeric', 'min:0'],
            'default_low_stock_alert'  => ['nullable', 'numeric', 'min:0'],
            'enable_batch_tracking'    => ['nullable', 'boolean'],

            'medical_details'                        => ['nullable', 'array'],
            'medical_details.generic_name'           => ['nullable', 'string', 'max:255'],
            'medical_details.salt_composition'       => ['nullable', 'string'],
            'medical_details.manufacturer'           => ['nullable', 'string', 'max:255'],
            'medical_details.medicine_type'          => ['nullable', 'string', 'max:50'],
            'medical_details.dosage_form'            => ['nullable', 'string', 'max:50'],
            'medical_details.prescription_required'  => ['nullable', 'boolean'],
            'medical_details.storage_instruction'    => ['nullable', 'string'],

            'variants'                               => ['nullable', 'array'],
            'variants.*.unit_id'                     => ['nullable', $this->existsForOrganization('units', $organizationId)],
            'variants.*.sku'                         => ['nullable', 'string', 'max:100'],
            'variants.*.barcode'                     => ['nullable', 'string', 'max:100'],
            'variants.*.variant_name'                => ['nullable', 'string', 'max:255'],
            'variants.*.purchase_price'              => ['nullable', 'numeric', 'min:0'],
            'variants.*.selling_price'               => ['nullable', 'numeric', 'min:0'],
            'variants.*.wholesale_price'             => ['nullable', 'numeric', 'min:0'],
            'variants.*.mrp'                         => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_qty'                   => ['nullable', 'numeric', 'min:0'],
            'variants.*.opening_stock'               => ['nullable', 'numeric', 'min:0'],
            'variants.*.available_stock'             => ['nullable', 'numeric', 'min:0'],
            'variants.*.low_stock_alert'             => ['nullable', 'numeric', 'min:0'],
            'variants.*.image'                       => ['nullable', 'string', 'max:255'],
            'variants.*.status'                      => ['nullable', 'integer', Rule::in([0, 1])],
            'variants.*.attribute_values'            => ['nullable', 'array'],
            'variants.*.attribute_values.*.attribute_id'       => ['nullable', $this->existsForOrganization('attributes', $organizationId)],
            'variants.*.attribute_values.*.attribute_name'     => ['nullable', 'string', 'max:255'],
            'variants.*.attribute_values.*.attribute_value_id' => ['nullable', $this->existsForOrganization('attribute_values', $organizationId)],
            'variants.*.attribute_values.*.attribute_value'    => ['nullable', 'string', 'max:255'],

            'batches'                    => ['nullable', 'array'],
            'batches.*.variant_index'    => ['nullable', 'integer', 'min:0'],
            'batches.*.supplier_id'      => ['nullable', 'integer'],
            'batches.*.batch_no'         => ['required_with:batches', 'string', 'max:100'],
            'batches.*.mfg_date'         => ['nullable', 'date'],
            'batches.*.expiry_date'      => ['nullable', 'date'],
            'batches.*.purchase_price'   => ['nullable', 'numeric', 'min:0'],
            'batches.*.mrp'              => ['nullable', 'numeric', 'min:0'],
            'batches.*.selling_price'    => ['nullable', 'numeric', 'min:0'],
            'batches.*.opening_qty'      => ['nullable', 'numeric', 'min:0'],
            'batches.*.available_qty'    => ['nullable', 'numeric', 'min:0'],
            'batches.*.status'           => ['nullable', 'integer', Rule::in([0, 1])],
        ]);

        $validator->after(function ($validator) use ($request, $shopType) {
            if ($shopType !== OrganizationType::Medical->value && $request->filled('medical_details')) {
                $validator->errors()->add('medical_details', 'Medical details are allowed only for medical shops.');
            }

            if ($shopType === OrganizationType::Cloth->value && $request->filled('batches')) {
                $validator->errors()->add('batches', 'Batch details are not used for cloth shops.');
            }
        });

        return $validator->validate();
    }

    private function validateUniqueVariantFields(array $variants, int $organizationId, ?int $productId = null): void
    {
        $errors = [];

        foreach (['sku', 'barcode'] as $field) {
            $seen = [];

            foreach ($variants as $index => $variant) {
                $value = trim((string) ($variant[$field] ?? ''));

                if ($value === '') {
                    continue;
                }

                $key = Str::lower($value);

                if (isset($seen[$key])) {
                    $errors["variants.{$index}.{$field}"][] = ucfirst($field) . ' must be unique.';
                    continue;
                }

                $seen[$key] = true;

                $query = ProductVariant::query()
                    ->where('organization_id', $organizationId)
                    ->where('deleted', 0)
                    ->where($field, $value);

                if ($productId) {
                    $query->where('product_id', '!=', $productId);
                }

                if ($query->exists()) {
                    $errors["variants.{$index}.{$field}"][] = ucfirst($field) . ' already exists.';
                }
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function replaceVariants(Product $product, array $data, int $organizationId): array
    {
        ProductBatch::where('product_id', $product->id)->delete();
        ProductVariantAttributeValue::where('product_id', $product->id)->delete();
        ProductVariant::where('product_id', $product->id)->delete();

        $variants = $data['variants'] ?? [];

        if (empty($variants)) {
            $variants = [$this->defaultVariantData($data)];
        }

        $createdVariants = [];

        foreach ($variants as $index => $variantData) {
            $stockQty = $variantData['stock_qty']
                ?? $variantData['available_stock']
                ?? $variantData['opening_stock']
                ?? ($data['default_stock_qty'] ?? 0);

            $variant = ProductVariant::create([
                'organization_id'         => $organizationId,
                'product_id'              => $product->id,
                'unit_id'                 => $variantData['unit_id'] ?? ($data['base_unit_id'] ?? null),
                'sku'                     => $variantData['sku'] ?? ($data['default_sku'] ?? null),
                'barcode'                 => $variantData['barcode'] ?? ($data['default_barcode'] ?? null),
                'variant_name'            => $variantData['variant_name'] ?? $product->name,
                'purchase_price'          => $variantData['purchase_price'] ?? ($data['default_purchase_price'] ?? 0),
                'selling_price'           => $variantData['selling_price'] ?? ($data['default_selling_price'] ?? 0),
                'wholesale_price'         => $variantData['wholesale_price'] ?? ($data['default_wholesale_price'] ?? 0),
                'mrp'                     => $variantData['mrp'] ?? ($data['default_mrp'] ?? 0),
                'stock_qty'               => $stockQty,
                'available_stock_base_qty'=> $stockQty,
                'opening_stock_base_qty'  => $stockQty,
                'low_stock_alert'         => $variantData['low_stock_alert'] ?? ($data['default_low_stock_alert'] ?? 0),
                'image'                   => $variantData['image'] ?? null,
                'status'                  => $variantData['status'] ?? 1,
                'deleted'                 => 0,
            ]);

            $this->syncVariantAttributeValues($product, $variant, $variantData['attribute_values'] ?? [], $organizationId);

            $createdVariants[$index] = $variant;
        }

        return $createdVariants;
    }

    private function syncVariantAttributeValues(Product $product, ProductVariant $variant, array $attributeRows, int $organizationId): void
    {
        foreach ($attributeRows as $attributeRow) {
            [$attributeId, $attributeValueId] = $this->resolveAttributeValueIds($attributeRow, $organizationId);

            ProductVariantAttributeValue::create([
                'organization_id'    => $organizationId,
                'product_id'         => $product->id,
                'product_variant_id' => $variant->id,
                'attribute_id'       => $attributeId,
                'attribute_value_id' => $attributeValueId,
            ]);
        }
    }

    private function resolveAttributeValueIds(array $attributeRow, int $organizationId): array
    {
        $attributeId = $attributeRow['attribute_id'] ?? null;
        $attributeName = trim((string) ($attributeRow['attribute_name'] ?? ''));
        $attributeValueId = $attributeRow['attribute_value_id'] ?? null;
        $attributeValue = trim((string) ($attributeRow['attribute_value'] ?? ''));

        if (!$attributeId && $attributeName === '') {
            throw ValidationException::withMessages([
                'variants' => ['Attribute name or attribute id is required for variant attributes.'],
            ]);
        }

        $attribute = $attributeId
            ? Attribute::query()
                ->where('id', $attributeId)
                ->where('deleted', 0)
                ->where(function ($query) use ($organizationId) {
                    $query->where('organization_id', $organizationId)
                        ->orWhereNull('organization_id');
                })
                ->first()
            : null;

        if (!$attribute) {
            $attribute = Attribute::firstOrCreate(
                [
                    'organization_id' => $organizationId,
                    'name'            => $attributeName,
                ],
                [
                    'code'    => Str::slug($attributeName, '_'),
                    'status'  => 1,
                    'deleted' => 0,
                ]
            );
        }

        if (!$attributeValueId && $attributeValue === '') {
            throw ValidationException::withMessages([
                'variants' => ['Attribute value or attribute value id is required for variant attributes.'],
            ]);
        }

        $value = $attributeValueId
            ? AttributeValue::query()
                ->where('id', $attributeValueId)
                ->where('attribute_id', $attribute->id)
                ->where('deleted', 0)
                ->where(function ($query) use ($organizationId) {
                    $query->where('organization_id', $organizationId)
                        ->orWhereNull('organization_id');
                })
                ->first()
            : null;

        if (!$value) {
            $value = AttributeValue::firstOrCreate(
                [
                    'organization_id' => $organizationId,
                    'attribute_id'    => $attribute->id,
                    'value'           => $attributeValue,
                ],
                [
                    'code'    => Str::slug($attributeValue, '_'),
                    'status'  => 1,
                    'deleted' => 0,
                ]
            );
        }

        return [$attribute->id, $value->id];
    }

    private function syncMedicalDetail(Product $product, array $data, int $organizationId, int $shopType): void
    {
        if ($shopType !== OrganizationType::Medical->value) {
            ProductMedicalDetail::where('product_id', $product->id)->delete();
            return;
        }

        $medical = $data['medical_details'] ?? [];

        if (empty($medical)) {
            ProductMedicalDetail::where('product_id', $product->id)->delete();
            return;
        }

        ProductMedicalDetail::updateOrCreate(
            ['product_id' => $product->id],
            [
                'organization_id'         => $organizationId,
                'generic_name'            => $medical['generic_name'] ?? null,
                'salt_composition'        => $medical['salt_composition'] ?? null,
                'manufacturer'            => $medical['manufacturer'] ?? null,
                'medicine_type'           => $medical['medicine_type'] ?? null,
                'dosage_form'             => $medical['dosage_form'] ?? null,
                'prescription_required'   => !empty($medical['prescription_required']) ? 1 : 0,
                'storage_instruction'     => $medical['storage_instruction'] ?? null,
                'status'                  => 1,
                'deleted'                 => 0,
            ]
        );
    }

    private function syncBatches(Product $product, array $data, array $createdVariants, int $organizationId, int $shopType): void
    {
        ProductBatch::where('product_id', $product->id)->delete();

        if (!$this->shouldSaveBatches($data, $shopType)) {
            return;
        }

        $batches = $data['batches'] ?? [];

        foreach ($batches as $batchData) {
            $variantIndex = isset($batchData['variant_index']) ? (int) $batchData['variant_index'] : 0;
            $variant = $createdVariants[$variantIndex] ?? ($createdVariants[0] ?? null);

            if (!$variant) {
                throw ValidationException::withMessages([
                    'batches' => ['Batch variant mapping is invalid.'],
                ]);
            }

            ProductBatch::create([
                'organization_id'   => $organizationId,
                'product_id'        => $product->id,
                'product_variant_id'=> $variant->id,
                'supplier_id'       => $batchData['supplier_id'] ?? null,
                'batch_no'          => $batchData['batch_no'],
                'mfg_date'          => $batchData['mfg_date'] ?? null,
                'expiry_date'       => $batchData['expiry_date'] ?? null,
                'purchase_price'    => $batchData['purchase_price'] ?? 0,
                'mrp'               => $batchData['mrp'] ?? 0,
                'selling_price'     => $batchData['selling_price'] ?? 0,
                'opening_qty'       => $batchData['opening_qty'] ?? 0,
                'available_qty'     => $batchData['available_qty'] ?? 0,
                'status'            => $batchData['status'] ?? 1,
                'deleted'           => 0,
            ]);
        }
    }

    private function shouldSaveBatches(array $data, int $shopType): bool
    {
        if ($shopType === OrganizationType::Cloth->value) {
            return false;
        }

        if ($shopType === OrganizationType::Medical->value) {
            return !empty($data['batches']);
        }

        return !empty($data['enable_batch_tracking']) && !empty($data['batches']);
    }

    private function defaultVariantData(array $data): array
    {
        return [
            'unit_id'          => $data['base_unit_id'] ?? null,
            'sku'              => $data['default_sku'] ?? null,
            'barcode'          => $data['default_barcode'] ?? null,
            'variant_name'     => $data['name'] ?? 'Default Variant',
            'purchase_price'   => $data['default_purchase_price'] ?? 0,
            'selling_price'    => $data['default_selling_price'] ?? 0,
            'wholesale_price'  => $data['default_wholesale_price'] ?? 0,
            'mrp'              => $data['default_mrp'] ?? 0,
            'stock_qty'        => $data['default_stock_qty'] ?? 0,
            'low_stock_alert'  => $data['default_low_stock_alert'] ?? 0,
            'status'           => $data['status'] ?? 1,
            'attribute_values' => [],
        ];
    }

    private function variantsForValidation(array $data): array
    {
        $variants = $data['variants'] ?? [];

        if (!empty($variants)) {
            return $variants;
        }

        return [$this->defaultVariantData($data)];
    }

    private function loadProduct(Product $product): Product
    {
        return $product->load([
            'category:id,name',
            'brand:id,name',
            'medicalDetail' => fn ($query) => $query->where('deleted', 0),
            'variants' => fn ($variantQuery) => $variantQuery
                ->where('deleted', 0)
                ->with([
                    'unit:id,name,short_name',
                    'attributeValues.attribute:id,name',
                    'attributeValues.attributeValue:id,attribute_id,value',
                    'batches' => fn ($batchQuery) => $batchQuery
                        ->where('deleted', 0)
                        ->orderBy('id'),
                ])
                ->orderBy('id'),
            'batches' => fn ($batchQuery) => $batchQuery
                ->where('deleted', 0)
                ->orderBy('id'),
        ]);
    }

    private function findProduct(int $id, int $organizationId): Product
    {
        return Product::query()
            ->where('id', $id)
            ->where('organization_id', $organizationId)
            ->where('deleted', 0)
            ->firstOrFail();
    }

    private function organizationId(Request $request): int
    {
        $user = $this->currentUser($request);
        $organizationId = $user->organization?->id;

        if (!$organizationId) {
            throw ValidationException::withMessages([
                'organization' => ['Organization not found for current user.'],
            ]);
        }

        return (int) $organizationId;
    }

    private function shopType(Request $request): int
    {
        $user = $this->currentUser($request);
        $shopType = $user->organization?->shop_type;

        if ($shopType instanceof \BackedEnum) {
            return (int) $shopType->value;
        }

        return (int) $shopType;
    }

    private function currentUser(Request $request)
    {
        $user = $request->attributes->get('user');
        $user?->loadMissing('organization');

        return $user;
    }

    private function existsForOrganization(string $table, int $organizationId)
    {
        return Rule::exists($table, 'id')->where(function ($query) use ($organizationId) {
            $query->where('deleted', 0)
                ->where(function ($subQuery) use ($organizationId) {
                    $subQuery->where('organization_id', $organizationId)
                        ->orWhereNull('organization_id');
                });
        });
    }

    private function makeSlug(string $name, int $organizationId, ?int $ignoreProductId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'product';
        $slug = $baseSlug;
        $counter = 2;

        while (
            Product::query()
                ->where('organization_id', $organizationId)
                ->where('deleted', 0)
                ->where('slug', $slug)
                ->when($ignoreProductId, fn ($query) => $query->where('id', '!=', $ignoreProductId))
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
