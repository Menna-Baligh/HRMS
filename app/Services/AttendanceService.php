<?php
namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Exception;

class AttendanceService
{
    public function __construct(private GeofenceService $geofenceService) {}


    public function checkIn(Employee $employee, float $lat, float $lng): Attendance
    {
        $today = now()->toDateString();

        $existingAttendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if ($existingAttendance) {
            throw new Exception('DUPLICATE_CHECKIN');
        }

        $location = $employee->companyLocation;
        if (! $location || ! $location->is_active) {
            throw new Exception('LOCATION_NOT_CONFIGURED');
        }

        $isInside = $this->geofenceService->isWithinRadius(
            $lat, $lng, $location->latitude, $location->longitude, $location->radius
        );

        if (! $isInside) {
            throw new Exception('OUTSIDE_RADIUS');
        }

        $now = now();
        $shiftStartTime = Carbon::parse('09:00:00');
        $status = $now->gt($shiftStartTime) ? 'Late' : 'Present';

        return Attendance::create([
            'employee_id' => $employee->id,
            'company_location_id' => $location->id,
            'date' => $today,
            'check_in' => $now,
            'check_in_lat' => $lat,
            'check_in_lng' => $lng,
            'status' => $status,
        ]);
    }


    public function checkOut(Employee $employee, float $lat, float $lng): Attendance
    {
        $today = now()->toDateString();

        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if (! $attendance || ! $attendance->check_in) {
            throw new Exception('NO_OPEN_CHECKIN');
        }

        if ($attendance->check_out) {
            throw new Exception('ALREADY_CHECKED_OUT');
        }

        $location = $attendance->companyLocation;
        $isInside = $this->geofenceService->isWithinRadius(
            $lat, $lng, $location->latitude, $location->longitude, $location->radius
        );

        if (! $isInside) {
            throw new Exception('OUTSIDE_RADIUS');
        }

        $now = now();
        $workedSeconds = $now->diffInSeconds($attendance->check_in);

        $attendance->update([
            'check_out' => $now,
            'check_out_lat' => $lat,
            'check_out_lng' => $lng,
            'worked_seconds' => $workedSeconds,
        ]);

        return $attendance;
    }

    
    public function getTodayData(Employee $employee, ?float $currentLat = null, ?float $currentLng = null): array
    {
        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', now()->toDateString())
            ->first();

        $location = $employee->companyLocation;
        $distance = null;
        $isInside = false;

        if ($location && $currentLat && $currentLng) {
            $distance = $this->geofenceService->calculateDistance(
                $currentLat, $currentLng, $location->latitude, $location->longitude
            );
            $isInside = $distance <= $location->radius;
        }

        $workedSeconds = 0;
        if ($attendance && $attendance->check_in) {
            $endTime = $attendance->check_out ?? now();
            $workedSeconds = $endTime->diffInSeconds($attendance->check_in);
        }

        return [
            'has_checked_in' => (bool) ($attendance?->check_in),
            'has_checked_out' => (bool) ($attendance?->check_out),
            'check_in_time' => $attendance?->check_in?->format('h:i A'),
            'check_out_time' => $attendance?->check_out?->format('h:i A'),
            'status' => $attendance?->status ?? 'Absent',
            'worked_seconds' => $workedSeconds,
            'distance_meters' => $distance,
            'is_inside_radius' => $isInside,
        ];
    }
}
