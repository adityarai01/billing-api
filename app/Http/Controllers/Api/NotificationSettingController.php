<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationSettingService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationSettingController extends Controller
{
    use ApiResponseTrait;
    public function __construct(private NotificationSettingService $service) {}

    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }

    public function index(Request $request): JsonResponse
    {
        $settings = $this->service->getSettings($this->orgId($request));
        return $this->successResponse($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $settings = $request->input('settings', []);
        if (empty($settings)) return $this->errorResponse('Settings data required', 422);
        $this->service->updateSettings($this->orgId($request), $settings);
        return $this->successResponse(null, 'Settings saved successfully');
    }
}
