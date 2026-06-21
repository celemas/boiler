# Boiler template engine for PHP

Boiler is a small template engine for PHP 8.5+, inspired by [Plates](https://platesphp.com/). Like Plates, it uses native PHP as its templating language rather than introducing a custom syntax.

The main differences from Plates are:

- Boiler automatically escapes strings and [Stringable](https://www.php.net/manual/en/class.stringable.php) values by default. You can disable this globally or for individual render calls.
- The template context is global by default. Values from the main template are available in included templates and layouts.

## Features

- Automatic escaping via PHP's `htmlspecialchars()`
- A small API centered around the [Engine](engine.md)
- Code reuse with [layouts](layouts.md), [inserts](inserts.md), [sections](sections.md), and [slots](slots.md)
- Plain PHP templates with no custom syntax
- Wrapper-driven escaping and a pluggable filter system for value transformations such as HTML sanitization, case conversion, tag stripping, and trimming
- Custom template methods, including safe HTML helpers, and optional trusted classes
- Fully tested and statically analyzed with Psalm level 1

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
