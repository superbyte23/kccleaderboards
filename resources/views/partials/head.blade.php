<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<meta name="description" content="Rally — live leaderboards, team standings, and results for your events.">
<meta name="theme-color" content="#0a0b0d">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=archivo:400,500,600,700,800,900&family=barlow-condensed:500,600,700,800&family=space-mono:400,700&display=swap" rel="stylesheet" />

<script>
    window.localStorage.setItem('flux.appearance', 'dark')
</script>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
