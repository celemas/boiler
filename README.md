# Celema Boiler

<!-- prettier-ignore-start -->
[![ci](https://codeberg.org/celema/boiler/badges/workflows/ci.yml/badge.svg?style=flat&logo=codeberg&logoColor=white&label=ci)](https://codeberg.org/celema/boiler/actions)
[![code coverage](https://img.shields.io/endpoint?url=https%3A%2F%2Fcov.celema.dev%2Fcelema%2Fboiler%2Fcode%2Fbadge.json)](https://cov.celema.dev/celema/boiler/code)
[![type coverage](https://img.shields.io/endpoint?url=https%3A%2F%2Fcov.celema.dev%2Fcelema%2Fboiler%2Ftypes%2Fbadge-cover.json)](https://cov.celema.dev/celema/boiler/types)
[![psalm level](https://img.shields.io/endpoint?url=https%3A%2F%2Fcov.celema.dev%2Fcelema%2Fboiler%2Ftypes%2Fbadge-level.json)](https://cov.celema.dev/celema/boiler/types)
[![Software License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)
<!-- prettier-ignore-end -->

Boiler is a small template engine for PHP 8.5+, inspired by [Plates](https://platesphp.com/). Like Plates, it uses native PHP as its templating language rather than introducing a custom syntax.

Key differences from Plates:

- Automatic escaping of strings and [Stringable](https://www.php.net/manual/en/class.stringable.php) values for enhanced security
- Inherited render context across layouts, inserts, and section captures; custom insert or layout context merges on top and overrides duplicate keys

Other highlights:

- Layouts, inserts/partials, and sections, including append and prepend support
- Wrapper-driven escaping and a pluggable filter system for value transformations
- Custom template methods, including safe HTML helpers, and optional trusted classes

## Installation

```console
composer require celema/boiler
```

Install Symfony's HTML sanitizer when you want Boiler's built-in `sanitize` filter:

```console
composer require symfony/html-sanitizer
```

## Documentation

Start here: [docs/index.md](docs/index.md).

### Topic overview

- [Quick start](docs/quickstart.md)
- [Engine](docs/engine.md)
- [Rendering templates](docs/rendering.md)
- [Displaying values](docs/values.md)
- [Layouts](docs/layouts.md)
- [Inserts](docs/inserts.md)
- [Sections](docs/sections.md)
- [Slots](docs/slots.md)
- [Template](docs/template.md)

## Quick start

Consider this example directory structure:

```text
path
`-- to
    `-- templates
        `-- page.php
```

Create a template file at `/path/to/templates/page.php` with this content:

```php
<p>ID <?= $id ?></p>
```

Then initialize the `Engine` and render your template:

```php
use Celema\Boiler\Engine;

$engine = Engine::create('/path/to/templates');
$html = $engine->render('page', ['id' => 13]);

assert($html === '<p>ID 13</p>');
```

## Common patterns

Render from multiple directories, optionally with namespaces:

```php
$engine = Engine::create([
    'theme' => '/path/to/theme',
    'app' => '/path/to/templates',
]);

// Renders the first match (theme overrides app)
$engine->render('page');

// Force a specific namespace
$engine->render('app:page');
```

Control escaping:

```php
$engine = Engine::create('/path/to/templates');
$engine->render('page');
$engine->renderUnescaped('page');

$engine = Engine::unescaped('/path/to/templates');
$engine->render('page');
$engine->renderEscaped('page');
```

Configure shared defaults and trusted classes:

```php
$engine = Engine::create(
    '/path/to/templates',
    defaults: ['siteName' => 'Celema'],
    trusted: [TrustedHtml::class],
);
```

Register custom template methods with `method()`. Pass `safe: true` when a helper returns safe HTML:

```php
use function App\Template\icon;

$engine = Engine::create('/path/to/templates')
    ->method('icon', icon(...), safe: true);
```

Methods are available as `$this->icon()` inside templates, inserts, and layouts.

Register custom filters with the fluent `filter()` method:

```php
use Celema\Boiler\Contract\Filter;

$engine = Engine::create('/path/to/templates')
    ->filter('upper', new class implements Filter {
        public function apply(string $value, mixed ...$args): string
        {
            return strtoupper($value);
        }

        public function safe(): bool
        {
            return false;
        }
    });
```

Filters are available as virtual methods on wrapped string values in templates. In escaped renders, Boiler wraps string values for you. When you need filters on a raw value inside a template, call `$this->wrap($value)` first. Boiler ships with built-in `lower`, `upper`, `stripTags`, and `trim` filters, and registers `sanitize` automatically when `symfony/html-sanitizer` is installed.

For filter safety rules and advanced wrapper, filter, and escaper customization, see [displaying values](docs/values.md), [engine](docs/engine.md), and [template](docs/template.md).

Template helpers available via `$this` inside templates:

- `$this->layout('layout')`
- `$this->insert('partial', ['value' => '...'])`
- `$this->slot(['value' => '...'])` / `$this->hasSlot()` inside inserted templates that receive a closure or `Slot::template()` slot
- `$this->begin('name')` / `$this->append('name')` / `$this->prepend('name')` / `$this->end()`
- `$this->section('name', 'default')` / `$this->has('name')`
- `$this->unwrap($value)` when you need the original value instead of the escaped wrapper
- `$this->escape($value)` and `$this->wrap($value)` when you need proxy behavior such as string filters on a raw value

Wrapped values are proxy objects, so `===` against a plain value is always false. Compare through the proxy's `is()` method instead, which checks the raw value strictly: `$item->status->is('active')`, `$item->status->is(Status::Active)`. See [comparing wrapped values](docs/values.md#comparing-wrapped-values).

## Error handling

Boiler fails fast on invalid lookups and render state, such as missing templates, invalid template names, duplicate layouts, unclosed sections, missing slots, or unknown methods and filters. See [rendering templates](docs/rendering.md), [layouts](docs/layouts.md), [sections](docs/sections.md), [slots](docs/slots.md), and [template](docs/template.md) for the exact rules.

## Benchmark

Boiler includes a canonical benchmark in [`bench/`](bench/) that renders a feature-rich catalog page and is used mainly to catch performance regressions during development.

Run it with `composer benchmark`. For benchmark scope, caveats, and detailed usage, see [`bench/README.md`](bench/README.md).

## Run the tests

```console
composer test
composer lint
composer types
composer docs:lint
```

For the PHP verification pipeline, run:

```console
composer ci
```

`composer ci:full` additionally lints Markdown and requires Node (`npx`).

## License

This project is licensed under the [MIT license](LICENSE.md).
