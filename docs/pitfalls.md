# Common pitfalls

In an escaped render, Boiler passes strings, arrays, and objects to templates as wrapped values: proxy objects that escape their content when printed. Printing works as you would expect. Everything else PHP does with a value itself sees the proxy instead of the value: `if`, `===`, `match`, `instanceof`, and the functions you call. Three rules cover most cases:

- Use `u()` where PHP needs the value: in conditions, comparisons, `match`, and calls of PHP functions.
- Use `w()` to print what PHP made of a value, so it is escaped.
- Never print `u()`. Its result is the original value, which prints unescaped.

Declare strict types at the top of every template, so that a wrapped value passed to a `string` parameter fails instead of quietly turning into escaped text:

```php
<?php declare(strict_types=1) ?>
```

This page lists the mistakes these rules prevent, by the symptom you would see. [Displaying values](values.md) explains the mechanisms in detail.

## At a glance

| Instead of | Write | Because |
| --- | --- | --- |
| `if ($subtitle)` | `if (u($subtitle))` | a wrapped value is an object, which is always true |
| `$status === 'active'` | `u($status) === 'active'` | `===` compares the proxy object |
| `$title == 'Tom & Jerry'` | `u($title) === 'Tom & Jerry'` | `==` compares the escaped text |
| `match ($status)` | `match (u($status))` | `match` compares with `===` |
| `$user instanceof Admin` | `u($user) instanceof Admin` | the proxy is not an `Admin` |
| `mb_strlen($title)` | `mb_strlen(u($title))` | the function gets the escaped text |
| `<?= mb_strtoupper(u($title)) ?>` | `<?= w(mb_strtoupper(u($title))) ?>` | a function result prints as is |
| `<?= u($title) ?>` | `<?= $title ?>` | the original value prints unescaped |
| `json_encode($items)` | `json_encode(u($items), JSON_HEX_TAG)` | encoding a wrapped value fails |
| `implode(', ', $tags)` | `w(implode(', ', u($tags)))` | a wrapped array is not an `array` |
| `['text' => "News: {$title}"]` | `['text' => 'News: ' . u($title)]` | the string is escaped again when passed on |
| `!$value \|> u(...)` | `!u($value)` | `!` applies to the proxy first |

## A condition is always true

Wrapped strings and arrays are objects, and PHP treats every object as true, even one that holds `''` or `[]`. `empty()` is false for them for the same reason. Test the original value:

```php
<?php if (u($subtitle)) : ?>
<?php if (!u($items)) : ?>
<?php if (count($items) > 0) : ?>
```

