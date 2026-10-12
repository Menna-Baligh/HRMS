<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use Illuminate\Support\Facades\Mail;

if (app()->isLocal()) {
    Route::get('/test-mail', function () {
        try {
            $to = config('mail.from.address') ?: 'test@example.com';
            Mail::raw('Testing mail connection', function ($message) use ($to) {
                $message->to($to)
                    ->subject('Test Email');
            });

            return 'Email Sent Successfully!';
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    });
}
