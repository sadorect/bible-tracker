<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use App\Services\Messaging\MessageVariableRenderer;
use App\Services\Messaging\MessagingSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminMessagingSettingsController extends Controller
{
    public function __construct(
        private readonly MessagingSettings $settings,
        private readonly MessageVariableRenderer $variableRenderer,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function index(): View
    {
        Gate::authorize('manage-message-templates');

        return view('admin.messages.settings', [
            'defaultDelivery' => $this->settings->defaultDelivery(),
            'emailEnabled' => $this->settings->emailEnabled(),
            'templates' => MessageTemplate::query()->with('creator')->orderBy('name')->get(),
            'deliveryOptions' => User::messageDeliveryOptions(),
            'availableVariables' => $this->variableRenderer->availableVariables(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('manage-message-templates');

        $validated = $request->validate([
            'default_delivery' => ['required', Rule::in(array_keys(User::messageDeliveryOptions()))],
            'email_enabled' => ['nullable', 'boolean'],
        ]);

        $this->settings->update([
            MessagingSettings::KEY_DEFAULT_DELIVERY => $validated['default_delivery'],
            MessagingSettings::KEY_EMAIL_ENABLED => $request->boolean('email_enabled'),
        ]);

        $this->auditLogger->log(
            'messages.settings_updated',
            $request->user(),
            null,
            [
                'default_delivery' => $validated['default_delivery'],
                'email_enabled' => $request->boolean('email_enabled'),
            ],
            'Updated messaging settings.',
        );

        return back()->with('success', 'Messaging settings updated successfully.');
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        Gate::authorize('manage-message-templates');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject_template' => ['required', 'string', 'max:255'],
            'body_template' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $template = MessageTemplate::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLogger->log(
            'messages.template_created',
            $request->user(),
            $template,
            [
                'is_active' => $template->is_active,
            ],
            "Created message template {$template->name}.",
        );

        return back()->with('success', 'Message template created successfully.');
    }

    public function updateTemplate(Request $request, MessageTemplate $messageTemplate): RedirectResponse
    {
        Gate::authorize('manage-message-templates');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subject_template' => ['required', 'string', 'max:255'],
            'body_template' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $messageTemplate->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->auditLogger->log(
            'messages.template_updated',
            $request->user(),
            $messageTemplate,
            [
                'is_active' => $messageTemplate->is_active,
            ],
            "Updated message template {$messageTemplate->name}.",
        );

        return back()->with('success', 'Message template updated successfully.');
    }

    public function destroyTemplate(MessageTemplate $messageTemplate): RedirectResponse
    {
        Gate::authorize('manage-message-templates');

        $name = $messageTemplate->name;
        $messageTemplate->delete();

        $this->auditLogger->log(
            'messages.template_deleted',
            request()->user(),
            $messageTemplate,
            [],
            "Deleted message template {$name}.",
        );

        return back()->with('success', 'Message template deleted successfully.');
    }
}
