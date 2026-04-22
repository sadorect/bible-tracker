<?php

namespace App\Services\Messaging;

use App\Jobs\DeliverMessageRecipientEmail;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Support\SchemaCapabilities;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MessageCenterService
{
    public function __construct(
        private readonly MessageAudienceResolver $audienceResolver,
        private readonly MessagePreferenceResolver $preferenceResolver,
        private readonly MessageVariableRenderer $variableRenderer,
    ) {
    }

    public function previewDownwardRecipients(User $sender, array $filters): EloquentCollection
    {
        return $this->audienceResolver->resolveDownwardRecipients($sender, $filters);
    }

    public function previewUpwardRecipients(User $sender): EloquentCollection
    {
        return $this->audienceResolver->resolveUpwardRecipients($sender);
    }

    public function previewReplyRecipients(User $sender, Message $threadRoot): EloquentCollection
    {
        return $this->audienceResolver->resolveReplyRecipients($sender, $threadRoot);
    }

    public function sendDownward(User $sender, array $payload): Message
    {
        $recipients = $this->applyExclusions(
            $this->audienceResolver->resolveDownwardRecipients($sender, $payload['filters'] ?? []),
            $payload['excluded_recipient_ids'] ?? [],
        );

        return $this->storeMessage(
            sender: $sender,
            recipients: $recipients,
            direction: Message::DIRECTION_DOWNWARD,
            subject: $payload['subject'],
            body: $payload['body'],
            template: $payload['template'] ?? null,
            targetingFilters: $payload['filters'] ?? [],
            targetingSnapshot: [
                'mode' => 'downward',
                'recipient_count' => $recipients->count(),
            ],
            parentMessage: $payload['parent_message'] ?? null,
            threadRoot: $payload['thread_root'] ?? null,
        );
    }

    public function sendUpward(User $sender, array $payload): Message
    {
        $recipients = $this->applyExclusions(
            $this->audienceResolver->resolveUpwardRecipients($sender),
            $payload['excluded_recipient_ids'] ?? [],
        );

        return $this->storeMessage(
            sender: $sender,
            recipients: $recipients,
            direction: Message::DIRECTION_UPWARD,
            subject: $payload['subject'],
            body: $payload['body'],
            template: $payload['template'] ?? null,
            targetingFilters: ['mode' => 'upward'],
            targetingSnapshot: [
                'mode' => 'upward',
                'recipient_count' => $recipients->count(),
            ],
            parentMessage: $payload['parent_message'] ?? null,
            threadRoot: $payload['thread_root'] ?? null,
        );
    }

    public function sendReply(User $sender, Message $threadRoot, array $payload): Message
    {
        $recipients = $this->applyExclusions(
            $this->audienceResolver->resolveReplyRecipients($sender, $threadRoot),
            $payload['excluded_recipient_ids'] ?? [],
        );

        $direction = $sender->isAdmin() || $sender->isLeader()
            ? Message::DIRECTION_DOWNWARD
            : Message::DIRECTION_UPWARD;

        return $this->storeMessage(
            sender: $sender,
            recipients: $recipients,
            direction: $direction,
            subject: $payload['subject'],
            body: $payload['body'],
            template: $payload['template'] ?? null,
            targetingFilters: ['mode' => 'reply'],
            targetingSnapshot: [
                'mode' => 'reply',
                'recipient_count' => $recipients->count(),
            ],
            parentMessage: $payload['parent_message'] ?? $threadRoot,
            threadRoot: $threadRoot,
        );
    }

    public function archiveThread(User $user, Message $threadRoot): void
    {
        $this->updateThreadState($user, $threadRoot, 'archive');
    }

    public function trashThread(User $user, Message $threadRoot): void
    {
        $this->updateThreadState($user, $threadRoot, 'trash');
    }

    public function restoreThread(User $user, Message $threadRoot): void
    {
        $this->updateThreadState($user, $threadRoot, 'restore');
    }

    public function threadFolderState(User $user, Message $threadRoot): array
    {
        $root = $threadRoot->fresh();
        $recipientRow = MessageRecipient::query()
            ->where('recipient_id', $user->id)
            ->where('message_id', $root->id)
            ->first();

        if ($recipientRow) {
            return [
                'mailbox' => 'inbox',
                'folder' => SchemaCapabilities::supportsMessageRecipientFolders()
                    ? ($recipientRow->deleted_at ? 'trash' : ($recipientRow->archived_at ? 'archive' : 'inbox'))
                    : 'inbox',
            ];
        }

        if ($root->sender_id === $user->id) {
            return [
                'mailbox' => 'sent',
                'folder' => SchemaCapabilities::supportsMessageSenderFolders()
                    ? ($root->sender_deleted_at ? 'trash' : ($root->sender_archived_at ? 'archive' : 'sent'))
                    : 'sent',
            ];
        }

        return [
            'mailbox' => 'inbox',
            'folder' => 'inbox',
        ];
    }

    private function storeMessage(
        User $sender,
        EloquentCollection $recipients,
        string $direction,
        string $subject,
        string $body,
        ?MessageTemplate $template = null,
        array $targetingFilters = [],
        array $targetingSnapshot = [],
        ?Message $parentMessage = null,
        ?Message $threadRoot = null,
    ): Message {
        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages([
                'recipients' => 'No recipients matched the current messaging rules and filters.',
            ]);
        }

        /** @var Message $message */
        $message = DB::transaction(function () use (
            $sender,
            $recipients,
            $direction,
            $subject,
            $body,
            $template,
            $targetingFilters,
            $targetingSnapshot,
            $parentMessage,
            $threadRoot,
        ) {
            $message = Message::create([
                'sender_id' => $sender->id,
                'message_template_id' => $template?->id,
                'parent_message_id' => $parentMessage?->id,
                'thread_root_id' => $threadRoot?->id,
                'direction' => $direction,
                'subject' => $subject,
                'body' => $body,
                'targeting_filters' => $targetingFilters,
                'targeting_snapshot' => $targetingSnapshot,
            ]);

            if (! $message->thread_root_id) {
                $message->forceFill([
                    'thread_root_id' => $message->id,
                ])->save();
            }

            foreach ($recipients as $recipient) {
                $rendered = $this->variableRenderer->renderForRecipient($subject, $body, $recipient, $sender);
                $deliveryPreference = $this->preferenceResolver->resolveFor($recipient);
                $allowInbox = $this->preferenceResolver->allowsInbox($deliveryPreference);
                $allowEmail = $this->preferenceResolver->allowsEmail($deliveryPreference);

                $messageRecipient = MessageRecipient::create([
                    'message_id' => $message->id,
                    'recipient_id' => $recipient->id,
                    'delivery_preference_snapshot' => $deliveryPreference,
                    'rendered_subject' => $rendered['subject'],
                    'rendered_body' => $rendered['body'],
                    'inbox_delivered_at' => $allowInbox ? now() : null,
                    'email_status' => $allowEmail && $recipient->hasVerifiedEmail()
                        ? MessageRecipient::EMAIL_STATUS_PENDING
                        : MessageRecipient::EMAIL_STATUS_SKIPPED,
                    'email_failure' => $allowEmail && ! $recipient->hasVerifiedEmail()
                        ? 'Recipient does not have a verified email address.'
                        : null,
                ]);

                if ($allowEmail && $recipient->hasVerifiedEmail()) {
                    DeliverMessageRecipientEmail::dispatch($messageRecipient->id);
                }
            }

            return $message;
        });

        return $message->fresh(['sender', 'template', 'recipients.recipient']);
    }

    private function applyExclusions(EloquentCollection $recipients, array $excludedRecipientIds): EloquentCollection
    {
        $excluded = collect($excludedRecipientIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($excluded->isEmpty()) {
            return $recipients;
        }

        return $recipients
            ->reject(fn (User $recipient) => $excluded->contains($recipient->id))
            ->values();
    }

    private function updateThreadState(User $user, Message $threadRoot, string $action): void
    {
        DB::transaction(function () use ($user, $threadRoot, $action) {
            $recipientRows = MessageRecipient::query()
                ->where('recipient_id', $user->id)
                ->whereHas('message', fn ($query) => $query->where('thread_root_id', $threadRoot->id));
            $sentMessages = Message::query()
                ->where('sender_id', $user->id)
                ->where('thread_root_id', $threadRoot->id);

            if (SchemaCapabilities::supportsMessageRecipientFolders()) {
                match ($action) {
                    'archive' => $recipientRows->update([
                        'archived_at' => now(),
                        'deleted_at' => null,
                    ]),
                    'trash' => $recipientRows->update([
                        'deleted_at' => now(),
                    ]),
                    default => $recipientRows->update([
                        'archived_at' => null,
                        'deleted_at' => null,
                    ]),
                };
            }

            if (SchemaCapabilities::supportsMessageSenderFolders()) {
                match ($action) {
                    'archive' => $sentMessages->update([
                        'sender_archived_at' => now(),
                        'sender_deleted_at' => null,
                    ]),
                    'trash' => $sentMessages->update([
                        'sender_deleted_at' => now(),
                    ]),
                    default => $sentMessages->update([
                        'sender_archived_at' => null,
                        'sender_deleted_at' => null,
                    ]),
                };
            }
        });
    }
}
