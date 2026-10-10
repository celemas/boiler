<figure class="block-image">
    <img src="{{ $block['src'] }}" alt="{{ $block['alt'] }}" width="{{ $block['width'] }}" height="{{ $block['height'] }}" loading="lazy">
    <figcaption>
        {{ $block['caption'] }}
        @isset($block['credit'])
            <small>Photo: {{ $block['credit'] }}</small>
        @endisset
    </figcaption>
</figure>
