@extends('layouts.base')

@section('title')
    @yield('shop-title', 'All products') | Shop
@endsection

@section('head')
    @parent
    <link rel="stylesheet" href="/css/shop.css">
@endsection

@section('body-class', 'page-shop')

@section('hero')
    <section class="promo">
        <h2>{{ strtoupper($campaign['title']) }}</h2>
        <p>
            Use code <strong>{{ $campaign['code'] }}</strong>
            for free shipping above {{ money($campaign['threshold']) }}.
        </p>
        <p>Ends <time datetime="{{ $campaign['endsAt']->format('Y-m-d\TH:i') }}">{{ $campaign['endsAt']->format('F j, H:i') }}</time></p>
    </section>
@endsection

@section('content')
    <div class="shop">
        <div class="shop-main">
            @yield('shop-content')
        </div>

        <aside class="shop-sidebar">
            @section('sidebar')
                @include('partials.sidebar')
            @show
        </aside>
    </div>
@endsection

@prepend('scripts')
    <script src="/js/shop.js"></script>
@endprepend
