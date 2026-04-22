<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Bible Reading Tracker') }} · Admin</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none !important;}</style>
</head>
@php($user = auth()->user())
@php($unreadInboxCount = $user?->unreadInboxCount() ?? 0)
@php($unreadNotificationCount = $user?->unreadNotifications()->count() ?? 0)
<body
    class="bg-slate-100 font-sans text-slate-900 antialiased"
    x-data="{
        sidebarOpen: false,
        sidebarCollapsed: JSON.parse(localStorage.getItem('admin-sidebar-collapsed') ?? 'false'),
        toggleSidebarCollapse() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('admin-sidebar-collapsed', JSON.stringify(this.sidebarCollapsed));
        },
    }"
>
    <div class="min-h-screen">
        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden"></div>

        <aside
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                sidebarCollapsed ? 'lg:w-24' : 'lg:w-72',
            ]"
            class="fixed inset-y-0 left-0 z-50 flex w-72 transform flex-col border-r border-slate-200 bg-slate-950 text-slate-100 shadow-2xl shadow-slate-950/20 transition-all duration-300 ease-out"
        >
            <div class="flex h-20 items-center justify-between border-b border-white/10 px-6">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/20">
                        <i class="fas fa-shield-halved text-sm"></i>
                    </span>
                    <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>
                        <span class="block text-xs font-semibold uppercase tracking-[0.28em] text-emerald-300">Operations</span>
                        <span class="block text-lg font-semibold text-white">Admin Console</span>
                    </span>
                </a>

                <div class="flex items-center gap-2">
                    <button @click="toggleSidebarCollapse()" class="hidden rounded-xl p-2 text-slate-400 hover:bg-white/5 hover:text-white lg:inline-flex" :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                        <i class="fas" :class="sidebarCollapsed ? 'fa-angles-right' : 'fa-angles-left'"></i>
                    </button>
                    <button @click="sidebarOpen = false" class="rounded-xl p-2 text-slate-400 hover:bg-white/5 hover:text-white lg:hidden">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="flex flex-1 flex-col overflow-y-auto px-4 py-6">
                <div class="space-y-8">
                    <div x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms class="rounded-[1.75rem] border border-white/10 bg-white/5 px-5 py-5">
                        <p class="text-xs uppercase tracking-[0.24em] text-slate-400">Signed in as</p>
                        <p class="mt-3 text-lg font-semibold text-white">{{ $user->name }}</p>
                        <p class="mt-1 text-sm text-slate-400">{{ $user->email }}</p>
                    </div>

                    <nav class="space-y-1.5">
                        @if($user->hasPermissionTo('dashboard.view'))
                            <a href="{{ route('admin.dashboard') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('admin.dashboard') ? 'bg-white text-slate-950 shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 px-4 py-3'">
                                <i class="fas fa-chart-pie w-5 text-center flex-shrink-0"></i>
                                <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>Dashboard</span>
                                <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Home</span>
                            </a>
                        @endif
                        @if($user->hasPermissionTo('plans.manage'))
                            <a href="{{ route('admin.reading-plans.index') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('admin.reading-plans.*') ? 'bg-white text-slate-950 shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 px-4 py-3'">
                                <i class="fas fa-book-open-reader w-5 text-center flex-shrink-0"></i>
                                <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>Reading Plans</span>
                                <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Plans</span>
                            </a>
                        @endif
                        @if($user->hasPermissionTo('users.manage'))
                            <a href="{{ route('admin.users.index') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('admin.users.*') ? 'bg-white text-slate-950 shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 px-4 py-3'">
                                <i class="fas fa-users w-5 text-center flex-shrink-0"></i>
                                <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>Users</span>
                                <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Users</span>
                            </a>
                        @endif
                        @if($user->hasPermissionTo('hierarchies.manage'))
                            <a href="{{ route('admin.hierarchies.index') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('admin.hierarchies.index') ? 'bg-white text-slate-950 shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 px-4 py-3'">
                                <i class="fas fa-sitemap w-5 text-center flex-shrink-0"></i>
                                <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>Hierarchies</span>
                                <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Groups</span>
                            </a>
                            <a href="{{ route('admin.hierarchies.tree') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('admin.hierarchies.tree') || request()->routeIs('admin.hierarchies.show') ? 'bg-white text-slate-950 shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 pl-8 py-3'">
                                <i class="fas fa-diagram-project w-5 text-center flex-shrink-0"></i>
                                <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>Tree view</span>
                                <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Tree</span>
                            </a>
                        @endif
                        @if($user->hasPermissionTo('progress.view'))
                            <a href="{{ route('admin.progress.index') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('admin.progress.*') ? 'bg-white text-slate-950 shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 px-4 py-3'">
                                <i class="fas fa-chart-line w-5 text-center flex-shrink-0"></i>
                                <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>Progress Reports</span>
                                <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Reports</span>
                            </a>
                        @endif
                        @if($user->hasPermissionTo('audits.view'))
                            <a href="{{ route('admin.audits.index') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('admin.audits.*') ? 'bg-white text-slate-950 shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 px-4 py-3'">
                                <i class="fas fa-clipboard-list w-5 text-center flex-shrink-0"></i>
                                <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>Audit Trail</span>
                                <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Audit</span>
                            </a>
                        @endif
                        @if($user->hasPermissionTo('automation.manage'))
                            <a href="{{ route('admin.automation.index') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('admin.automation.*') ? 'bg-white text-slate-950 shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 px-4 py-3'">
                                <i class="fas fa-bolt w-5 text-center flex-shrink-0"></i>
                                <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>Automation</span>
                                <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Auto</span>
                            </a>
                        @endif
                        <a href="{{ route('notifications.index') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('notifications.*') ? 'bg-white text-slate-950 shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 px-4 py-3'">
                            <i class="fas fa-bell w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms class="flex items-center gap-2">
                                Alerts
                                @if($unreadNotificationCount > 0)
                                    <span class="rounded-full bg-amber-500 px-2 py-0.5 text-xs font-semibold text-white">{{ $unreadNotificationCount }}</span>
                                @endif
                            </span>
                            <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Alerts</span>
                        </a>
                        <a href="{{ route('manual.index') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('manual.*') ? 'bg-white text-slate-950 shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 px-4 py-3'">
                            <i class="fas fa-book w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>Manual</span>
                            <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Guide</span>
                        </a>
                        <a href="{{ route('messages.inbox') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('messages.inbox') || request()->routeIs('messages.index') || request()->routeIs('messages.sent') || request()->routeIs('messages.show') ? 'bg-white text-slate-950 shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 px-4 py-3'">
                            <i class="fas fa-inbox w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms class="flex items-center gap-2">
                                Message Centre
                                @if($unreadInboxCount > 0)
                                    <span class="rounded-full bg-emerald-500 px-2 py-0.5 text-xs font-semibold text-white">{{ $unreadInboxCount }}</span>
                                @endif
                            </span>
                            <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Inbox</span>
                        </a>
                        <a href="{{ route('messages.compose') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('messages.compose') ? 'bg-white text-slate-950 shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 pl-8 py-3'">
                            <i class="fas fa-pen-to-square w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>Compose message</span>
                            <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Write</span>
                        </a>
                        @if($user->hasPermissionTo('messages.manage_templates'))
                            <a href="{{ route('admin.messages.settings') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('admin.messages.settings*') || request()->routeIs('admin.messages.templates.*') ? 'bg-white text-slate-950 shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 pl-8 py-3'">
                                <i class="fas fa-gear w-5 text-center flex-shrink-0"></i>
                                <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>Messaging settings</span>
                                <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Setup</span>
                            </a>
                        @endif
                        @if($user->hasPermissionTo('system_roles.manage'))
                            <a href="{{ route('admin.system-roles.index') }}" class="flex rounded-2xl text-sm font-medium transition {{ request()->routeIs('admin.system-roles.*') ? 'bg-white text-slate-950 shadow-lg shadow-black/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}" :class="sidebarCollapsed ? 'flex-col items-center justify-center gap-0.5 px-2 py-2.5' : 'flex-row items-center gap-3 px-4 py-3'">
                                <i class="fas fa-key w-5 text-center flex-shrink-0"></i>
                                <span x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>System Access</span>
                                <span x-show="sidebarCollapsed" class="text-[9px] font-semibold leading-none tracking-wide">Access</span>
                            </a>
                        @endif
                    </nav>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-emerald-400/20 bg-emerald-500/10 px-5 py-4 text-sm text-emerald-100" x-show="!sidebarCollapsed" x-transition.opacity.duration.200ms>
                    <p class="font-semibold text-white">Mission snapshot</p>
                    <p class="mt-2 leading-6">Guide cohorts through training, custom reading cadences, and refresh breaks while giving leaders clear visibility down the chain.</p>
                </div>
            </div>
        </aside>

        <div :class="sidebarCollapsed ? 'lg:pl-24' : 'lg:pl-72'" class="transition-all duration-300 ease-out">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-slate-100/90 backdrop-blur">
                <div class="mx-auto flex min-h-20 w-full max-w-screen-2xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8 xl:px-10">
                    <div class="flex min-w-0 items-center gap-3">
                        <button @click="sidebarOpen = true" class="flex-shrink-0 rounded-2xl border border-slate-200 bg-white p-3 text-slate-500 shadow-sm shadow-slate-900/5 hover:text-slate-900 lg:hidden">
                            <i class="fas fa-bars"></i>
                        </button>
                        <button @click="toggleSidebarCollapse()" class="hidden flex-shrink-0 rounded-2xl border border-slate-200 bg-white p-3 text-slate-500 shadow-sm shadow-slate-900/5 hover:text-slate-900 lg:inline-flex" :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                            <i class="fas" :class="sidebarCollapsed ? 'fa-angles-right' : 'fa-angles-left'"></i>
                        </button>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Command Center</p>
                            @isset($header)
                                <div class="mt-1 min-w-0 text-slate-900">{{ $header }}</div>
                            @else
                                <h1 class="text-lg font-semibold text-slate-900">Coordinate the reading movement.</h1>
                            @endisset
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('dashboard') }}" class="hidden rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm shadow-slate-900/5 transition hover:border-slate-300 hover:text-slate-900 sm:inline-flex">
                            User dashboard
                        </a>
                        <a href="{{ route('notifications.index') }}" class="hidden rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm shadow-slate-900/5 transition hover:border-slate-300 hover:text-slate-900 sm:inline-flex">
                            Alerts
                            @if($unreadNotificationCount > 0)
                                <span class="ml-2 rounded-full bg-amber-500 px-2 py-0.5 text-xs font-semibold text-white">{{ $unreadNotificationCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('manual.index') }}" class="hidden rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm shadow-slate-900/5 transition hover:border-slate-300 hover:text-slate-900 sm:inline-flex">
                            Manual
                        </a>

                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm shadow-slate-900/5 transition hover:border-slate-300">
                                <x-user-avatar :name="$user->name" class="h-10 w-10 rounded-2xl bg-slate-950 text-white" />
                                <div class="hidden text-left sm:block">
                                    <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                                    <p class="text-xs text-slate-500">Administrator</p>
                                </div>
                                <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                            </button>

                            <div x-cloak x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-3 w-56 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10">
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                                    <i class="fas fa-user-gear w-4 text-center text-slate-400"></i>
                                    Profile settings
                                </a>
                                <a href="{{ route('notifications.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                                    <i class="fas fa-bell w-4 text-center text-slate-400"></i>
                                    Alerts
                                </a>
                                <a href="{{ route('manual.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                                    <i class="fas fa-book w-4 text-center text-slate-400"></i>
                                    User manual
                                </a>
                                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                                    <i class="fas fa-house w-4 text-center text-slate-400"></i>
                                    Member dashboard
                                </a>
                                <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-200">
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

    @stack('scripts')
</body>
</html>
