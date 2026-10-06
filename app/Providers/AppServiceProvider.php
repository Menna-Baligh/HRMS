<?php

namespace App\Providers;

use App\Enums\UserRole;
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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

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
            return (method_exists($user, 'hasRole') && $user->hasRole('Owner')) || (isset($user->role) && ($user->role === UserRole::Owner || $user->role === 'Owner')) ? true : null;
        });

        Gate::policy(LeaveType::class, LeaveTypePolicy::class);
        Gate::policy(LeaveRequest::class, LeaveRequestPolicy::class);

        Event::listen(LeaveRequestSubmitted::class, SendLeaveSubmittedNotification::class);
        Event::listen(LeaveRequestApproved::class, SendLeaveApprovedNotification::class);
        Event::listen(LeaveRequestRejected::class, SendLeaveRejectedNotification::class);

        if (str_contains(config('app.url'), 'ngrok-free.dev')) {
            URL::forceScheme('https');
        }

        Mail::extend('brevo', function (array $config = []) {
            $key = $config['key'] ?? env('BREVO_KEY');
            $client = HttpClient::create();
            return (new BrevoTransportFactory(null, $client))->create(new Dsn('brevo+api', 'default', $key));
        });
    }
}