`isset()` and `??` work on wrapped values as on plain ones. See [checking for values](values.md#checking-for-values).

## A comparison is always false

`===` and `match` compare the proxy object, which is never identical to a plain value. `==` converts the proxy to its escaped text, so it works only until the value contains `&`, `<`, `>`, or a quote. Compare the original value:

```php
<?php if (u($item->status) === Status::Active) : ?>
<?= match (u($item->status)) {
    'active' => 'Active',
    'blocked' => 'Blocked',
} ?>
```

Keys in loops over wrapped arrays are wrapped too, so compare `u($key)`. See [comparing wrapped values](values.md#comparing-wrapped-values).

## A type check fails

A wrapped value is a proxy object, so `instanceof`, `is_string()`, `is_array()`, and type declarations do not see the value. Check the original:

```php
<?php if (u($user) instanceof Admin) : ?>
<?php if (is_array(u($value))) : ?>
```

A PHP function or one of your own that declares an `array` or class parameter raises a `TypeError` for a proxy. Pass `u($value)`. Template methods, filters, and the methods of wrapped objects unwrap their arguments themselves.

## A function counts, cuts, or searches the escaped text

A PHP function that expects a string converts a wrapped string to its escaped text: `strlen()` counts `&amp;` as five characters, `substr()` can cut an entity in half, and `str_contains($title, '<b>')` searches for the escaped tag. Nothing reports this unless the template declares strict types. Pass the original value:

```php
<?= mb_strlen(u($title)) ?>
```

See [passing wrapped values to PHP functions](values.md#passing-wrapped-values-to-php-functions).

## Markup prints unescaped

Boiler escapes the values it wraps. What a PHP function returns is a plain string, which it prints as is, and so is the result of `u()`. Wrap a function result with `w()` before you print it:

```php
<?= w(mb_strimwidth(u($title), 0, 40, '…')) ?>
<?= $title |> u(...) |> mb_strtoupper(...) |> w(...) ?>
```

Print values as they come, `<?= $title ?>`, never as `<?= u($title) ?>`. For HTML that should print unescaped, use a [trusted class](values.md#trusted-classes), a [safe template method](values.md#return-safe-html-from-a-method), or the `sanitize` [filter](values.md#filters).

## Text shows `&amp;amp;`

Converting a wrapped value to a string escapes it: printing does that, and so do concatenation and interpolation. A string built this way and passed back into Boiler, for example to `include()` or a template method, is escaped a second time. Build it from the original values:

```php
<?php $this->include('heading', ['text' => 'News: ' . u($title)]) ?>
```

The same happens to the result of `$this->escape()` passed on this way. Pass the result of `w()` instead, which is escaped only once. See [building strings from wrapped values](values.md#building-strings-from-wrapped-values).

## `json_encode()` fails the render

PHP would encode a proxy as `{}`, so Boiler fails the render instead. Encode the original value, and escape the result in an attribute:

```php
<script type="application/json"><?= json_encode(u($items), JSON_HEX_TAG) ?></script>
<div data-items="<?= w(json_encode(u($items))) ?>"></div>
```

## A method of a wrapped value fails

`is()`, `in()`, `contains()`, filters such as `->upper()`, and the other methods of wrapped values exist only on proxies. Integers, floats, booleans, and `null` are never wrapped, and an unescaped render wraps nothing, so calling such a method there fails with a PHP error. Compare integers as they are, `$item->count === 3`, and use `u()` in templates that render both escaped and unescaped. For a filter on a plain string, wrap it first: `w($html)->sanitize()`.

## The pipe operator applies `!` first

`!` binds tighter than `|>`, so `!$value |> u(...)` negates the proxy, which is always false, and passes `false` to `u()`. Call `u()` directly in conditions:

```php
<?php if (!u($items)) : ?>
```

An arrow function on the right of `|>` needs parentheses: `$title |> u(...) |> (fn($s) => mb_strimwidth($s, 0, 40, '…')) |> w(...)`.

## Coming from Twig or Blade

Twig and Blade compile templates and escape at the output, so the values in a template are the originals. Boiler runs templates as they are and escapes the values themselves, which is why `u()` appears where the other engines need nothing:

| Twig | Blade | Boiler |
| --- | --- | --- |
| `{{ title }}` | `{{ $title }}` | `<?= $title ?>` |
| `{% if title %}` | `@if ($title)` | `<?php if (u($title)) : ?>` |
| `{% if status == 'active' %}` | `@if ($status === 'active')` | `<?php if (u($status) === 'active') : ?>` |
| `{% if items is empty %}` | `@if (empty($items))` | `<?php if (!u($items)) : ?>` |
| `{{ title\|upper }}` | `{{ mb_strtoupper($title) }}` | `<?= $title->upper() ?>` |
| `{{ title\|length }}` | `{{ mb_strlen($title) }}` | `<?= mb_strlen(u($title)) ?>` |
| `{{ items\|json_encode }}` | `@json($items)` | `<?= json_encode(u($items), JSON_HEX_TAG) ?>` |
| `{{ text\|e('js') }}` | — | `<?= $this->escape($text, 'js') ?>`, with an escaper registered as `js` |
| `{{ html\|raw }}` | `{!! $html !!}` | an object of a [trusted class](values.md#trusted-classes), or `<?= $html->sanitize() ?>` |
