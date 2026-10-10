<nav class="pagination" aria-label="Pagination">
    @if ($pagination['page'] > 1)
        <a rel="prev" href="{{ $pagination['url'] }}{{ $pagination['page'] - 1 }}">Previous</a>
    @endif
    @for ($page = 1; $page <= $pagination['pages']; $page++)
        @if ($page === $pagination['page'])
            <span class="current" aria-current="page">{{ $page }}</span>
        @else
            <a href="{{ $pagination['url'] }}{{ $page }}">{{ $page }}</a>
        @endif
    @endfor
    @if ($pagination['page'] < $pagination['pages'])
        <a rel="next" href="{{ $pagination['url'] }}{{ $pagination['page'] + 1 }}">Next</a>
    @endif
</nav>
