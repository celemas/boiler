<section class="block-products">
    <h3>{{ $block['title'] }}</h3>
    <div class="grid">
        @foreach ($block['products'] as $product)
            @include('partials.product-card')
        @endforeach
    </div>
</section>
