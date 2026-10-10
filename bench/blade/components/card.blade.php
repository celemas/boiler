@props(['title', 'href' => null, 'tone' => 'plain'])

<article class="card card-{{ $tone }}">
    <h3 class="card-title">
        @if ($href)
            <a href="{{ $href }}">{{ $title }}</a>
        @else
            {{ $title }}
        @endif
    </h3>
    <div class="card-body">
        {{ $slot }}
    </div>
</article>
