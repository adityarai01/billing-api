<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Http\Request;

trait ApiResponseTrait
{
    public function successResponse($data = null, string $message = 'Success', int $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $this->normalizeResponseData($data)
        ], $status);
    }

    public function paginatedSuccessResponse($data, string $message = 'Success', int $status = 200)
    {
        return $this->successResponse($data, $message, $status);
    }

    protected function resolvePagination(Request $request, int $defaultPerPage = 15, int $maxPerPage = 100): array
    {
        $perPage = (int) $request->input('per_page', $request->input('limit', $defaultPerPage));
        $page = (int) $request->input('page', 1);

        $perPage = max(1, min($maxPerPage, $perPage));
        $page = max(1, $page);

        return [$perPage, $page];
    }

    public function errorResponse(string $message = 'Something went wrong', int $status = 500, $errors = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }

    public function notFoundResponse(string $message = 'Data not found')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null
        ], 404);
    }

    protected function normalizeResponseData($data)
    {
        if ($data instanceof LengthAwarePaginatorContract || $data instanceof PaginatorContract) {
            $records = $data->items();

            return $this->formatPaginatedPayload(
                $records,
                (int) $data->currentPage(),
                (int) (method_exists($data, 'lastPage') ? $data->lastPage() : 1),
                (int) (method_exists($data, 'total') ? $data->total() : count($records))
            );
        }

        if (is_array($data)) {
            $normalized = $this->normalizePaginatedArray($data);

            if ($normalized !== null) {
                return $normalized;
            }
        }

        return $data;
    }

    protected function normalizePaginatedArray(array $data): ?array
    {
        if (array_key_exists('pagination', $data) && is_array($data['pagination'])) {
            $pagination = $data['pagination'];
            $recordInfo = $this->extractPaginatedRecords($data);

            if ($recordInfo !== null) {
                return array_merge(
                    $this->formatPaginatedPayload(
                        $recordInfo['value'],
                        (int) ($pagination['page'] ?? $pagination['current_page'] ?? $data['page'] ?? 1),
                        (int) ($pagination['total_page'] ?? $pagination['last_page'] ?? $data['total_page'] ?? $data['last_page'] ?? 1),
                        (int) ($pagination['total_data'] ?? $pagination['total'] ?? $data['total_data'] ?? $data['total'] ?? $this->countPaginatedRecords($recordInfo['value']))
                    ),
                    $this->removePaginationKeys($data, [$recordInfo['key'], 'pagination'])
                );
            }
        }

        $page = $data['page'] ?? $data['current_page'] ?? null;
        $totalPage = $data['total_page'] ?? $data['last_page'] ?? null;
        $totalData = $data['total_data'] ?? $data['total'] ?? null;
        $recordInfo = $this->extractPaginatedRecords($data);

        if (($page !== null || $totalPage !== null || $totalData !== null) && $recordInfo !== null) {
            return array_merge(
                $this->formatPaginatedPayload(
                    $recordInfo['value'],
                    (int) ($page ?? 1),
                    (int) ($totalPage ?? 1),
                    (int) ($totalData ?? $this->countPaginatedRecords($recordInfo['value']))
                ),
                $this->removePaginationKeys($data, [$recordInfo['key']])
            );
        }

        return null;
    }

    protected function extractPaginatedRecords(array $data): ?array
    {
        foreach (['record', 'records', 'data', 'items', 'messages', 'images'] as $key) {
            if (array_key_exists($key, $data)) {
                return [
                    'key' => $key,
                    'value' => $data[$key],
                ];
            }
        }

        return null;
    }

    protected function removePaginationKeys(array $data, array $extraKeys = []): array
    {
        $keys = array_merge([
            'page',
            'current_page',
            'total_page',
            'last_page',
            'total_data',
            'total',
            'limit',
            'per_page',
            'record',
            'records',
            'data',
            'items',
            'messages',
            'images',
        ], $extraKeys);

        return array_diff_key($data, array_flip($keys));
    }

    protected function countPaginatedRecords($records): int
    {
        if (is_countable($records)) {
            return count($records);
        }

        return 0;
    }

    protected function formatPaginatedPayload($records, int $page = 1, int $totalPage = 1, int $totalData = 0): array
    {
        return [
            'page' => max(1, $page),
            'total_page' => max(1, $totalPage),
            'total_data' => max(0, $totalData),
            'record' => $records,
        ];
    }
}
