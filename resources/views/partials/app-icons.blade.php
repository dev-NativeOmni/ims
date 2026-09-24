{{--
    Favicon, ikon layar utama iOS, dan manifest PWA Android -- semuanya dari logo
    SMA Islam Al Azhar 7 (public/icons, dibuat dari public/images/logo_alazhar7.png).
    Naikkan ?v= bila berkas ikon diganti, supaya browser/HP tidak memakai cache lama.
--}}
<link rel="manifest" href="/manifest.json?v=2">
<meta name="theme-color" content="{{ $themeColor ?? '#059669' }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="TAD SMAIA 7">
<meta name="application-name" content="TAD SMAIA 7">
<link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png?v=2">
<link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32.png?v=2">
<link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16.png?v=2">
<link rel="icon" href="/favicon.ico?v=2" sizes="any">
