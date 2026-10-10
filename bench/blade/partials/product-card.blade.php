<x-card :title="$product->name" :href="$product->url" :tone="$product->onSale() ? 'sale' : 'plain'">
    <img src="{{ $product->image->src }}" alt="{{ $product->image->alt }}" width="{{ $product->image->width }}" height="{{ $product->image->height }}" loading="lazy">
    <p class="vendor">{{ strtoupper($product->vendor) }} · <span class="sku">{{ $product->sku }}</span></p>
    @if (in_array('new', $product->tags))
        <span class="flag">New</span>
    @endif

    @include('partials.price')
    @include('partials.rating', ['rating' => $product->rating, 'count' => $product->reviews])

    @if ($product->badges)
        <ul class="badges">
            @foreach ($product->badges as $badge)
                <li>{{ strtoupper($badge) }}</li>
            @endforeach
        </ul>
    @endif
    <ul class="tags">
        @foreach ($product->tags as $tag)
            <li><a href="/tags/{{ $tag }}">{{ $tag }}</a></li>
        @endforeach
    </ul>
    <button type="button" data-add="{{ $product->sku }}">{!! icon('cart') !!} Add to cart</button>
</x-card>
