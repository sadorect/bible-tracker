<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Platform Access</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">System roles and permissions</h1>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
        <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Create access role</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">New system role</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Build permission bundles for people who help administer the platform without changing their hierarchy role.</p>
            </div>

            <form method="POST" action="{{ route('admin.system-roles.store') }}" class="mt-8 space-y-6">
                @csrf

                <div class="grid gap-5 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Role key</span>
                        <input type="text" name="slug" value="{{ old('slug') }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        <p class="mt-2 text-xs text-slate-500">Use lowercase letters, numbers, dashes, or underscores.</p>
                        @error('slug')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Display name</span>
                        <input type="text" name="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        @error('name')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>
                </div>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Description</span>
                    <textarea name="description" rows="3" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">{{ old('description') }}</textarea>
                </label>

                <div class="space-y-4">
                    <div>
                        <p class="text-sm font-medium text-slate-700">Permissions</p>
                        <p class="mt-1 text-sm text-slate-500">Choose the exact admin surfaces this role can use.</p>
                    </div>

                    @foreach($permissionGroups as $group => $permissions)
                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">{{ $group }}</p>
                            <div class="mt-4 grid gap-3">
                                @foreach($permissions as $slug => $permission)
                                    <label class="flex items-start gap-3 rounded-2xl bg-white px-4 py-3">
                                        <input type="checkbox" name="permissions[]" value="{{ $slug }}" {{ in_array($slug, old('permissions', []), true) ? 'checked' : '' }} class="mt-1 rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">{{ $permission['label'] }}</p>
                                            <p class="mt-1 text-sm text-slate-500">{{ $permission['description'] }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Create role
                    </button>
                </div>
            </form>
        </section>

        <section class="space-y-6">
            @foreach($roles as $role)
                <article class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
                    <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-2xl font-semibold text-slate-900">{{ $role->name }}</h2>
                                @if($role->is_system)
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Built in</span>
                                @endif
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $role->users->count() }} assigned</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-500">Key: <span class="font-mono text-slate-700">{{ $role->slug }}</span></p>
                            @if($role->description)
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $role->description }}</p>
                            @endif
                        </div>

                        @unless($role->is_system)
                            <form method="POST" action="{{ route('admin.system-roles.destroy', $role) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-medium text-rose-700 transition hover:bg-rose-100">
                                    Delete role
                                </button>
                            </form>
                        @endunless
                    </div>

                    <form method="POST" action="{{ route('admin.system-roles.update', $role) }}" class="mt-6 space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid gap-5 md:grid-cols-2">
                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">Display name</span>
                                <input type="text" name="name" value="{{ old('name', $role->name) }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            </label>

                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">Description</span>
                                <input type="text" name="description" value="{{ old('description', $role->description) }}" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            </label>
                        </div>

                        <div class="space-y-4">
                            @foreach($permissionGroups as $group => $permissions)
                                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">{{ $group }}</p>
                                    <div class="mt-4 grid gap-3">
                                        @foreach($permissions as $slug => $permission)
                                            <label class="flex items-start gap-3 rounded-2xl bg-white px-4 py-3">
                                                <input type="checkbox" name="permissions[]" value="{{ $slug }}" {{ $role->permissions->contains('name', $slug) ? 'checked' : '' }} class="mt-1 rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-900">{{ $permission['label'] }}</p>
                                                    <p class="mt-1 text-sm text-slate-500">{{ $permission['description'] }}</p>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                                Save role
                            </button>
                        </div>
                    </form>
                </article>
            @endforeach
        </section>
    </div>
</x-admin-layout>
