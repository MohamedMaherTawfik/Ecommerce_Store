<!DOCTYPE html>
<html lang="{{ $seo['locale'] ?? config('app.locale') }}" dir="{{ ($seo['locale'] ?? config('app.locale')) === 'ar' ? 'rtl' : 'ltr' }}" id="html-root" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $seo['title'] }} | {{ config('seo.title_suffix') }}</title>
    <meta name="description" content="{{ $seo['description'] }}">
    <meta name="robots" content="{{ $seo['robots'] }}">
    @if (!empty($seo['keywords']))
        <meta name="keywords" content="{{ $seo['keywords'] }}">
    @endif
    <link rel="canonical" href="{{ $seo['canonical'] }}">

    @if (!empty($heroImage))
        @php $heroHost = parse_url($heroImage, PHP_URL_SCHEME) ? parse_url($heroImage, PHP_URL_SCHEME).'://'.parse_url($heroImage, PHP_URL_HOST) : null; @endphp
        @if ($heroHost)
            <link rel="preconnect" href="{{ $heroHost }}" crossorigin>
        @endif
        <link rel="preload" as="image" href="{{ $heroImage }}"
            @if (!empty($heroSrcset)) imagesrcset="{{ $heroSrcset }}" imagesizes="100vw" @endif
            fetchpriority="high">
    @endif
    @foreach (($seo['alternates'] ?? []) as $locale => $url)
        <link rel="alternate" hreflang="{{ $locale }}" href="{{ $url }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $seo['alternates'][config('seo.default_locale')] ?? $seo['canonical'] }}">

    <meta property="og:site_name" content="{{ config('seo.site_name') }}">
    <meta property="og:type" content="{{ $seo['type'] }}">
    <meta property="og:title" content="{{ $seo['og_title'] ?? $seo['title'] }}">
    <meta property="og:description" content="{{ $seo['og_description'] ?? $seo['description'] }}">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    <meta property="og:locale" content="{{ ($seo['locale'] ?? 'en') === 'ar' ? 'ar_AR' : 'en_US' }}">
    @if (!empty($seo['image']))
        <meta property="og:image" content="{{ $seo['image'] }}">
        <meta property="og:image:alt" content="{{ $seo['og_title'] ?? $seo['title'] }}">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['twitter_title'] ?? $seo['og_title'] ?? $seo['title'] }}">
    <meta name="twitter:description" content="{{ $seo['twitter_description'] ?? $seo['og_description'] ?? $seo['description'] }}">
    @if (!empty(config('seo.twitter_site')))
        <meta name="twitter:site" content="{{ config('seo.twitter_site') }}">
    @endif
    @if (!empty($seo['twitter_image'] ?? $seo['image']))
        <meta name="twitter:image" content="{{ $seo['twitter_image'] ?? $seo['image'] }}">
        <meta name="twitter:image:alt" content="{{ $seo['twitter_title'] ?? $seo['title'] }}">
    @endif

    @if (!empty($seo['schema']))
        <script type="application/ld+json">{!! json_encode($seo['schema'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif

    @php
        $manifestExists = file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'));
        $usingHot = file_exists(public_path('hot'));
        $homeEntry = null;
        $iconsEntry = null;
        if (!$usingHot && file_exists(public_path('build/manifest.json'))) {
            $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true) ?: [];
            $iconsEntry = $manifest['resources/css/icons.css'] ?? null;
            if ($isStorefront ?? false) {
                $homeEntry = $manifest['resources/js/views/home/home.vue']
                    ?? $manifest['resources/js/views/home/Home.vue']
                    ?? null;
            }
        }
        $tabIconData = \App\Support\TaggedCache::tags(['settings'])->remember(
            'setting_tab_icon_view',
            3600,
            function () {
                if (! \Illuminate\Support\Facades\Schema::hasTable('site_settings')) {
                    return [
                        'url' => null,
                        'v' => time(),
                    ];
                }

                $setting = \App\Models\SiteSetting::where('key', 'tab_icon')->first();
                $val = $setting?->value;
                if (is_string($val) && (str_starts_with($val, 'storage/') || str_starts_with($val, '/storage/'))) {
                    $val = asset(ltrim($val, '/'));
                }
                return [
                    'url' => $val,
                    'v' => $setting ? $setting->updated_at->timestamp : time(),
                ];
            },
        );
        $tabIconUrl = $tabIconData['url']
            ? $tabIconData['url'] . '?v=' . $tabIconData['v']
            : asset('favicon.svg');
    @endphp

    <link rel="icon" href="{{ $tabIconUrl }}" sizes="any" @if(str_ends_with(parse_url($tabIconUrl, PHP_URL_PATH) ?? '', '.svg')) type="image/svg+xml" @endif>
    <link rel="apple-touch-icon" href="{{ $tabIconUrl }}">

    @if ($manifestExists)
        @if ($homeEntry)
            <link rel="modulepreload" href="{{ asset('build/'.$homeEntry['file']) }}">
            @foreach (($homeEntry['css'] ?? []) as $homeCss)
                <link rel="stylesheet" href="{{ asset('build/'.$homeCss) }}">
            @endforeach
        @endif
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @if ($usingHot)
            @vite('resources/css/icons.css')
        @elseif ($iconsEntry)
            <link rel="stylesheet" href="{{ asset('build/'.$iconsEntry['file']) }}" media="print" onload="this.media='all'">
            <noscript><link rel="stylesheet" href="{{ asset('build/'.$iconsEntry['file']) }}"></noscript>
        @endif
    @else
        <style>
            body {
                font-family: sans-serif;
                text-align: center;
                padding: 50px;
                background: #f8f9fa;
            }

            .error-box {
                max-width: 600px;
                margin: 0 auto;
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            h1 {
                color: #dc3545;
            }
        </style>
    @endif

    <style>
        html {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            margin: 0;
        }

        .bi {
            display: inline-block;
            width: 1em;
            min-width: 1em;
            height: 1em;
            line-height: 1;
        }

        .boot-nav {
            min-height: 72px;
            border-bottom: 1px solid #e2e8f0;
            background: #fff;
        }

        .boot-hero {
            height: clamp(520px, 88vh, 900px);
            background: linear-gradient(110deg, #111827, #1f2937);
        }

        @media (max-width: 767px) {
            .boot-hero {
                height: clamp(480px, 80vh, 680px);
            }
        }
    </style>

    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>

</head>

<body class="app-body">
    @if (!$manifestExists)
        <div class="error-box">
            <h1>Frontend Build Missing</h1>
            <p>Please run <code>npm install && npm run build</code> or start the dev server with <code>npm run
                    dev</code> to build the frontend assets.</p>
        </div>
    @else
        <div id="app">
            @if ($isStorefront ?? false)
                <div class="boot-nav" aria-hidden="true"></div>
                @if (!empty($initialHomeData))
                    <div class="boot-hero" aria-hidden="true"></div>
                @endif
            @endif
        </div>
    @endif

    @if (!empty($initialHomeData))
        <script id="initial-home-data" type="application/json">{!! json_encode($initialHomeData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @endif
    @if (!empty($initialLayoutData))
        <script id="initial-layout-data" type="application/json">{!! json_encode($initialLayoutData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @endif
</body>

</html>
