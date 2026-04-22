@props([
    'name',
    'size' => 'h-10 w-10',
    'rounded' => 'rounded-2xl',
    'bg' => 'bg-slate-900',
    'text' => 'text-white',
    'textSize' => 'text-sm',
])

@php
    $parts = collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()
        ->take(2);

    $initials = $parts->isNotEmpty()
        ? $parts->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('')
        : '?';
@endphp

<span
    {{ $attributes->merge([
        'class' => "inline-flex shrink-0 items-center justify-center {$size} {$rounded} {$bg} {$text} {$textSize} font-semibold",
    ]) }}
    aria-label="{{ $name }}"
    title="{{ $name }}"
>
    {{ $initials }}
</span>
