<section class="block-faq">
    <h3>{{ $block['title'] }}</h3>
    @foreach ($block['items'] as $item)
        <x-card :title="$item['question']" tone="faq">
            <p>{{ $item['answer'] }}</p>
        </x-card>
    @endforeach
</section>
