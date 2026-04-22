<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Bible Reading Tracker') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
@php($user = auth()->user())
<body class="bg-stone-100 font-sans text-slate-900 antialiased" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen">
        <div
            x-show="sidebarOpen"
            x-transition.opacity
            @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-900/60 lg:hidden"
        ></div>

        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-50 w-72 transform border-r border-stone-200 bg-white/95 shadow-2xl shadow-slate-900/10 backdrop-blur transition duration-300 ease-out"
        >
            <div class="flex h-20 items-center justify-between border-b border-stone-200 px-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/20">
                        <i class="fas fa-book-bible text-sm"></i>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold uppercase tracking-[0.24em] text-emerald-600">Bible Journey</span>
                        <span class="block text-lg font-semibold text-slate-900">Member Space</span>
                    </span>
                </a>

                <button @click="sidebarOpen = false" class="rounded-xl p-2 text-slate-400 hover:bg-stone-100 hover:text-slate-600 lg:hidden">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="flex h-[calc(100vh-5rem)] flex-col justify-between px-4 py-6">
                <div class="space-y-8">
                    <div class="rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-700 px-5 py-6 text-white shadow-xl shadow-slate-900/15">
                        <p class="text-xs uppercase tracking-[0.3em] text-emerald-200">Walking Together</p>
                        <p class="mt-3 text-xl font-semibold leading-tight">{{ $user->name }}</p>
                        <div class="mt-4 inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-medium capitalize text-emerald-50">
                            {{ str_replace('_', ' ', $user->role) }}
                        </div>
                    </div>

                    <nav class="space-y-1.5">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/10' : 'text-slate-600 hover:bg-stone-100 hover:text-slate-900' }}">
                            <i class="fas fa-house w-5 text-center"></i>
                            Dashboard
                        </a>
                        <a href="{{ route('reading-plans.index') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('reading-plans.*') ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/10' : 'text-slate-600 hover:bg-stone-100 hover:text-slate-900' }}">
                            <i class="fas fa-book-open w-5 text-center"></i>
                            Reading Plans
                        </a>
                        <a href="{{ route('reading.progress') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('reading.progress') || request()->routeIs('progress.view') ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/10' : 'text-slate-600 hover:bg-stone-100 hover:text-slate-900' }}">
                            <i class="fas fa-chart-line w-5 text-center"></i>
                            Progress
                        </a>
                        <a href="{{ route('reading-history') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('reading-history') ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/10' : 'text-slate-600 hover:bg-stone-100 hover:text-slate-900' }}">
                            <i class="fas fa-clock-rotate-left w-5 text-center"></i>
                            Reading History
                        </a>
                        @if($user->canManageHierarchy())
                            <a href="{{ route('hierarchy.manage') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ request()->routeIs('hierarchy.manage') ? 'bg-slate-900 text-white shadow-lg shadow-slate-900/10' : 'text-slate-600 hover:bg-stone-100 hover:text-slate-900' }}">
                                <i class="fas fa-users w-5 text-center"></i>
                                Manage Team
                            </a>
                        @endif
                        @if($user->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition text-slate-600 hover:bg-stone-100 hover:text-slate-900">
                                <i class="fas fa-shield-halved w-5 text-center"></i>
                                Admin Console
                            </a>
                        @endif
                    </nav>
                </div>

                <div class="rounded-3xl border border-stone-200 bg-stone-50 px-5 py-4 text-sm text-slate-600">
                    <p class="font-semibold text-slate-900">Daily rhythm</p>
                    <p class="mt-2 leading-relaxed">Train, read faithfully for ten days, pause for a refresh-and-prayer break, then continue the journey.</p>
                </div>
            </div>
        </aside>

        <div class="lg:pl-72">
            <header class="sticky top-0 z-30 border-b border-stone-200 bg-stone-100/90 backdrop-blur">
                <div class="mx-auto flex h-20 w-full max-w-screen-2xl items-center justify-between px-4 sm:px-6 lg:px-8 xl:px-10">
                    <div class="flex items-center gap-3">
                        <button @click="sidebarOpen = true" class="rounded-2xl border border-stone-200 bg-white p-3 text-slate-500 shadow-sm shadow-slate-900/5 hover:text-slate-900 lg:hidden">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Scripture Tracker</p>
                            <h1 class="text-lg font-semibold text-slate-900">Stay steady, stay encouraged.</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('profile.edit') }}" class="hidden rounded-2xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm shadow-slate-900/5 transition hover:border-stone-300 hover:text-slate-900 sm:inline-flex">
                            Profile
                        </a>

                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center gap-3 rounded-2xl border border-stone-200 bg-white px-3 py-2 shadow-sm shadow-slate-900/5 transition hover:border-stone-300">
                                <x-user-avatar :name="$user->name" class="h-10 w-10 rounded-2xl bg-slate-900 text-white" />
                                <div class="hidden text-left sm:block">
                                    <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                                    <p class="text-xs capitalize text-slate-500">{{ str_replace('_', ' ', $user->role) }}</p>
                                </div>
                                <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                            </button>

                            <div
                                x-show="open"
                                @click.away="open = false"
                                x-transition
                                class="absolute right-0 mt-3 w-56 overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-2xl shadow-slate-900/10"
                            >
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

                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
