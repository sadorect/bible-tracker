<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Automation\AutomationRunner;
use App\Services\Automation\AutomationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminAutomationController extends Controller
{
    public function __construct(
        private readonly AutomationSettings $settings,
        private readonly AutomationRunner $automationRunner,
    ) {
    }

    public function index()
    {
        return view('admin.automation.index', [
            'settings' => $this->settings->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->settings->update([
            AutomationSettings::KEY_MEMBER_READING_REMINDERS => $request->boolean('member_reading_reminders'),
            AutomationSettings::KEY_MEMBER_TRAINING_REMINDERS => $request->boolean('member_training_reminders'),
            AutomationSettings::KEY_LEADER_DIGESTS => $request->boolean('leader_digests'),
            AutomationSettings::KEY_ADMIN_DIGESTS => $request->boolean('admin_digests'),
            AutomationSettings::KEY_VACANCY_ALERTS => $request->boolean('vacancy_alerts'),
            AutomationSettings::KEY_EMAIL_ENABLED => $request->boolean('email_enabled'),
            AutomationSettings::KEY_LIFECYCLE_AUTOMATION => $request->boolean('lifecycle_automation_enabled'),
        ]);

        return back()->with('success', 'Automation settings updated successfully.');
    }

    public function runNow(Request $request): RedirectResponse
    {
        $summary = $this->automationRunner->run(today(), true, $request->user());

        return back()->with('success', 'Automation ran successfully: '
            ."{$summary['member_reading_reminders']} reading reminders, "
            ."{$summary['member_training_reminders']} training reminders, "
            ."{$summary['leader_digests']} leader digests, "
            ."{$summary['admin_digests']} admin digests.");
    }
}
