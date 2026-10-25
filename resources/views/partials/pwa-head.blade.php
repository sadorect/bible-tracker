{{-- Shared PWA / icon head tags. Included by every layout so the installed app
     behaves the same wherever the user first lands. --}}
<link rel="manifest" href="/manifest.json">

{{-- Browser UI colour. The darker value keeps the status bar readable in dark mode. --}}
<meta name="theme-color" content="#059669" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#065f46" media="(prefers-color-scheme: dark)">

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">

{{-- iOS applies its own mask, so this one is a full square with no transparency. --}}
<link rel="apple-touch-icon" href="/pwa-icons/apple-touch-icon.png">

<meta name="application-name" content="Bible Reading Tracker">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="BibleTracker">
