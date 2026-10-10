<footer class="site-footer">
    <div class="footer-columns">
        @foreach ($footer as $column)
            <section>
                <h2>{{ $column['title'] }}</h2>
                <ul>
                    @foreach ($column['links'] as $link)
                        <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>

    <p class="support">
        Questions? <a href="mailto:{{ $site->support->email }}">{{ $site->support->email }}</a>
        · {{ $site->support->phone }} · {{ $site->support->hours }}
    </p>
    <p class="legal">© {{ $site->year }} {{ $site->name }}. {{ $site->tagline }}.</p>
</footer>
