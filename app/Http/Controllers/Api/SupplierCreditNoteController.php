<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\SupplierCreditNoteService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierCreditNoteController extends Controller
{
    use ApiResponseTrait;
    public function __construct(private SupplierCreditNoteService $service) {}
    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'total_amount'      => ['required', 'numeric', 'min:0'],
            'credit_note_date'  => ['required', 'date'],
            'credit_note_type'  => ['required', 'integer', 'in:1,2,3,4,5'],
        ]);
        $note = $this->service->createCreditNote($this->orgId($request), $request->all());
        return $this->successResponse($note, 'Supplier credit note created', 201);
    }

    public function search(Request $request): JsonResponse
    {
        $result = $this->service->searchCreditNotes($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Credit notes fetched');
    }

    public function details(Request $request, int $id): JsonResponse
    {
        $note = $this->service->creditNoteDetails($this->orgId($request), $id);
        return $this->successResponse($note, 'Credit note details');
    }

    public function adjust(Request $request): JsonResponse
    {
        $request->validate([
            'supplier_credit_note_id' => ['required', 'integer'],
            'adjusted_amount'         => ['required', 'numeric', 'min:0.01'],
            'adjustment_date'         => ['required', 'date'],
            'adjustment_type'         => ['required', 'integer', 'in:1,2,3'],
        ]);
        $adj = $this->service->adjustCreditNote($this->orgId($request), $request->all());
        return $this->successResponse($adj, 'Credit note adjusted');
    }
}
