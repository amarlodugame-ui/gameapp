<!DOCTYPE html>
<!-- <html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])> -->
<html lang="en" translate="no" data-dpr="1" dir="ltr" style="font-size: 39px;">
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="google" content="notranslate">
        <meta name="robots" content="noindex,nofollow">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

        <title>{{ gs('site_name') }}</title>

        <link rel="icon" href="/assets/logo/paw-download.webp" sizes="any">
        <link rel="icon" href="/assets/logo/paw-download.webp" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/assets/logo/paw-download.webp">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
        @routes
        @vite(['resources/js/app.ts', "resources/js/pages/themes/{$page['component']}.vue"])
        @inertiaHead

    </head>
    <body>
        @inertia
    </body>
</html>
