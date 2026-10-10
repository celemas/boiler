<p class="rating" aria-label="{{ $rating }} out of 5 stars">
    @for ($star = 1; $star <= 5; $star++)
        {{ $star <= $rating ? '★' : '☆' }}
    @endfor
    @isset($count)
        <span class="rating-count">({{ $count }})</span>
    @endisset
</p>
