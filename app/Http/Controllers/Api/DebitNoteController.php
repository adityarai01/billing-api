<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\DebitNoteService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebitNoteController extends Controller
{
    use ApiResponseTrait;
    public function __construct(private DebitNoteService $service) {}
    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'total_amount'     => ['required', 'numeric', 'min:0'],
            'debit_note_date'  => ['required', 'date'],
            'debit_note_type'  => ['required', 'integer', 'in:1,2,3,4'],
        ]);
        $note = $this->service->createManual($this->orgId($request), $request->all());
        return $this->successResponse($note, 'Debit note created', 201);
    }

    public function search(Request $request): JsonResponse
    {
        $result = $this->service->searchDebitNotes($this->orgId($request), $request->all());
        return $this->successResponse($result, 'Debit notes fetched');
    }

    public function details(Request $request, int $id): JsonResponse
    {
        $note = $this->service->debitNoteDetails($this->orgId($request), $id);
        return $this->successResponse($note, 'Debit note details');
    }

    public function adjust(Request $request): JsonResponse
    {
        $request->validate([
            'debit_note_id'   => ['required', 'integer'],
            'adjusted_amount' => ['required', 'numeric', 'min:0.01'],
            'adjustment_date' => ['required', 'date'],
            'adjustment_type' => ['required', 'integer', 'in:1,2,3'],
        ]);
        $adj = $this->service->adjustDebitNote($this->orgId($request), $request->all());
        return $this->successResponse($adj, 'Debit note adjusted');
    }
}
