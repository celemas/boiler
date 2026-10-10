<!DOCTYPE html>
<html lang="{{ $site->locale }}">

<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Welcome') – {{ $site->name }}</title>
    @section('head')
        <link rel="stylesheet" href="/css/site.css">
    @show
</head>

<body class="@yield('body-class', 'page')">
    <div class="announcement">{!! $announcement !!}</div>

    <header class="site-header">
        <a class="logo" href="/">{{ $site->name }}</a>
        <p class="tagline">{{ $site->tagline }}</p>

        @include('partials.nav')

        <div class="account">
            @if ($user)
                <img src="{{ $user->avatar }}" alt="" width="32" height="32">
                <span class="user">Hello, {{ $user->name }}</span>
                <span class="tier tier-{{ $user->tier }}">{{ strtoupper($user->tier) }}</span>
            @else
                <a href="/login">Sign in</a>
            @endif
            <a class="cart" href="/cart">{!! icon('cart') !!} Cart ({{ $cart['items'] }}) · {{ money($cart['subtotal']) }}</a>
        </div>
    </header>

    <nav class="breadcrumbs" aria-label="Breadcrumb">
        @foreach ($breadcrumbs as $crumb)
            <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
        @endforeach
    </nav>

    @yield('hero')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    <script src="/js/site.js"></script>
    @stack('scripts')
</body>

</html>
