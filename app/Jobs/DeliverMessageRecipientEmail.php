<?php

namespace App\Jobs;

use App\Mail\MessageRecipientMail;
use App\Models\MessageRecipient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DeliverMessageRecipientEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $messageRecipientId,
    ) {
    }

    public function handle(): void
    {
        $messageRecipient = MessageRecipient::query()
            ->with(['recipient', 'message.sender'])
            ->find($this->messageRecipientId);

        if (! $messageRecipient || ! $messageRecipient->recipient) {
            return;
        }

        $messageRecipient->forceFill([
            'email_attempted_at' => now(),
        ])->save();

        try {
            Mail::to($messageRecipient->recipient->email)
                ->send(new MessageRecipientMail($messageRecipient));

            $messageRecipient->forceFill([
                'email_status' => MessageRecipient::EMAIL_STATUS_SENT,
                'emailed_at' => now(),
                'email_failure' => null,
            ])->save();
        } catch (Throwable $exception) {
            $messageRecipient->forceFill([
                'email_status' => MessageRecipient::EMAIL_STATUS_FAILED,
                'email_failure' => $exception->getMessage(),
            ])->save();

            throw $exception;
        }
    }
}
