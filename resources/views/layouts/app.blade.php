<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Bible Reading Tracker') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>[x-cloak]{display:none !important;}</style>
</head>
@php($user = auth()->user())
@php($unreadInboxCount = $user?->unreadInboxCount() ?? 0)
<body
    class="bg-stone-100 font-['Instrument_Sans'] text-slate-900 antialiased"
    x-data="{
        sidebarOpen: false,
        sidebarCollapsed: JSON.parse(localStorage.getItem('member-sidebar-collapsed') ?? 'false'),
        toggleSidebarCollapse() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('member-sidebar-collapsed', JSON.stringify(this.sidebarCollapsed));
        },
    }"
>
    <div class="min-h-screen">
        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-900/60 lg:hidden"></div>

        <aside
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                sidebarCollapsed ? 'lg:w-24' : 'lg:w-72',
            ]"
            class="fixed inset-y-0 left-0 z-50 flex transform flex-col border-r border-stone-200 bg-white/95 shadow-2xl shadow-slate-900/10 backdrop-blur transition-all duration-300 ease-out"
        >
            <div class="flex h-20 items-center justify-between border-b border-stone-200 px-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/20">
                        <i class="fas fa-book-bible text-sm"></i>
                    </span>
                    <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>
                        <span class="block text-sm font-semibold uppercase tracking-[0.24em] text-emerald-600">Bible Journey</span>
                        <span class="block text-lg font-semibold text-slate-900">Member Space</span>
                    </span>
                </a>

                <div class="flex items-center gap-2">
                    <button @click="toggleSidebarCollapse()" class="hidden rounded-xl p-2 text-slate-400 hover:bg-stone-100 hover:text-slate-600 lg:inline-flex" :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                        <i class="fas" :class="sidebarCollapsed ? 'fa-angles-right' : 'fa-angles-left'"></i>
                    </button>
                    <button @click="sidebarOpen = false" class="rounded-xl p-2 text-slate-400 hover:bg-stone-100 hover:text-slate-600 lg:hidden">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="flex flex-1 flex-col overflow-y-auto px-4 py-6">
                <div class="space-y-8">
                    <div x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms class="rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-700 px-5 py-6 text-white shadow-xl shadow-slate-900/15">
                        <p class="text-xs uppercase tracking-[0.3em] text-emerald-200">Walking Together</p>
                        <p class="mt-3 text-xl font-semibold leading-tight">{{ $user->name }}</p>
                        <div class="mt-4 inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-medium capitalize text-emerald-50">
                            {{ str_replace('_', ' ', $user->role) }}
                        </div>
                    </div>

                    <nav class="space-y-1.5">
                        <a href="{{ route('dashboard') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/10' : 'text-slate-600 hover:bg-stone-100 hover:text-slate-900' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 px-4 py-3'">
                            <i class="fas fa-house w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>Dashboard</span>
                            <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Home</span>
                        </a>
                        <a href="{{ route('reading-plans.index') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('reading-plans.*') ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/10' : 'text-slate-600 hover:bg-stone-100 hover:text-slate-900' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 px-4 py-3'">
                            <i class="fas fa-book-open w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>Reading Plans</span>
                            <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Plans</span>
                        </a>
                        <a href="{{ route('reading.progress') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('reading.progress') || request()->routeIs('progress.view') ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/10' : 'text-slate-600 hover:bg-stone-100 hover:text-slate-900' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 px-4 py-3'">
                            <i class="fas fa-chart-line w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>Progress</span>
                            <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Stats</span>
                        </a>
                        <a href="{{ route('reading-history') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('reading-history') ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/10' : 'text-slate-600 hover:bg-stone-100 hover:text-slate-900' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 px-4 py-3'">
                            <i class="fas fa-clock-rotate-left w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>Reading History</span>
                            <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">History</span>
                        </a>
                        <a href="{{ route('messages.inbox') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('messages.inbox') || request()->routeIs('messages.index') || request()->routeIs('messages.sent') || request()->routeIs('messages.show') ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/10' : 'text-slate-600 hover:bg-stone-100 hover:text-slate-900' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 px-4 py-3'">
                            <i class="fas fa-inbox w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms class="flex items-center gap-2">
                                Message Centre
                                @if($unreadInboxCount > 0)
                                    <span class="rounded-full bg-emerald-500 px-2 py-0.5 text-xs font-semibold text-white">{{ $unreadInboxCount }}</span>
                                @endif
                            </span>
                            <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Inbox</span>
                        </a>
                        <a href="{{ route('messages.compose') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('messages.compose') ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/10' : 'text-slate-600 hover:bg-stone-100 hover:text-slate-900' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 pl-8 py-3'">
                            <i class="fas fa-pen-to-square w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>Compose message</span>
                            <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Write</span>
                        </a>
                        @if($user->canManageHierarchy())
                            <a href="{{ route('hierarchy.manage') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('hierarchy.manage') ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/10' : 'text-slate-600 hover:bg-stone-100 hover:text-slate-900' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 px-4 py-3'">
                                <i class="fas fa-users w-5 text-center flex-shrink-0"></i>
                                <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>Manage Team</span>
                                <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Team</span>
                            </a>
                            <a href="{{ route('leader.hierarchies.tree') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('leader.hierarchies.*') ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/10' : 'text-slate-600 hover:bg-stone-100 hover:text-slate-900' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 pl-8 py-3'">
                                <i class="fas fa-diagram-project w-5 text-center flex-shrink-0"></i>
                                <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>My branch</span>
                                <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Branch</span>
                            </a>
                        @endif
                        @if($user->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="flex rounded-2xl text-sm font-medium transition text-slate-600 hover:bg-stone-100 hover:text-slate-900" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 px-4 py-3'">
                                <i class="fas fa-shield-halved w-5 text-center flex-shrink-0"></i>
                                <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>Admin Console</span>
                                <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Admin</span>
                            </a>
                        @endif
                    </nav>
                </div>

                <div x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms class="mt-8 rounded-3xl border border-stone-200 bg-stone-50 px-5 py-4 text-sm text-slate-600">
                    <p class="font-semibold text-slate-900">Daily rhythm</p>
                    <p class="mt-2 leading-relaxed">Train, read faithfully for ten days, pause for a refresh-and-prayer break, then continue the journey.</p>
                </div>
            </div>
        </aside>

        <div :class="sidebarCollapsed ? 'lg:pl-24' : 'lg:pl-72'" class="transition-all duration-300 ease-out">
            <header class="sticky top-0 z-30 border-b border-stone-200 bg-stone-100/90 backdrop-blur">
                <div class="mx-auto flex min-h-20 w-full max-w-screen-2xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8 xl:px-10">
                    <div class="flex min-w-0 items-center gap-3">
                        <button @click="sidebarOpen = true" class="flex-shrink-0 rounded-2xl border border-stone-200 bg-white p-3 text-slate-500 shadow-sm shadow-slate-900/5 hover:text-slate-900 lg:hidden">
                            <i class="fas fa-bars"></i>
                        </button>
                        <button @click="toggleSidebarCollapse()" class="hidden flex-shrink-0 rounded-2xl border border-stone-200 bg-white p-3 text-slate-500 shadow-sm shadow-slate-900/5 hover:text-slate-900 lg:inline-flex" :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                            <i class="fas" :class="sidebarCollapsed ? 'fa-angles-right' : 'fa-angles-left'"></i>
                        </button>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Scripture Tracker</p>
                            @isset($header)
                                <div class="mt-1 min-w-0 text-slate-900">{{ $header }}</div>
                            @else
                                <h1 class="text-lg font-semibold text-slate-900">Stay steady, stay encouraged.</h1>
                            @endisset
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('profile.edit') }}" class="hidden rounded-2xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm shadow-slate-900/5 transition hover:border-stone-300 hover:text-slate-900 sm:inline-flex">
                            Profile
                        </a>

                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center gap-3 rounded-2xl border border-stone-200 bg-white px-3 py-2 shadow-sm shadow-slate-900/5 transition hover:border-stone-300">
                                <img class="h-10 w-10 rounded-2xl object-cover" src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=0f172a&color=fff" alt="User avatar">
                                <div class="hidden text-left sm:block">
                                    <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                                    <p class="text-xs capitalize text-slate-500">{{ str_replace('_', ' ', $user->role) }}</p>
                                </div>
                                <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                            </button>

                            <div x-cloak x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-3 w-56 overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-2xl shadow-slate-900/10">
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-slate-600 transition hover:bg-stone-50 hover:text-slate-900">
                                    <i class="fas fa-user-gear w-4 text-center text-slate-400"></i>
                                    Profile settings
                                </a>
                                @if($user->isAdmin())
                                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-slate-600 transition hover:bg-stone-50 hover:text-slate-900">
                                        <i class="fas fa-shield-halved w-4 text-center text-slate-400"></i>
                                        Admin dashboard
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('logout') }}" class="border-t border-stone-200">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-3 px-4 py-3 text-sm text-rose-600 transition hover:bg-rose-50">
                                        <i class="fas fa-right-from-bracket w-4 text-center"></i>
                                        Sign out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-screen-2xl px-4 py-6 sm:px-6 lg:px-8 xl:px-10">
                @if(session('success'))
                    <div class="mb-6 rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm shadow-emerald-900/5">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 rounded-3xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800 shadow-sm shadow-rose-900/5">
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('info'))
                    <div class="mb-6 rounded-3xl border border-sky-200 bg-sky-50 px-5 py-4 text-sm text-sky-800 shadow-sm shadow-sky-900/5">
                        {{ session('info') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const parseColumnConfig = (value) => {
                if (! value) {
                    return {};
                }

                try {
                    return JSON.parse(value);
                } catch (error) {
                    return {};
                }
            };

            document.querySelectorAll('[data-table-columns]').forEach((panel) => {
                const storageKey = `table-columns:${panel.dataset.tableColumns}`;
                const toggleInputs = Array.from(panel.querySelectorAll('[data-column-toggle]'));

                if (toggleInputs.length === 0) {
                    return;
                }

                const columnKeys = toggleInputs.map((input) => input.dataset.columnToggle);
                const tableRoot = panel.querySelector('[data-table-columns-root]') ?? panel;

                const defaultSettings = () => {
                    const settings = {};

                    [
                        panel.dataset.defaultColumns,
                        window.innerWidth >= 768 ? panel.dataset.defaultColumnsMd : null,
                        window.innerWidth >= 1024 ? panel.dataset.defaultColumnsLg : null,
                        window.innerWidth >= 1280 ? panel.dataset.defaultColumnsXl : null,
                    ].forEach((config) => Object.assign(settings, parseColumnConfig(config)));

                    columnKeys.forEach((key) => {
                        if (! Object.prototype.hasOwnProperty.call(settings, key)) {
                            settings[key] = true;
                        }
                    });

                    return settings;
                };

                const loadSettings = () => {
                    const defaults = defaultSettings();
                    const saved = localStorage.getItem(storageKey);

                    if (! saved) {
                        return defaults;
                    }

                    try {
                        return { ...defaults, ...JSON.parse(saved) };
                    } catch (error) {
                        return defaults;
                    }
                };

                const persistSettings = (settings) => {
                    localStorage.setItem(storageKey, JSON.stringify(settings));
                };

                const applySettings = (settings) => {
                    columnKeys.forEach((key) => {
                        tableRoot.querySelectorAll(`[data-column="${key}"]`).forEach((element) => {
                            element.classList.toggle('hidden', ! settings[key]);
                        });
                    });
                };

                const syncInputs = (settings) => {
                    toggleInputs.forEach((input) => {
                        input.checked = Boolean(settings[input.dataset.columnToggle]);
                    });
                };

                let settings = loadSettings();
                applySettings(settings);
                syncInputs(settings);

                toggleInputs.forEach((input) => {
                    input.addEventListener('change', () => {
                        settings[input.dataset.columnToggle] = input.checked;
                        persistSettings(settings);
                        applySettings(settings);
                    });
                });

                panel.querySelector('[data-table-columns-reset]')?.addEventListener('click', () => {
                    settings = defaultSettings();
                    persistSettings(settings);
                    applySettings(settings);
                    syncInputs(settings);
                });
            });
        });
    </script>

    @livewireScripts
</body>
</html>
