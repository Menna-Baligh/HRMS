<?php

namespace App\Providers;

use App\Events\LeaveRequestApproved;
use App\Events\LeaveRequestRejected;
use App\Events\LeaveRequestSubmitted;
use App\Listeners\SendLeaveApprovedNotification;
use App\Listeners\SendLeaveRejectedNotification;
use App\Listeners\SendLeaveSubmittedNotification;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Policies\LeaveRequestPolicy;
use App\Policies\LeaveTypePolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return (method_exists($user, 'hasRole') && $user->hasRole('Owner')) || (isset($user->role) && ($user->role === \App\Enums\UserRole::Owner || $user->role === 'Owner')) ? true : null;
        });

        Gate::policy(LeaveType::class, LeaveTypePolicy::class);
        Gate::policy(LeaveRequest::class, LeaveRequestPolicy::class);

        Event::listen(LeaveRequestSubmitted::class, SendLeaveSubmittedNotification::class);
        Event::listen(LeaveRequestApproved::class, SendLeaveApprovedNotification::class);
        Event::listen(LeaveRequestRejected::class, SendLeaveRejectedNotification::class);
    }
}
