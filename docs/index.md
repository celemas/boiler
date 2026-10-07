# Boiler template engine for PHP

Boiler is a small template engine for PHP 8.5+, inspired by [Plates](https://platesphp.com/). Like Plates, it uses native PHP as its templating language rather than introducing a custom syntax.

The main differences from Plates are:

- Boiler automatically escapes strings and [Stringable](https://www.php.net/manual/en/class.stringable.php) values by default. You can disable this globally or for individual render calls.
- The template context is global by default. Values from the main template are available in included templates and layouts.

## Features

- Automatic escaping via PHP's `htmlspecialchars()`
- A small API centered around the [Engine](engine.md)
- Code reuse with [layouts](layouts.md), [inserts](inserts.md), [sections](sections.md), and [slots](slots.md), whose concepts map onto Blade's
- Plain PHP templates with no custom syntax
- Wrapper-driven escaping and a pluggable filter system for value transformations such as HTML sanitization, case conversion, tag stripping, and trimming
- Custom template methods, including safe HTML helpers, and optional trusted classes
- Fully tested and statically analyzed with Psalm level 1

## Composition at a glance

A template prints the page or block it wraps with `slot()`, and any template can write sections that a layout prints with `yield()`:

| To | Write | Print | Blade |
| --- | --- | --- | --- |
| Wrap a page in a layout | `$this->layout('layout')` | `<?= $this->slot() ?>` in the layout | `@extends('layout')` with `@yield('content')`, or a component layout with `{{ $slot }}` |
| Include a partial | `$this->insert('card', ['title' => 'News'])` | — | `@include('card', ['title' => 'News'])` |
| Pass a block into a partial | `$this->component('card')` … `$this->end()` | `<?= $this->slot() ?>` in the partial | `<x-card>` … `</x-card>`, `{{ $slot }}` |
| Write a named region from any template | `$this->section('title')`, `append()`, or `prepend()` … `$this->end()` | `<?= $this->yield('title', 'default') ?>` | `@section`, `@push`, `@prepend`, `@yield`, `@stack` |
| Check a named region | — | `$this->hasSection('title')` | `@hasSection('title')` |

## Start here

If you are new to Boiler, read the docs in this order:

1. [Quick start](quickstart.md)
2. [The engine](engine.md)
3. [Rendering templates](rendering.md)
4. [Displaying values](values.md)
5. [Layouts](layouts.md)
6. [Inserts](inserts.md)
7. [Sections](sections.md)
8. [Slots](slots.md)
9. [Template](template.md)
