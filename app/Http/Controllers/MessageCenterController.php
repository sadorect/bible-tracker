<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Support\SchemaCapabilities;
use App\Services\Messaging\MessageCenterService;
use App\Services\Messaging\MessageVariableRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MessageCenterController extends Controller
{
    public function __construct(
        private readonly MessageCenterService $messageCenter,
        private readonly MessageVariableRenderer $variableRenderer,
    ) {
    }

    public function inbox(Request $request): View
    {
        $user = $request->user();
        $state = $request->string('state')->toString();
        $folder = $request->string('folder')->toString() ?: 'inbox';
        $search = trim($request->string('search')->toString());

        $query = $user->receivedMessageRecipients()
            ->with(['message.sender', 'message.threadRoot'])
            ->orderByDesc('created_at');

        if (SchemaCapabilities::supportsMessageRecipientFolders()) {
            $query = match ($folder) {
                'archive' => $query->whereNull('deleted_at')->whereNotNull('archived_at'),
                'trash' => $query->whereNotNull('deleted_at'),
                default => $query->whereNull('deleted_at')->whereNull('archived_at'),
            };
        } else {
            $folder = 'inbox';
        }

        if ($state === 'unread') {
            $query->whereNull('read_at');
        } elseif ($state === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($search !== '') {
            $query->where(function ($scoped) use ($search) {
                $scoped->where('rendered_subject', 'like', "%{$search}%")
                    ->orWhere('rendered_body', 'like', "%{$search}%")
                    ->orWhereHas('message.sender', function ($senders) use ($search) {
                        $senders->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return view('messages.inbox', [
            'layoutComponent' => $this->layoutComponent($user),
            'inboxItems' => $query->paginate(15)->withQueryString(),
            'filters' => compact('state', 'folder', 'search'),
        ]);
    }

    public function sent(Request $request): View
    {
        $user = $request->user();
        $folder = $request->string('folder')->toString() ?: 'sent';
        $search = trim($request->string('search')->toString());

        $query = $user->sentMessages()
            ->with('template')
            ->withCount('recipients')
            ->withCount([
                'recipients as read_count' => fn ($recipients) => $recipients->whereNotNull('read_at'),
                'recipients as emailed_count' => fn ($recipients) => $recipients->where('email_status', 'sent'),
                'recipients as failed_email_count' => fn ($recipients) => $recipients->where('email_status', 'failed'),
            ])
            ->orderByDesc('created_at');

        if (SchemaCapabilities::supportsMessageSenderFolders()) {
            $query = match ($folder) {
                'archive' => $query->whereNull('sender_deleted_at')->whereNotNull('sender_archived_at'),
                'trash' => $query->whereNotNull('sender_deleted_at'),
                default => $query->whereNull('sender_deleted_at')->whereNull('sender_archived_at'),
            };
        } else {
            $folder = 'sent';
        }

        if ($search !== '') {
            $query->where(function ($scoped) use ($search) {
                $scoped->where('subject', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        return view('messages.sent', [
            'layoutComponent' => $this->layoutComponent($user),
            'sentItems' => $query->paginate(15)->withQueryString(),
            'filters' => compact('folder', 'search'),
        ]);
    }

    public function compose(Request $request): View
    {
        $user = $request->user();
        $replyThread = $request->filled('reply_to')
            ? $this->threadRoot(Message::query()->findOrFail($request->integer('reply_to')))
            : null;

        if ($replyThread) {
            Gate::authorize('view', $replyThread);
        }

        $direction = $request->string('direction')->toString();

        if ($direction === '') {
            $direction = $user->isAdmin()
                ? Message::DIRECTION_DOWNWARD
                : ($user->isLeader() ? Message::DIRECTION_DOWNWARD : Message::DIRECTION_UPWARD);
        }

        return $this->composeView(
            user: $user,
            formData: [
                'direction' => $direction,
                'template_id' => '',
                'subject' => $replyThread ? $this->replySubject($replyThread->subject) : '',
                'body' => '',
                'hierarchy_ids' => [],
                'roles' => [],
                'active_state' => '',
                'active_plan_id' => '',
                'plan_type' => '',
                'training_status' => '',
                'pace_status' => '',
                'reply_to' => $replyThread?->id,
            ],
            previewRecipients: collect(),
            replyThread: $replyThread,
            selectedRecipientIds: [],
        );
    }

    public function preview(Request $request): View
    {
        $user = $request->user();
        $formData = $this->validatedComposePayload($request, $user);
        $replyThread = ! empty($formData['reply_to'])
            ? $this->threadRoot(Message::query()->findOrFail((int) $formData['reply_to']))
            : null;

        if ($replyThread) {
            Gate::authorize('view', $replyThread);
        }

        $previewRecipients = $replyThread
            ? $this->messageCenter->previewReplyRecipients($user, $replyThread)
            : ($formData['direction'] === Message::DIRECTION_DOWNWARD
                ? $this->messageCenter->previewDownwardRecipients($user, $this->extractFilters($formData))
                : $this->messageCenter->previewUpwardRecipients($user));

        return $this->composeView(
            user: $user,
            formData: $formData,
            previewRecipients: $previewRecipients,
            replyThread: $replyThread,
            selectedRecipientIds: $previewRecipients->pluck('id')->all(),
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $formData = $this->validatedComposePayload($request, $user);
        $template = $this->resolveTemplate($formData['template_id'] ?? null);
        $replyThread = ! empty($formData['reply_to'])
            ? $this->threadRoot(Message::query()->findOrFail((int) $formData['reply_to']))
            : null;

        if ($replyThread) {
            Gate::authorize('reply', $replyThread);
        }

        $payload = [
            'subject' => $formData['subject'],
            'body' => $formData['body'],
            'template' => $template,
        ];

        if ($replyThread) {
            $allowedIds = $this->messageCenter->previewReplyRecipients($user, $replyThread)->pluck('id')->all();
            $selectedIds = $this->validatedSelectedRecipientIds($request, $allowedIds);
            $payload['excluded_recipient_ids'] = array_values(array_diff($allowedIds, $selectedIds));
            $payload['thread_root'] = $replyThread;
            $payload['parent_message'] = $replyThread;

            $message = $this->messageCenter->sendReply($user, $replyThread, $payload);
        } elseif ($formData['direction'] === Message::DIRECTION_DOWNWARD) {
            $allowedIds = $this->messageCenter
                ->previewDownwardRecipients($user, $this->extractFilters($formData))
                ->pluck('id')
                ->all();
            $selectedIds = $this->validatedSelectedRecipientIds($request, $allowedIds);
            $payload['filters'] = $this->extractFilters($formData);
            $payload['excluded_recipient_ids'] = array_values(array_diff($allowedIds, $selectedIds));

            $message = $this->messageCenter->sendDownward($user, $payload);
        } else {
            $message = $this->messageCenter->sendUpward($user, $payload);
        }

        return redirect()->route('messages.show', $this->threadRoot($message))
            ->with('success', 'Message sent successfully.');
    }

    public function show(Request $request, Message $message): View
    {
        $user = $request->user();
        $threadRoot = $this->threadRoot($message);

        Gate::authorize('view', $threadRoot);

        $threadMessages = Message::query()
            ->where('thread_root_id', $threadRoot->id)
            ->with(['sender', 'recipients.recipient'])
            ->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->orWhereHas('recipients', function ($recipients) use ($user) {
                        $recipients->where('recipient_id', $user->id);
                    });
            })
            ->orderBy('created_at')
            ->get();

        $userRecipientRows = $user->receivedMessageRecipients()
            ->whereIn('message_id', $threadMessages->pluck('id'))
            ->get()
            ->keyBy('message_id');

        $userRecipientRows
            ->filter(fn ($row) => $row->read_at === null)
            ->each
            ->markAsRead();

        $replyRecipients = $this->messageCenter->previewReplyRecipients($user, $threadRoot);
        $threadState = $this->messageCenter->threadFolderState($user, $threadRoot);

        return view('messages.show', [
            'layoutComponent' => $this->layoutComponent($user),
            'threadRoot' => $threadRoot->load(['sender', 'template']),
            'threadMessages' => $threadMessages,
            'userRecipientRows' => $userRecipientRows,
            'replyRecipients' => $replyRecipients,
            'canReply' => $replyRecipients->isNotEmpty() && Gate::allows('reply', $threadRoot),
            'replySubject' => $this->replySubject($threadRoot->subject),
            'threadMailbox' => $threadState['mailbox'],
            'threadFolder' => $threadState['folder'],
        ]);
    }

    public function archive(Request $request, Message $message): RedirectResponse
    {
        $threadRoot = $this->threadRoot($message);

        Gate::authorize('view', $threadRoot);

        $this->messageCenter->archiveThread($request->user(), $threadRoot);

        return back()->with('success', 'Conversation archived.');
    }

    public function trash(Request $request, Message $message): RedirectResponse
    {
        $threadRoot = $this->threadRoot($message);

        Gate::authorize('view', $threadRoot);

        $this->messageCenter->trashThread($request->user(), $threadRoot);

        return redirect()->route('messages.'.($request->user()->id === $threadRoot->sender_id ? 'sent' : 'inbox'), [
            'folder' => 'trash',
        ])->with('success', 'Conversation moved to trash.');
    }

    public function restore(Request $request, Message $message): RedirectResponse
    {
        $threadRoot = $this->threadRoot($message);

        Gate::authorize('view', $threadRoot);

        $this->messageCenter->restoreThread($request->user(), $threadRoot);

        $folder = $request->user()->id === $threadRoot->sender_id ? 'sent' : 'inbox';

        return redirect()->route('messages.'.$folder)->with('success', 'Conversation restored.');
    }

    private function composeView(
        User $user,
        array $formData,
        Collection $previewRecipients,
        ?Message $replyThread,
        array $selectedRecipientIds,
    ): View {
        $hierarchies = $user->isAdmin()
            ? \App\Models\Hierarchy::query()->with('parent')->ordered()->get()
            : ($user->currentLeadershipHierarchy()
                ? \App\Models\Hierarchy::query()
                    ->whereIn('id', $user->currentLeadershipHierarchy()->descendantIdsIncludingSelf())
                    ->with('parent')
                    ->ordered()
                    ->get()
                : collect());

        return view('messages.compose', [
            'layoutComponent' => $this->layoutComponent($user),
            'formData' => $formData,
            'templates' => MessageTemplate::query()->where('is_active', true)->orderBy('name')->get(),
            'hierarchies' => $hierarchies,
            'roles' => User::roleOptions(),
            'planTypes' => ReadingPlan::typeConfigurations(),
            'readingPlans' => ReadingPlan::query()->orderBy('name')->get(),
            'trainingStatuses' => [
                'not_required' => 'Training Not Required',
                'not_started' => 'Training Not Started',
                'partial' => 'Training In Progress',
                'completed' => 'Training Completed',
            ],
            'paceStatuses' => [
                'in_training' => 'In Training',
                'awaiting_start' => 'Awaiting Start',
                'catching_up' => 'Catching Up',
                'on_track' => 'On Track',
                'reading_ahead' => 'Reading Ahead',
                'no_active_plan' => 'No Active Plan',
            ],
            'availableVariables' => $this->variableRenderer->availableVariables(),
            'previewRecipients' => $previewRecipients,
            'selectedRecipientIds' => $selectedRecipientIds,
            'replyThread' => $replyThread,
            'canSendDownward' => Gate::allows('send-downward-messages'),
            'canSendUpward' => Gate::allows('send-upward-messages'),
        ]);
    }

    private function validatedComposePayload(Request $request, User $user): array
    {
        $allowedDirections = [];

        if (Gate::allows('send-downward-messages')) {
            $allowedDirections[] = Message::DIRECTION_DOWNWARD;
        }

        if (Gate::allows('send-upward-messages')) {
            $allowedDirections[] = Message::DIRECTION_UPWARD;
        }

        return $request->validate([
            'direction' => ['required', Rule::in($allowedDirections)],
            'template_id' => ['nullable', 'exists:message_templates,id'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'hierarchy_ids' => ['array'],
            'hierarchy_ids.*' => ['integer', 'exists:hierarchies,id'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::in(User::assignableRoles())],
            'active_state' => ['nullable', Rule::in(['active', 'inactive'])],
            'active_plan_id' => ['nullable', 'integer', 'exists:reading_plans,id'],
            'plan_type' => ['nullable', Rule::in(array_keys(ReadingPlan::typeConfigurations()))],
            'training_status' => ['nullable', Rule::in(['not_required', 'not_started', 'partial', 'completed'])],
            'pace_status' => ['nullable', Rule::in(['in_training', 'awaiting_start', 'catching_up', 'on_track', 'reading_ahead', 'no_active_plan'])],
            'reply_to' => ['nullable', 'integer', 'exists:messages,id'],
        ]);
    }

    private function validatedSelectedRecipientIds(Request $request, array $allowedIds): array
    {
        if ($allowedIds === []) {
            return [];
        }

        $validated = $request->validate([
            'recipient_ids' => ['required', 'array', 'min:1'],
            'recipient_ids.*' => ['integer', Rule::in($allowedIds)],
        ]);

        return array_map('intval', $validated['recipient_ids']);
    }

    private function extractFilters(array $formData): array
    {
        return [
            'hierarchy_ids' => $formData['hierarchy_ids'] ?? [],
            'roles' => $formData['roles'] ?? [],
            'active_state' => $formData['active_state'] ?? '',
            'active_plan_id' => $formData['active_plan_id'] ?? '',
            'plan_type' => $formData['plan_type'] ?? '',
            'training_status' => $formData['training_status'] ?? '',
            'pace_status' => $formData['pace_status'] ?? '',
        ];
    }

    private function resolveTemplate(?string $templateId): ?MessageTemplate
    {
        return $templateId ? MessageTemplate::query()->find($templateId) : null;
    }

    private function threadRoot(Message $message): Message
    {
        if (! $message->thread_root_id || $message->thread_root_id === $message->id) {
            return $message;
        }

        return Message::query()->findOrFail($message->thread_root_id);
    }

    private function replySubject(string $subject): string
    {
        return str_starts_with($subject, 'Re: ') ? $subject : "Re: {$subject}";
    }

    private function layoutComponent(User $user): string
    {
        return $user->isAdmin() ? 'admin-layout' : 'app-layout';
    }
}
