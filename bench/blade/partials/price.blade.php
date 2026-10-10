@use('Celema\Boiler\Bench\Stock')

<p class="price">
    <span class="price-now">{{ money($product->price) }}</span>
    @if ($product->onSale())
        <s class="price-was">{{ money($product->compareAt) }}</s>
        <span class="price-save">Save {{ $product->discount() }}%</span>
    @endif
</p>
<p class="stock stock-{{ $product->stock->value }}">
    @if ($product->stock === Stock::InStock)
        In stock
    @elseif ($product->stock === Stock::Low)
        Only {{ $product->stockCount }} left
    @elseif ($product->stock === Stock::Preorder)
        Preorder
    @else
        Sold out
    @endif
    @if ($product->freeShipping)
        · Free shipping
    @endif
</p>
