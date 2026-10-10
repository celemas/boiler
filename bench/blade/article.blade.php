@extends('layouts.base')

@section('title', $article['title'])

@section('head')
    @parent
    <meta name="description" content="{{ $article['lead'] }}">
    <meta name="author" content="{{ $article['author']->name }}">
    <link rel="stylesheet" href="/css/article.css">
@endsection

@section('body-class', 'page-article')

@section('hero')
    <header class="article-hero">
        <h1>{{ $article['title'] }}</h1>
        <p class="lead">{{ $article['lead'] }}</p>
        <p class="meta">
            By <a href="{{ $article['author']->url }}">{{ $article['author']->name }}</a>, {{ $article['author']->role }} ·
            <time datetime="{{ $article['published']->format('Y-m-d') }}">{{ $article['published']->format('F j, Y') }}</time> ·
            {{ $article['readingMinutes'] }} min read
        </p>
    </header>
@endsection

@section('content')
    <div class="article">
        <nav class="toc" aria-label="Contents">
            <h2>Contents</h2>
            <ol>
                @foreach ($blocks as $block)
                    @if ($block['type'] === 'heading')
                        <li class="toc-level-{{ $block['level'] }}"><a href="#{{ $block['id'] }}">{{ $block['text'] }}</a></li>
                    @endif
                @endforeach
            </ol>
        </nav>

        <div class="article-body">
            @foreach ($blocks as $block)
                @include('blocks.' . $block['type'])
            @endforeach
        </div>

        <footer class="article-footer">
            <ul class="tags">
                @foreach ($article['tags'] as $tag)
                    <li><a href="/magazine/tags/{{ strtolower($tag) }}">#{{ $tag }}</a></li>
                @endforeach
            </ul>

            <x-card title="About the author" tone="author">
                <p><strong>{{ $article['author']->name }}</strong> – {{ $article['author']->role }}</p>
                <p>{{ $article['author']->bio }}</p>
            </x-card>
        </footer>
    </div>
@endsection
