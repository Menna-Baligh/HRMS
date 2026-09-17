<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use Illuminate\Support\Facades\Mail;

Route::get('/test-mail', function () {
    try {
        Mail::raw('Testing Brevo connection', function ($message) {
            $message->to('mennabaligh06@gmail.com')
                ->subject('Test Email');
        });

        return 'Email Sent Successfully!';
    } catch (Throwable $e) {
        return response()->json([
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }
});
