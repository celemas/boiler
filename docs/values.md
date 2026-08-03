# Displaying values

In escaped renders, Boiler wraps strings and most objects before exposing them to templates. This gives you automatic escaping while still allowing objects, arrays, and iterators to be used naturally in template code.

Read this page if you want to understand when Boiler escapes values and when you need `$this->unwrap()`, `$this->escape()`, or `$this->wrap()`.

## What Boiler escapes automatically

In escaped renders, Boiler escapes:

- strings
- `Stringable` values
- strings returned from wrapped objects, arrays, and iterators during template access

Boiler uses PHP's `htmlspecialchars()` with these defaults:

- `ENT_QUOTES | ENT_SUBSTITUTE`
- `UTF-8`

Integers, floats, booleans, `null`, resources, and similar scalar values are not converted into escaped string wrappers ahead of time.

## Unwrap values

Use `$this->unwrap($value)` when you need the original value instead of the wrapped proxy object.

This is mainly useful for explicit checks or when you need the original array of arguments inside your own helper logic.

```php
<?php if ($this->unwrap($title) !== '') : ?>
    <h1><?= $title ?></h1>
<?php endif; ?>
```

## Escape a value explicitly

Use `$this->escape()` when you need to escape a value manually, or when you want to select a named escaper:

```php
$this->escape($value);
$this->escape($value, 'html');
$this->escape(
    value: $value,
    escaper: 'html',
);

$title->escape();
$title->escape('html');
```

Boiler ships with the `html` escaper. It uses PHP's `htmlspecialchars()` with `ENT_QUOTES | ENT_SUBSTITUTE` and `UTF-8`.

`$this->escape()` accepts strings, `Stringable` values, and Boiler's wrapped string or object proxies. Wrapped strings also expose `->escape()` directly. Explicit escape calls always run the selected escaper on the wrapped string, even when a safe filter would let direct output skip auto-escaping. That includes calls such as `$this->escape($html->sanitize())` and `$html->sanitize()->escape()`. Use direct output such as `<?= $html->sanitize() ?>` when you want to preserve safe filter output without escaping it again. The `escaper` argument is forwarded to the wrapper's configured escaper registry. Boiler's built-in `Escapers` registry supports constructor-seeded entries and incremental `->register()` calls, and custom escaper implementations can expose additional escaper names too.

## Wrap a value explicitly

Use `$this->wrap()` when you need Boiler's proxy behavior for a raw value inside a template.

This is most useful when you want string filter methods on a literal or raw string value, especially in unescaped renders:

```php
<?= $this->wrap($html)->sanitize() ?>
<?= $this->wrap('<b>Boiler</b>')->stripTags('<b>') ?>
```

`$this->wrap()` always uses the wrapper directly, so it still returns proxies even when the engine is rendering unescaped output, and for instances of [trusted classes](#trusted-classes). Trust governs automatic wrapping; asking for a proxy explicitly overrides it.

## Return safe HTML from a method

When a registered template method generates safe HTML, mark it with `safe: true`:

```php
use function App\Template\icon;

$engine->method('icon', icon(...), safe: true);
```

In escaped renders, `<?= $this->icon('check') ?>` then skips auto-escaping. Boiler still exposes the result as a wrapped string, so safety-preserving filters continue to work:

```php
<?= $this->icon('check')->trim() ?>
```

Use this only when the helper itself guarantees safe HTML. In escaped renders, safe methods must return `string` or `Stringable`.

## Filters

Filters are value transformations applied as virtual methods on wrapped string values inside templates:

```php
<?= $html->sanitize() ?>
<?= $title->stripTags('<b>') ?>
```

In escaped renders, string values from template context are already wrapped. In unescaped renders, or when you start from a literal string in the template, call `$this->wrap()` first.

Filters can be chained. Safe output only stays safe through filters that explicitly preserve safety. Boiler's built-in `lower`, `upper`, `stripTags`, and `trim` filters preserve already-safe HTML without claiming to sanitize arbitrary input:

```php
<?= $html->sanitize()->upper() ?>
<?= $html->sanitize()->stripTags('<b>') ?>
```

Boiler ships with built-in filters:

- `sanitize` removes unsafe HTML while allowing safe elements. This filter is safe, meaning its output skips auto-escaping. Requires `symfony/html-sanitizer`.
- `lower` lowercases text via `mb_strtolower()`. This filter is not safe on arbitrary input, but it preserves already-safe output from earlier safe filters.
- `upper` uppercases text via `mb_strtoupper()`. This filter is not safe on arbitrary input, but it preserves already-safe output from earlier safe filters.
- `stripTags` removes HTML tags via `strip_tags()`. This filter is not safe on arbitrary input, but it preserves already-safe output from earlier safe filters.
- `trim` trims leading and trailing characters via `trim()`. This filter is not safe on arbitrary input, but it preserves already-safe output from earlier safe filters.

When you write a custom filter, return `true` from `safe()` only when the filter output is safe HTML from arbitrary input. When it should keep already-safe HTML safe, implement `Celema\Boiler\Contract\PreservesSafety` instead.

Register custom filters on the engine with the fluent `filter()` method. Read [the engine](engine.md) for details.

Use filters when you want to transform wrapped values. Use named escapers when you intentionally need a different escaping context. Use normal escaped output or `$this->escape()` when plain text output is enough.

## Trusted classes

By default, Boiler wraps objects in escaped renders. If a specific class should stay unwrapped, add it to the trusted list when creating the `Engine` or when rendering a standalone `Template`.

Trust applies at any depth: an instance of a trusted class stays unwrapped whether it is a context value itself, an element of an array, or yielded by an iterator.

```php
$engine = \Celema\Boiler\Engine::create(
    '/path/to/templates',
    defaults: [],
    trusted: [TrustedHtml::class],
);
```

Use this carefully. Trusted objects bypass Boiler's normal wrapping and can output unescaped string content.

## Working with arrays, iterators, and objects

Boiler also wraps arrays, traversables, and objects so nested values keep the same escaping behavior inside templates.

That means this stays escaped in a normal render:

```php
<?php foreach ($items as $item) : ?>
    <li><?= $item ?></li>
<?php endforeach; ?>
```

The same applies when values come from object properties, object methods, or iterator items.

## Unescaped renders

When you use `Engine::unescaped()` or `renderUnescaped()`, Boiler stops wrapping values for automatic escaping.

In that mode:

- `<?= $value ?>` outputs unescaped string content
- `$this->unwrap()` usually returns the same value you already have
- `$this->wrap()` is still available when you need proxy behavior such as string filters
- string filter methods such as `->sanitize()` only exist on wrapped string proxies, so plain strings in unescaped renders do not expose them automatically
