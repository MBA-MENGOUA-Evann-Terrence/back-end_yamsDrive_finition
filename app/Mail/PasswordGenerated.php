<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Mail\PasswordGenerated;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;



class PasswordGenerated extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;

    public function __construct(User $user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre compte a été créé',
        );
    }
    /**
     * Get the message content definition.
     */
   public function content(): Content
    {
        return new Content(
            view: 'emails.password_generated',
        );
    }

    public function attachments(): array
    {
        return [];
    }

}
  
