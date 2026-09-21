<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\CalendarRequest;
use App\Services\Calendar\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class CalendarController extends Controller
{
    public function __construct(
        protected CalendarService $calendarService
    ) {}

    /**
     * Return the authenticated user's unified calendar feed.
     */
    public function index(CalendarRequest $request): JsonResponse
    {
        $user = $request->user();

        $events = $this->calendarService->getEvents(
            user: $user,
            from: Carbon::parse($request->validated('from')),
            to: Carbon::parse($request->validated('to')),
        );

        return ResponseHelper::success(
            data: $events,
            message: 'Calendar events retrieved successfully.'
        );
    }
}
