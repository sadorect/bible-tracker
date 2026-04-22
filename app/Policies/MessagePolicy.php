<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function view(User $user, Message $message): bool
    {
        $threadRootId = $message->thread_root_id ?: $message->id;

        return Message::query()
            ->where('thread_root_id', $threadRootId)
            ->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->orWhereHas('recipients', function ($recipients) use ($user) {
                        $recipients->where('recipient_id', $user->id);
                    });
            })
            ->exists();
    }

    public function reply(User $user, Message $message): bool
    {
        return $this->view($user, $message);
    }
}
