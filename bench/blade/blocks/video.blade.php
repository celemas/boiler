<div class="block-video" id="video-{{ $block['id'] }}" data-provider="{{ $block['provider'] }}">
    <a href="{{ $block['url'] }}">{{ $block['title'] }}</a>
</div>

@push('scripts')
    <script src="/js/video.js" data-target="video-{{ $block['id'] }}" data-provider="{{ $block['provider'] }}"></script>
@endpush
