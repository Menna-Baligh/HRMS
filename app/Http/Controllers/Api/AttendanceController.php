<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetTodayAttendanceRequest;
use App\Http\Resources\TodayAttendanceResource;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function today(GetTodayAttendanceRequest $request): JsonResponse
    {
        $employee = auth('api')->user()?->employee;

        if (! $employee) {
            return ResponseHelper::error(
                message: 'Employee profile not found.',
                statusCode: Response::HTTP_NOT_FOUND
            );
        }

        $todayData = $this->attendanceService->getTodayData(
            $employee,
            $request->filled('latitude') ? (float) $request->latitude : null,
            $request->filled('longitude') ? (float) $request->longitude : null
        );

        return ResponseHelper::success(
            data: new TodayAttendanceResource($todayData),
            message: 'Today attendance retrieved successfully.'
        );
    }
}
