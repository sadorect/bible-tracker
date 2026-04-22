<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">People Ops</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Edit {{ $user->name }}.</h1>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-8">
                @csrf
                @method('PUT')

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Account details</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Basic information</h2>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Name</span>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        @error('name')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Email</span>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        @error('email')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Phone number</span>
                        <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number', $user->phone_number) }}" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        <p class="mt-2 text-xs text-slate-500">Optional. Phone login requires a saved number.</p>
                        @error('phone_number')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Hierarchy role</span>
                        <select name="role" id="role" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            @foreach($roleOptions as $value => $label)
                                <option value="{{ $value }}" {{ old('role', $user->role) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('role')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block md:col-span-2">
                        <span class="text-sm font-medium text-slate-700">System access roles</span>
                        <select name="system_role_ids[]" multiple class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            @foreach($systemRoles as $systemRole)
                                <option value="{{ $systemRole->id }}" {{ in_array($systemRole->id, old('system_role_ids', $userSystemRoleIds), true) ? 'selected' : '' }}>
                                    {{ $systemRole->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-slate-500">These roles grant admin console capabilities without changing the user’s hierarchy assignment.</p>
                        @error('system_role_ids')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                        @error('system_role_ids.*')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block md:col-span-2">
                        <span class="text-sm font-medium text-slate-700">Hierarchy assignment</span>
                        <select name="hierarchy_id" id="hierarchy_id" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            <option value="">Leave unassigned for now</option>
                            @foreach($hierarchies as $hierarchy)
                                <option value="{{ $hierarchy->id }}" {{ (string) old('hierarchy_id', $user->hierarchy_id) === (string) $hierarchy->id ? 'selected' : '' }}>
                                    {{ ucfirst($hierarchy->type) }} · {{ $hierarchy->displayPath() }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-slate-500">If this user already leads a hierarchy, assign a replacement there before moving or demoting them.</p>
                        @error('hierarchy_id')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Message delivery override</span>
                        <select name="message_delivery_preference" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            <option value="">Use admin default</option>
                            @foreach($deliveryOptions as $value => $label)
                                <option value="{{ $value }}" {{ old('message_delivery_preference', $user->message_delivery_preference) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="flex items-start gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4 md:col-span-2">
                        <input type="hidden" name="message_delivery_preference_locked" value="0">
                        <input type="checkbox" name="message_delivery_preference_locked" value="1" {{ old('message_delivery_preference_locked', $user->message_delivery_preference_locked) ? 'checked' : '' }} class="mt-1 rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Lock delivery preference</p>
                            <p class="mt-1 text-sm text-slate-500">When locked, this user cannot change their own inbox/email preference.</p>
                        </div>
                    </label>
                </div>

                <div class="border-t border-slate-200 pt-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Security</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Change password</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Leave both password fields blank to keep the current password.</p>

                    <div class="mt-6 grid gap-5 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">New password</span>
                            <input type="password" name="password" id="password" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            @error('password')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Confirm new password</span>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        </label>
                    </div>
                </div>

                <div class="border-t border-slate-200 pt-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Enrollment</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Manage reading plans</h2>

                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        @forelse($readingPlans as $plan)
                            @php
                                $isAssigned = in_array($plan->id, old('reading_plans', $userPlanIds));
                                $userPlan = $user->readingPlans->where('id', $plan->id)->first();
                            @endphp
                            <label class="flex cursor-pointer items-start gap-4 rounded-[1.5rem] border p-5 transition {{ $isAssigned ? 'border-emerald-200 bg-emerald-50/60' : 'border-slate-200 bg-slate-50 hover:border-emerald-200 hover:bg-emerald-50/40' }}">
                                <input type="checkbox" name="reading_plans[]" value="{{ $plan->id }}" {{ $isAssigned ? 'checked' : '' }} class="mt-1 rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-semibold text-slate-900">{{ $plan->name }}</p>
                                        @if($userPlan)
                                            <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-emerald-700">Assigned</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">{{ $plan->description ?: 'No plan description yet.' }}</p>
                                    <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                                        <span class="rounded-full bg-white px-3 py-1">{{ $plan->type_label }}</span>
                                        <span class="rounded-full bg-white px-3 py-1">{{ $plan->cadence_description }}</span>
                                    </div>
                                    @if($userPlan)
                                        <p class="mt-3 text-xs text-slate-500">
                                            Joined {{ $userPlan->pivot->joined_date ? \Illuminate\Support\Carbon::parse($userPlan->pivot->joined_date)->format('M d, Y') : 'N/A' }}
                                            · Current day {{ $userPlan->pivot->current_day }}
                                        </p>
                                    @endif
                                </div>
                            </label>
                        @empty
                            <div class="md:col-span-2 rounded-[1.5rem] border border-dashed border-slate-200 px-5 py-12 text-center text-sm text-slate-500">
                                No reading plans are available yet.
                            </div>
                        @endforelse
                    </div>

                    @error('reading_plans')
                        <p class="mt-3 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
                    <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Update user
                    </button>
                </div>
            </form>
        </section>

        <aside class="space-y-6">
            <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Account snapshot</p>
                <div class="mt-4 space-y-4 text-sm text-slate-600">
                    <div class="rounded-[1.5rem] bg-slate-50 p-4">
                        <p class="font-semibold text-slate-900">Created</p>
                        <p class="mt-1">{{ $user->created_at->format('M d, Y') }}</p>
                    </div>
                    <div class="rounded-[1.5rem] bg-slate-50 p-4">
                        <p class="font-semibold text-slate-900">Current status</p>
                        <p class="mt-1">{{ $user->email_verified_at ? 'Active and verified' : 'Not yet verified' }}</p>
                    </div>
                    <div class="rounded-[1.5rem] bg-slate-50 p-4">
                        <p class="font-semibold text-slate-900">Assigned plans</p>
                        <p class="mt-1">{{ count($userPlanIds) }} active plan association(s).</p>
                    </div>
                    <div class="rounded-[1.5rem] bg-slate-50 p-4">
                        <p class="font-semibold text-slate-900">System access</p>
                        <p class="mt-1">{{ count($userSystemRoleIds) }} access role(s).</p>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-sky-700 p-6 text-white shadow-2xl shadow-slate-900/15">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-100">Reminder</p>
                <h2 class="mt-3 text-2xl font-semibold">Preserve real progress.</h2>
                <p class="mt-3 text-sm leading-6 text-slate-200">Plan assignments keep existing pivot data where possible, so edits here do not wipe out a user’s current day or streak unless you remove the plan entirely.</p>
            </section>
        </aside>
    </div>
</x-admin-layout>
