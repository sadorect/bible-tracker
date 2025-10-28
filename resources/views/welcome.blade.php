<x-layouts.marketing>

<!-- Hero Section -->
<section class="relative overflow-hidden py-24 lg:py-36 bg-gradient-to-br from-teal-50 via-emerald-50 to-amber-100 dark:from-gray-900 dark:via-teal-900 dark:to-emerald-900">
    <!-- Decorative Mesh -->
    <div class="mesh absolute inset-0 opacity-70 pointer-events-none"></div>
    <div class="absolute -top-24 -left-24 w-80 h-80 bg-emerald-300/30 rounded-full blur-3xl animate-[floaty_6s_ease-in-out_infinite]"></div>
    <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-amber-300/30 rounded-full blur-3xl animate-[floaty_7s_ease-in-out_infinite]"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/80 dark:bg-gray-800/70 border border-emerald-200/60 dark:border-emerald-800/50 text-emerald-700 dark:text-emerald-300 text-sm font-medium">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a1 1 0 01.894.553l1.948 3.897 4.301.625a1 1 0 01.554 1.706l-3.11 3.033.734 4.28a1 1 0 01-1.451 1.054L12 15.898l-3.87 2.03a1 1 0 01-1.451-1.054l.734-4.28-3.11-3.033A1 1 0 014.858 7.07l4.3-.625L11.106 2.553A1 1 0 0112 2z"/></svg>
                Build a life-giving habit
            </span>
            <h1 class="mt-6 text-5xl md:text-7xl font-extrabold text-gray-900 dark:text-white tracking-tight drop-shadow-sm">
                Read the Bible with
                <span class="bg-gradient-to-r from-teal-600 to-emerald-500 bg-clip-text text-transparent">clarity</span>
                and
                <span class="bg-gradient-to-r from-amber-500 to-orange-500 bg-clip-text text-transparent">consistency</span>
            </h1>
            <p class="mt-6 text-xl md:text-2xl text-gray-700 dark:text-gray-200 max-w-3xl mx-auto leading-relaxed">
                Structured plans, visual progress, and a supportive community to help you stay rooted in Scripture—day after day.
            </p>
            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                @guest
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-4 rounded-xl font-semibold shadow-xl hover:shadow-2xl transition">
                        Start your journey
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                    <a href="#features" class="inline-flex items-center gap-2 border-2 border-emerald-600 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-600 hover:text-white px-8 py-4 rounded-xl font-semibold transition">
                        Explore features
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-4 rounded-xl font-semibold shadow-xl hover:shadow-2xl transition">
                        Continue reading
                    </a>
                @endguest
            </div>
            <!-- Stats -->
            <div class="mt-14 grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                <div class="bg-white/80 dark:bg-gray-900/50 backdrop-blur rounded-2xl p-8 border border-white/50 dark:border-gray-800 shadow-lg">
                    <div class="text-4xl font-extrabold text-teal-600 dark:text-teal-400">10K+</div>
                    <div class="mt-1 text-gray-700 dark:text-gray-300">Active readers</div>
                </div>
                <div class="bg-white/80 dark:bg-gray-900/50 backdrop-blur rounded-2xl p-8 border border-white/50 dark:border-gray-800 shadow-lg">
                    <div class="text-4xl font-extrabold text-emerald-600 dark:text-emerald-400">25+</div>
                    <div class="mt-1 text-gray-700 dark:text-gray-300">Reading plans</div>
                </div>
                <div class="bg-white/80 dark:bg-gray-900/50 backdrop-blur rounded-2xl p-8 border border-white/50 dark:border-gray-800 shadow-lg">
                    <div class="text-4xl font-extrabold text-amber-600 dark:text-amber-400">1M+</div>
                    <div class="mt-1 text-gray-700 dark:text-gray-300">Chapters read</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-24 bg-gradient-to-b from-white via-emerald-50 to-amber-50 dark:from-gray-950 dark:via-teal-950 dark:to-emerald-950">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-4xl md:text-5xl font-extrabold text-gray-900 dark:text-white tracking-tight">Designed for daily faithfulness</h2>
            <p class="mt-4 text-lg text-gray-700 dark:text-gray-300 max-w-3xl mx-auto">Everything you need to build a consistent habit—without losing the joy of discovery.</p>
        </div>
        <div class="mt-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
            @php
                $features = [
                    [
                        'icon' => '<svg class="w-12 h-12 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6v6l4 2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="10" /></svg>',
                        'title' => 'Purposeful Plans',
                        'desc' => 'Choose classic or thematic plans—or create your own—to match your season and schedule.',
                        'bg' => 'bg-teal-50 dark:bg-teal-900/30'
                    ],
                    [
                        'icon' => '<svg class="w-12 h-12 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                        'title' => 'Progress You Can See',
                        'desc' => 'Streaks, milestones, and gentle reminders help you stay on course without pressure.',
                        'bg' => 'bg-emerald-50 dark:bg-emerald-900/30'
                    ],
                    [
                        'icon' => '<svg class="w-12 h-12 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87M16 3.13a4 4 0 010 7.75M8 3.13a4 4 0 000 7.75" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                        'title' => 'Clans & Community',
                        'desc' => 'Read with others, share insights, and encourage your clan—growth is better together.',
                        'bg' => 'bg-amber-50 dark:bg-amber-900/30'
                    ],
                    [
                        'icon' => '<svg class="w-12 h-12 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="10" /></svg>',
                        'title' => 'Kind Reminders',
                        'desc' => 'Gentle nudges at your preferred time—because habits form with small steps.',
                        'bg' => 'bg-teal-50 dark:bg-teal-900/30'
                    ],
                    [
                        'icon' => '<svg class="w-12 h-12 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20l9-5-9-5-9 5 9 5z" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 12V4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                        'title' => 'Growth Insights',
                        'desc' => 'Simple visuals that reflect your journey—so you can celebrate progress, not just check boxes.',
                        'bg' => 'bg-emerald-50 dark:bg-emerald-900/30'
                    ],
                    [
                        'icon' => '<svg class="w-12 h-12 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="10" /></svg>',
                        'title' => 'Mobile Friendly',
                        'desc' => 'Fast and accessible on every device—your plan, always within reach.',
                        'bg' => 'bg-amber-50 dark:bg-amber-900/30'
                    ],
                ];
            @endphp
            @foreach($features as $feature)
                <div class="{{ $feature['bg'] }} rounded-2xl p-10 shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-200 group">
                    <div class="flex items-center justify-center mb-6">
                        {!! $feature['icon'] !!}
                    </div>
                    <h3 class="text-2xl font-bold mb-3 text-gray-900 dark:text-white group-hover:text-emerald-600 transition">
                        {{ $feature['title'] }}
                    </h3>
                    <p class="text-lg text-gray-700 dark:text-gray-200">{{ $feature['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- How it works -->
<section id="how" class="py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid gap-12 md:grid-cols-3">
        <div class="rounded-2xl p-8 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 flex items-center justify-center font-bold">1</div>
            <h3 class="mt-6 text-2xl font-bold">Pick a plan</h3>
            <p class="mt-2 text-gray-700 dark:text-gray-300">Start with a classic plan or create your own path through Scripture.</p>
        </div>
        <div class="rounded-2xl p-8 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 flex items-center justify-center font-bold">2</div>
            <h3 class="mt-6 text-2xl font-bold">Read each day</h3>
            <p class="mt-2 text-gray-700 dark:text-gray-300">Receive gentle reminders and mark chapters as you go.</p>
        </div>
        <div class="rounded-2xl p-8 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 flex items-center justify-center font-bold">3</div>
            <h3 class="mt-6 text-2xl font-bold">Grow together</h3>
            <p class="mt-2 text-gray-700 dark:text-gray-300">Join a clan, share insights, and encourage each other.</p>
        </div>
    </div>
</section>

<!-- Community highlight -->
<section id="community" class="py-24 bg-gradient-to-tr from-emerald-50 to-amber-50 dark:from-teal-950 dark:to-emerald-900/40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid gap-12 md:grid-cols-2 items-center">
        <div>
            <h2 class="text-4xl font-extrabold">Read with your clan</h2>
            <p class="mt-4 text-lg text-gray-700 dark:text-gray-300">Create or join a clan to read together, track group progress, and strengthen each other in the Word.</p>
            <div class="mt-8 flex gap-4">
                <a href="{{ route('register') }}" class="inline-flex items-center px-6 py-3 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700">Create a clan</a>
                <a href="#features" class="inline-flex items-center px-6 py-3 rounded-xl border-2 border-emerald-600 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-600 hover:text-white">See features</a>
            </div>
        </div>
        <div class="relative">
            <div class="absolute -inset-4 bg-gradient-to-r from-emerald-400/20 to-amber-400/20 blur-2xl rounded-3xl"></div>
            <div class="relative rounded-3xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xl p-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold">A</div>
                    <div>
                        <div class="font-semibold">Abigail — Morning Readers</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Streak 21 days</div>
                    </div>
                </div>
                <blockquote class="mt-4 text-lg text-gray-800 dark:text-gray-200">
                    “Reading with my clan has made daily time in the Word a joy. The gentle reminders and shared wins keep us going.”
                </blockquote>
            </div>
        </div>
    </div>
</section>

<!-- Simple fade-in animation (kept lightweight) -->
<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(40px);}
    to { opacity: 1; transform: none;}
}
.animate-fade-in {
    animation: fade-in 1s cubic-bezier(.4,0,.2,1) both;
}
.animate-fade-in.delay-100 { animation-delay: .1s; }
.animate-fade-in.delay-200 { animation-delay: .2s; }
.animate-fade-in.delay-300 { animation-delay: .3s; }
</style>

</x-layouts.marketing>