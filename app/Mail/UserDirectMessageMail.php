<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserDirectMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipientUser,
        public string $subjectLine,
        public string $messageBody,
        public string $senderName
    ) {}

    public function build(): self
    {
        return $this
            ->subject($this->subjectLine)
            ->view('emails.user_direct_message');
    }
}
