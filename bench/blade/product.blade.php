@extends('layouts.shop')

@section('shop-title', $product->name)

@section('head')
    @parent
    <meta property="og:title" content="{{ $product->name }}">
    <meta property="og:image" content="{{ $product->image->src }}">
    <link rel="canonical" href="{{ $product->url }}">
@endsection

@section('hero', '')

@section('shop-content')
    <article class="product" data-sku="{{ $product->sku }}">
        <div class="gallery">
            @foreach ($product->gallery as $image)
                <img src="{{ $image->src }}" alt="{{ $image->alt }}" width="{{ $image->width }}" height="{{ $image->height }}">
            @endforeach
        </div>

        <div class="summary">
            <h1>{{ $product->name }}</h1>
            <p class="vendor">by <a href="/brands/{{ strtolower($product->vendor) }}">{{ $product->vendor }}</a></p>

            @include('partials.rating', ['rating' => $product->rating, 'count' => $product->reviews])
            @include('partials.price')

            <form class="variants" action="/cart" method="post">
                @foreach ($product->variants as $variant)
                    <label class="{{ $variant['available'] ? 'is-available' : 'is-unavailable' }}">
                        <input type="radio" name="sku" value="{{ $variant['sku'] }}" @checked($variant['sku'] === $product->sku) @disabled(!$variant['available'])>
                        {{ $variant['label'] }}
                        <span>{{ money($variant['price']) }}</span>
                    </label>
                @endforeach
                <button type="submit">{!! icon('cart') !!} Add to cart</button>
            </form>

            <div class="description">{!! $product->description !!}</div>
        </div>

        <table class="specs">
            <caption>Specifications</caption>
            @foreach ($product->specs as $name => $value)
                <tr>
                    <th scope="row">{{ $name }}</th>
                    <td>{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    </article>

    <section class="reviews" id="reviews">
        <h2>Reviews ({{ count($reviews) }})</h2>
        @foreach ($reviews as $review)
            <x-card :title="$review->title" :tone="$review->verified ? 'verified' : 'plain'">
                @include('partials.rating', ['rating' => $review->rating])
                <p class="byline">
                    {{ $review->author }} ·
                    <time datetime="{{ $review->date->format('Y-m-d') }}">{{ $review->date->format('M j, Y') }}</time>
                    @if ($review->verified)
                        · <span class="verified">Verified purchase</span>
                    @endif
                </p>
                <p>{{ $review->body }}</p>
            </x-card>
        @endforeach
    </section>

    <section class="related">
        <h2>Customers also bought</h2>
        <div class="grid">
            @foreach ($related as $item)
                @include('partials.product-card', ['product' => $item])
            @endforeach
        </div>
    </section>
@endsection

@push('scripts')
    <script src="/js/product.js" data-sku="{{ $product->sku }}"></script>
@endpush
