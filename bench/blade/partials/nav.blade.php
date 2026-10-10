<nav class="site-nav" aria-label="Main">
    <ul>
        @foreach ($nav as $item)
            <li class="{{ $item->active() ? 'is-active' : 'is-idle' }}">
                <a href="{{ $item->url() }}">{{ $item->title() }}</a>
                @if ($item->hasChildren())
                    <ul>
                        @foreach ($item as $child)
                            <li><a href="{{ $child->url() }}">{{ $child->title() }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
