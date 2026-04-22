<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Automation</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Configure reminders and lifecycle automation.</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/5">
                <h2 class="text-lg font-semibold text-slate-900">Automation switches</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">These settings control the daily automation run that handles plan transitions, member reminders, and leadership digests.</p>

                <form method="POST" action="{{ route('admin.automation.update') }}" class="mt-6 space-y-5">
                    @csrf
                    @method('PUT')

                    @php($toggleFields = [
                        'member_reading_reminders' => ['label' => 'Member reading reminders', 'description' => 'Nudge active readers when today’s reading still needs to be reported.'],
                        'member_training_reminders' => ['label' => 'Training follow-up reminders', 'description' => 'Remind people still in training to finish the onboarding resources.'],
                        'leader_digests' => ['label' => 'Leader branch digests', 'description' => 'Send daily snapshots to each leader about the people in their tree.'],
                        'admin_digests' => ['label' => 'Admin operations digests', 'description' => 'Send platform-wide daily summaries to admins with dashboard access.'],
                        'vacancy_alerts' => ['label' => 'Vacancy alerts', 'description' => 'Alert admins when a hierarchy is left without an assigned leader.'],
                        'email_enabled' => ['label' => 'Email delivery', 'description' => 'Allow automation alerts to also go out through email when user delivery preferences permit it.'],
                        'lifecycle_automation_enabled' => ['label' => 'Plan lifecycle transitions', 'description' => 'Automatically move recruiting cohorts to active and close active cohorts after their schedule ends.'],
                    ])

                    @foreach($toggleFields as $field => $config)
                        <label class="flex items-start gap-4 rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-4">
                            <input type="checkbox" name="{{ $field }}" value="1" @checked($settings[$field]) class="mt-1 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                            <span>
                                <span class="block text-sm font-semibold text-slate-900">{{ $config['label'] }}</span>
                                <span class="mt-1 block text-sm leading-6 text-slate-500">{{ $config['description'] }}</span>
                            </span>
                        </label>
                    @endforeach

                    <button type="submit" class="inline-flex rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-950/10 transition hover:bg-slate-800">
                        Save automation settings
                    </button>
                </form>
            </div>

            <div class="space-y-4">
                <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/5">
                    <h2 class="text-lg font-semibold text-slate-900">Run now</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Trigger the daily automation cycle immediately. Duplicate reminders for the same day are suppressed.</p>
                    <form method="POST" action="{{ route('admin.automation.run-now') }}" class="mt-5">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            Run automation now
                        </button>
                    </form>
                </section>

                <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/5">
                    <h2 class="text-lg font-semibold text-slate-900">Last run</h2>
                    <p class="mt-3 text-sm text-slate-500">{{ $settings['last_run_at'] ? \Illuminate\Support\Carbon::parse($settings['last_run_at'])->format('M j, Y g:i A') : 'The automation cycle has not run yet.' }}</p>
                    <p class="mt-3 text-xs uppercase tracking-[0.18em] text-slate-400">Scheduled command</p>
                    <p class="mt-1 text-sm font-medium text-slate-700"><code>php artisan automation:run-daily</code></p>
                </section>
            </div>
        </section>
    </div>
</x-admin-layout>
