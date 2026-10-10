<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\AuditSearchRequest;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AuditController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {}

    public function index(AuditSearchRequest $request): JsonResponse
    {
        try {
            $audits = $this->auditService->search(
                $request->validated()
            );

            return ResponseHelper::success(
                data: $audits,
                message: __('audit.retrieved_successfully')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                config('app.debug') ? $e->getMessage() : null,
                __('audit.failed_retrieve'),
                500
            );
        }
    }
}
