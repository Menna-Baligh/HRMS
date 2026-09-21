<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetManagerEmployeeAttendanceDetailRequest;
use App\Http\Requests\GetManagerTeamAttendanceRequest;
use App\Http\Resources\ManagerEmployeeAttendanceDetailResource;
use App\Http\Resources\ManagerTeamAttendanceResource;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ManagerAttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function today(GetManagerTeamAttendanceRequest $request): JsonResponse
    {
        try {
            $manager = auth('api')->user();

            if (! $manager) {
                return ResponseHelper::error(
                    message: __('attendance.manager_profile_not_found'),
                    statusCode: Response::HTTP_NOT_FOUND
                );
            }

            $result = $this->attendanceService->getManagerTeamTodayData(
                $manager,
                $request->date,
                $request->status,
                $request->search,
                $request->filled('per_page') ? (int) $request->per_page : 15
            );

            return ResponseHelper::success(
                data: [
                    'selected_date' => $result['selected_date'],
                    'summary' => $result['summary'],
                    'weekly_chart' => $result['weekly_chart'],
                    'team' => ManagerTeamAttendanceResource::collection($result['team'])->response()->getData(true),
                ],
                message: __('attendance.team_attendance_retrieved')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: __('auth.something_went_wrong'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function show(GetManagerEmployeeAttendanceDetailRequest $request, int $employeeId): JsonResponse
    {
        try {
            $manager = auth('api')->user();

            if (! $manager) {
                return ResponseHelper::error(
                    message: __('attendance.manager_profile_not_found'),
                    statusCode: Response::HTTP_NOT_FOUND
                );
            }

            $detail = $this->attendanceService->getManagerEmployeeAttendanceDetail(
                $manager,
                $employeeId,
                $request->date
            );

            if (! $detail) {
                return ResponseHelper::error(
                    message: __('attendance.employee_not_in_team'),
                    statusCode: Response::HTTP_FORBIDDEN
                );
            }

            return ResponseHelper::success(
                data: new ManagerEmployeeAttendanceDetailResource($detail),
                message: __('attendance.details_retrieved')
            );
        } catch (Throwable $e) {
            report($e);

            return ResponseHelper::error(
                message: __('auth.something_went_wrong'),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
