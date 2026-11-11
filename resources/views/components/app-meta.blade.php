{{-- Icons and metadata so ReservaPro can be installed as an app (PWA). Served by ProgressiveWebAppController and public/sw.js. --}}
<link rel="icon" href="/icons/icon.svg" type="image/svg+xml">
<link rel="manifest" href="{{ route('manifest') }}">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<meta name="theme-color" content="{{ \App\Http\Controllers\ProgressiveWebAppController::THEME_COLOR }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="ReservaPro">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
