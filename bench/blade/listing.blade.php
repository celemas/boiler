@extends('layouts.shop')

@section('shop-title', $category['name'])

@section('head')
    @parent
    <meta name="description" content="{{ $category['description'] }}">
    <link rel="canonical" href="{{ $category['url'] }}">
@endsection

@section('sidebar')
    @include('partials.facets')
    @parent
@endsection

@section('shop-content')
    <section class="listing">
        <header>
            <h1>{{ $category['name'] }}</h1>
            <p class="lead">{{ $category['description'] }}</p>
            <p class="count">Showing {{ $pagination['from'] }}–{{ $pagination['to'] }} of {{ $pagination['total'] }} products</p>
        </header>

        <div class="toolbar">
            @if ($activeFilters)
                <ul class="active-filters">
                    @foreach ($activeFilters as $filter)
                        <li>
                            {{ $filter['label'] }}: <strong>{{ $filter['value'] }}</strong>
                            <a href="{{ $filter['remove'] }}" aria-label="Remove filter {{ $filter['label'] }}">×</a>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="active-filters">No filters selected.</p>
            @endif

            <label>
                Sort by
                <select name="sort">
                    @foreach ($sortOptions as $value => $label)
                        <option value="{{ $value }}" @selected($value === $sort)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="grid">
            @foreach ($products as $product)
                @include('partials.product-card')
            @endforeach
        </div>

        @include('partials.pagination')
    </section>
@endsection

@push('scripts')
    <script src="/js/listing.js" data-category="{{ $category['slug'] }}" data-page="{{ $pagination['page'] }}"></script>
@endpush
