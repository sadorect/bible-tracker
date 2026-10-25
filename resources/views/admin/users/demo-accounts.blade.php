<x-admin-layout title="Demo accounts" eyebrow="People Ops">

    <div class="space-y-6">
        @if (session('info'))
            <div class="rounded-2xl bg-sky-50 px-5 py-4 text-sm font-medium text-sky-800">{{ session('info') }}</div>
        @endif

        <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">What these are</p>
            <h2 class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($totalCount) }} demo account(s) found</h2>
            <p class="mt-3 max-w-3xl text-sm text-slate-600">
                These accounts were created by the database seeders for testing and all share the seeder's default
                password. Only addresses on reserved example domains
                (<span class="font-mono text-xs">{{ implode(', ', $reservedDomains) }}</span>) are matched, so a real
                member's account can never be caught by this action. Remove them before opening the app to real members.
            </p>

            @if ($totalCount > 0)
                <form method="POST" action="{{ route('admin.users.demo-accounts.purge') }}" class="mt-6 border-t border-slate-200 pt-6"
                      onsubmit="return confirm('Delete all {{ $totalCount }} seeded demo account(s)? This cannot be undone.');">
                    @csrf
                    @method('DELETE')

                    <label class="flex items-start gap-3 text-sm text-slate-700">
                        <input type="checkbox" name="confirm" value="1" required
                               class="mt-0.5 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                        <span>
                            I understand this permanently deletes these {{ number_format($totalCount) }} accounts and
                            all of their reading progress.
                        </span>
                    </label>

                    @error('confirm')
                        <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                    @enderror

                    <button type="submit"
                            class="mt-4 inline-flex items-center justify-center rounded-2xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">
                        Delete all demo accounts
                    </button>
                </form>
            @endif
        </section>

        <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-slate-900">Accounts to be removed</h2>
                <a href="{{ route('admin.users.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">
                    Back to directory
                </a>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <th class="py-3 pr-4">Name</th>
                            <th class="py-3 pr-4">Email</th>
                            <th class="py-3 pr-4">Role</th>
                            <th class="py-3 pr-4">Plans</th>
                            <th class="py-3 pr-4">Progress</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($accounts as $account)
                            <tr>
                                <td class="py-3 pr-4 font-medium text-slate-900">{{ $account->name }}</td>
                                <td class="py-3 pr-4 font-mono text-xs text-slate-600">{{ $account->email }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $roleOptions[$account->role] ?? $account->role }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $account->reading_plans_count }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $account->reading_progress_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-500">
                                    No seeded demo accounts remain.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $accounts->links() }}
            </div>
        </section>
    </div>
</x-admin-layout>
