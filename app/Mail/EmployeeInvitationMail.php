<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeInvitationMail extends Mailable implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to the Team! Activate Your Account',
        );
    }

    public function content(): Content
    {
        $activationUrl = config('app.frontend_url') . '/reset-password?email=' . urlencode($this->user->email); //* ensure from frontend team what is the correct url for activate account page, this is just a placeholder for now

        return new Content(
            markdown: 'emails.employee-invitation',
            with: [
                'activationUrl' => $activationUrl,
            ],
        );
    }
}
