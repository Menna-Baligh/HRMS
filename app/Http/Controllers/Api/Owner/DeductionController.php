<?php

namespace App\Http\Controllers\Api\Owner;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreDeductionRequest;
use App\Http\Resources\DeductionResource;
use App\Models\Deduction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DeductionController extends Controller
{
    public function index(): JsonResponse
    {
        $deductions = Deduction::with('user')->latest()->get();

        return ResponseHelper::success(
            data: DeductionResource::collection($deductions),
            message: __('financial.deductions.retrieved')
        );
    }

    public function store(StoreDeductionRequest $request): JsonResponse
    {
        $deduction = Deduction::create([
            ...$request->validated(),
            'type' => $request->validated('type', 'manual'),
            'status' => 'queued',
        ]);

        return ResponseHelper::success(
            data: new DeductionResource($deduction->load('user')),
            message: __('financial.deductions.created'),
            statusCode: Response::HTTP_CREATED
        );
    }
}
