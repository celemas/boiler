<nav class="category-tree" aria-label="Categories">
    <h2>Categories</h2>
    <ul>
        @foreach ($categories as $entry)
            <li class="{{ $entry['current'] ? 'is-current' : 'is-other' }}">
                <a href="{{ $entry['url'] }}">{{ $entry['name'] }}</a>
                <small>({{ $entry['count'] }})</small>
            </li>
        @endforeach
    </ul>
</nav>

<x-card title="Your cart" tone="cart">
    @if ($cart['lines'])
        <ul class="cart-lines">
            @foreach ($cart['lines'] as $line)
                <li>{{ $line['qty'] }} × {{ $line['name'] }} <span>{{ money($line['total']) }}</span></li>
            @endforeach
        </ul>
        <p class="cart-total">Subtotal: {{ money($cart['subtotal']) }}</p>
        @if ($cart['subtotal'] >= $campaign['threshold'])
            <p class="cart-shipping">You get free shipping.</p>
        @else
            <p class="cart-shipping">Add {{ money($campaign['threshold'] - $cart['subtotal']) }} for free shipping.</p>
        @endif
    @else
        <p>Your cart is empty.</p>
    @endif
</x-card>

<x-card title="Need help?" tone="support">
    <p><a href="mailto:{{ $site->support->email }}">{{ $site->support->email }}</a></p>
    <p>{{ $site->support->phone }} · {{ $site->support->hours }}</p>
</x-card>
