# Displaying values

In escaped renders, Boiler wraps strings and most objects before exposing them to templates. This gives you automatic escaping while still allowing objects, arrays, and iterators to be used naturally in template code.

Read this page if you want to understand when Boiler escapes values and when you need `u()`, `w()`, or `$this->escape()`. For a checklist of the mistakes wrapped values lead to, see [common pitfalls](pitfalls.md).

## What Boiler escapes automatically

In escaped renders, Boiler escapes:

- strings
- `Stringable` values, unless their class is [trusted](#trusted-classes)
- strings returned from wrapped objects, arrays, and iterators during template access
- keys of wrapped arrays and iterators in `foreach` loops

Boiler uses PHP's `htmlspecialchars()` with these defaults:

- `ENT_QUOTES | ENT_SUBSTITUTE`
- `UTF-8`

Integers, floats, booleans, `null`, resources, and similar scalar values are not converted into escaped string wrappers ahead of time.

## Template functions

Wrapped values are objects, so whatever PHP does with a value itself sees the proxy instead: a condition, a comparison, `match`, or a function call. Boiler defines two global functions for these places. `u()` takes a value out of Boiler for PHP, and `w()` brings what PHP made of it back:

```php
<?php if (u($subtitle)) : ?>
    <h2><?= $subtitle ?></h2>
<?php endif; ?>

<?= w(mb_strimwidth(u($title), 0, 40, '…')) ?>
```

Boiler defines the functions when it renders its first template. If another package has defined a global `u()` or `w()`, the render fails with a `LogicException` that names the file of that function.

### Get the original value with `u()`

`u()` returns the original of a wrapped string, array, object, or iterator. It unwraps the items of a plain array, so `u(['title' => $title])` holds the original title, and returns other values as they are. Integers, floats, booleans, and `null` are never wrapped, and an unescaped render wraps nothing, so `u()` returns those unchanged: a template that uses `u()` works in escaped and unescaped renders alike.

`u()` is the template form of `Celema\Boiler\unwrap()`, which PHP code uses; see [your own PHP code](#your-own-php-code).

Never print the result of `u()`. `<?= u($title) ?>` prints the original value unescaped, like `{!! $title !!}` in Blade or `{{ title|raw }}` in Twig.

### Print what PHP made of a value with `w()`

A PHP function returns a plain string, which Boiler prints as is. `w()` wraps it again, so it prints escaped like any value from the context:

```php
<?= w(mb_strtoupper(u($title))) ?>
<?= $title |> u(...) |> mb_strtoupper(...) |> w(...) ?>
```

The result of `w()` is what an escaped render passes to a template, with the escapers, filters, and trusted classes of the render. Filters work on it, and an included template that gets it escapes it only once. See [wrap a value explicitly](#wrap-a-value-explicitly).

## Comparing wrapped values

In escaped renders, strings, arrays, and most objects reach the template as proxy objects. Identity comparison against a plain value is therefore always false, no matter what the value contains:

```php
<?php if ($item->status === 'active') : ?>        <?php /* always false */ ?>
<?php if ($item->status === Status::Active) : ?>  <?php /* always false */ ?>
```

Loose comparison is not a fix either. `==` converts the proxy via `__toString()`, which returns the escaped string, so the check works until the value contains HTML special characters:

```php
$title == 'Tom & Jerry' // false: compares against 'Tom &amp; Jerry'
```

Compare the original value instead. This works in unescaped renders too, and for values that are never wrapped:

```php
<?php if (u($item->status) === 'active') : ?>
<?php if (u($item->status) === Status::Active) : ?>
<?php if (u($title) === u($subtitle)) : ?>
<?php if (in_array(u($item->status), ['draft', 'pending'], true)) : ?>
```

`match` compares with `===` as well, so match on the original value:

```php
<?= match (u($item->status)) {
    'active' => 'Active',
    'blocked' => 'Blocked',
} ?>
```

### Shortcuts on wrapped values

Wrapped values also offer methods for common checks, which work on the original value. They exist only on wrapped values: integers, floats, booleans, and `null` are never wrapped, and neither is anything in an unescaped render, so calling one of these methods there fails with a PHP error. Use `u()` in a template that renders both ways.

`is()` compares the raw value with `===` and unwraps the other side first when it is a proxy too:

```php
<?php if ($item->status->is('active')) : ?>
<?php if ($item->status->is(Status::Active)) : ?>
<?php if ($title->is($subtitle)) : ?>
```

`is()` is available at any depth, because property access, method calls, array access, and iteration all return wrapped values again.

`in()` is the list version. It compares the raw value against each element of a plain or wrapped array with the same strict semantics, unwrapping proxy elements:

```php
<?php if ($item->status->in(['draft', 'pending'])) : ?>
<?php if ($item->status->in([Status::Draft, Status::Pending])) : ?>
```

Wrapped strings also expose `matches()`, which runs `preg_match()` on the raw value:

```php
<?php if ($slug->matches('/^[a-z0-9-]+$/')) : ?>
```

An invalid pattern throws instead of quietly returning false.

Native string functions share the problem of `==`: in a template without [`strict_types`](#passing-wrapped-values-to-php-functions), `str_contains($title, '...')` coerces the proxy via `__toString()` and searches the escaped text. Pass `u($title)`, or use the predicates of wrapped strings, which work on the raw value and accept a wrapped needle:

```php
<?php if ($title->contains('&')) : ?>
<?php if ($url->startsWith('https://')) : ?>
<?php if ($file->endsWith('.pdf')) : ?>
```

Wrapped arrays offer the reverse check. `contains()` tests strict element membership on the raw array, unwrapping a proxy argument:

```php
<?php if ($tags->contains('featured')) : ?>
```

Wrapped iterators deliberately have no `contains()`, because checking would consume a single-pass generator.

## Checking for values

`isset()` and `??` work on wrapped objects and arrays as they do on plain ones: a missing property, a missing key, and `null` count as not set.

```php
<?php if (isset($user->avatar)) : ?>
<?= $user->nickname ?? $user->name ?>
<?= $row['label'] ?? 'Untitled' ?>
```

Use `exists()` on a wrapped array when a key with a `null` value should count as present.

Wrapped strings and arrays are objects, though, so they are always truthy, even when empty, and `empty()` is false for them. Test the original value instead:

```php
<?php if (u($summary)) : ?>
<?php if (!u($items)) : ?>
<?php if (count($items) > 0) : ?>
```

## Building strings from wrapped values

Converting a wrapped value to a string escapes it. Printing relies on that, and it also happens when you concatenate or interpolate a value. The result is a plain string, which Boiler cannot tell from text that still needs escaping. It is escaped a second time when it goes back through Boiler: as a value passed to `include()`, `component()`, or `layout()`, as an argument of a template method or of a method of a wrapped object, or as a value given to `$this->add()`.

```php
<?php $this->include('heading', ['text' => "News: {$title}"]) ?>     <?php /* News: Tom &amp;amp; Jerry */ ?>
<?php $this->include('heading', ['text' => 'News: ' . u($title)]) ?> <?php /* News: Tom &amp; Jerry */ ?>
```

Build such a string from the original values with `u()`. A string that goes straight to the output, as in `<?= "News: {$title}" ?>`, is escaped once and needs nothing.

Boiler escapes the entities in a string again on purpose. If it left them alone, it could not tell an escaped `<` from an `&lt;` that belongs to the content, so HTML source in an edit form or JSON in a `data-` attribute would come back changed.

## Passing wrapped values to PHP functions

A PHP function that expects a string converts a wrapped string first, so it works on the escaped text: `strlen($title)` counts `&amp;` as five characters, `substr($title, 0, 7)` can cut an entity in half, and `rawurlencode($title)` encodes the entity instead of the character. Nothing reports this.

Declare strict types as the first statement of a template to turn these cases into errors:

```php
<?php declare(strict_types=1) ?>
```

A wrapped string passed to a `string` parameter then raises a `TypeError`. The declaration covers the calls in its own file, so layouts and included templates declare it for themselves. Pass the original value instead:

```php
<?= mb_strlen(u($title)) ?>
<?= w(mb_strimwidth(u($title), 0, 40, '…')) ?>
```

The result of such a function is a plain string, which Boiler prints as is, so wrap it with `w()` when it can contain markup. Printing, concatenation, interpolation, and comparison with `==` are not function calls and work as before.

You unwrap a value only when it leaves Boiler for PHP. Boiler itself takes wrapped values: its helpers accept wrapped paths, names, and arrays of values, as in `$this->include($partial, $row)` or `$this->yield('title', $siteName)`, and template methods, filters, and methods of wrapped objects unwrap their arguments themselves.

A function that expects an array or an object of a specific class raises a `TypeError` for a proxy with or without strict types.

## Your own PHP code

Your own PHP functions and classes get wrapped values from a template, like PHP's functions do. Boiler unwraps arguments only for its own helpers, template methods, filters, and methods of wrapped objects.

Register a helper that exists for templates as a [template method](engine.md#register-custom-template-methods). It gets the original values of its arguments, and what it returns is escaped like any other value:

```php
$engine->method('stationTitle', App\station_title(...));
```

```php
<h2><?= $this->stationTitle($station) ?></h2>
```

PHP code that gets template values anyway, such as a class a template creates or a function shared with the rest of the application, unwraps them with `Celema\Boiler\unwrap()`. It works like `u()` and is loaded with Boiler, so it also works outside a render, in a console command or a test:

```php
use function Celema\Boiler\unwrap;

function station_title(mixed $station): string
{
    $station = unwrap($station);

    // ...
}
```

## Encoding values as JSON

`json_encode()` fails the render for a wrapped value, also for one inside a plain array, because PHP would encode the proxy as `{}` instead of the value. Encode the original value:

```php
<script type="application/json"><?= json_encode(u($items), JSON_HEX_TAG) ?></script>
<div data-items="<?= w(json_encode(u($items))) ?>"></div>
```

The result is a plain string, which Boiler prints as is. Inside a `<script>` element, pass `JSON_HEX_TAG` so that a `</script>` in the data cannot end the element. In an attribute, wrap the result with `w()` to escape it.

## Escape a value explicitly

To print the result of a PHP function escaped, [`w()`](#print-what-php-made-of-a-value-with-w) is enough. Use `$this->escape()` when you want to select a named escaper, or to escape a value that would otherwise print as is, such as a trusted object or the output of a safe filter:

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

`$this->escape()` accepts strings, `Stringable` values, and Boiler's proxies of strings and of stringable objects, traversable ones included. Wrapped strings also expose `->escape()` directly. Explicit escape calls always run the selected escaper on the wrapped string, even when a safe filter would let direct output skip auto-escaping. That includes calls such as `$this->escape($html->sanitize())` and `$html->sanitize()->escape()`. Use direct output such as `<?= $html->sanitize() ?>` when you want to preserve safe filter output without escaping it again. The `escaper` argument is forwarded to the wrapper's configured escaper registry. Boiler's built-in `Escapers` registry supports constructor-seeded entries and incremental `->register()` calls, and custom escaper implementations can expose additional escaper names too.

## Wrap a value explicitly

Use `w()` when you need Boiler's proxy behavior for a raw value inside a template: to print it escaped, or for string filter methods on a literal or raw string value, especially in unescaped renders:

```php
<?= w($html)->sanitize() ?>
<?= w('<b>Boiler</b>')->stripTags('<b>') ?>
```

`w()` returns what an escaped render passes to a template, so it still returns proxies when the engine is rendering unescaped output. That includes [trusted classes](#trusted-classes): the proxy of a trusted object prints unescaped. Use `$this->escape()` to escape it anyway. A value that is wrapped already comes back as it is.

`w()` uses the escapers, filters, and trusted classes of the render in progress, so it works only while a template renders. Called elsewhere, such as in a closure that runs after the render, it throws a `LogicException`.

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

Use this only when the helper itself guarantees safe HTML. It gets the original values of its arguments, like every template method, so it must escape the ones it prints: with a helper that does not, `$this->icon($name)` puts `$name` into the markup unescaped. In escaped renders, safe methods must return `string` or `Stringable`.

## Filters

Filters are value transformations applied as virtual methods on wrapped string values inside templates:

```php
<?= $html->sanitize() ?>
<?= $title->stripTags('<b>') ?>
```

In escaped renders, string values from template context are already wrapped. In unescaped renders, or when you start from a literal string in the template, call `w()` first.

An argument can be a literal or a value from the context. The filter gets its original value either way:

```php
<?= $body->stripTags($allowedTags) ?>
```

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

When you write a custom filter, return `true` from `safe()` only when the filter output is safe HTML from arbitrary input. That includes its arguments, which arrive unwrapped: a safe filter escapes the ones it prints. When it should keep already-safe HTML safe, implement `Celema\Boiler\Contract\PreservesSafety` instead.

Register custom filters on the engine with the fluent `filter()` method. Read [the engine](engine.md) for details. Real proxy methods take precedence over filter dispatch, so `is`, `in`, `matches`, `contains`, `startsWith`, `endsWith`, `escape`, `unwrap`, and `jsonSerialize` are reserved names: a filter registered under one of them is never reachable on wrapped strings.

Use filters when you want to transform wrapped values. Use named escapers when you intentionally need a different escaping context. Use normal escaped output or `$this->escape()` when plain text output is enough.

## Trusted classes

In escaped renders, Boiler escapes the string conversion of objects. If the objects of a class print safe HTML, such as a value object for sanitized rich text, add the class to the trusted list when creating the `Engine` or when rendering a standalone `Template`. Listing a base class or an interface trusts the classes that extend or implement it.

```php
$engine = \Celema\Boiler\Engine::create(
    '/path/to/templates',
    defaults: [],
    trusted: [TrustedHtml::class],
);
```

Trust covers the string conversion only. Boiler still wraps a trusted object, so what its properties, methods, and items return is escaped like any other value:

```php
<?= $html ?>              <?php /* printed as is */ ?>
<?= $html->source ?>      <?php /* escaped */ ?>
<?= $html->excerpt(80) ?> <?php /* escaped, unless excerpt() returns a trusted object */ ?>
```

A method that produces HTML therefore returns an object of a trusted class instead of a string. Trust applies wherever such an object appears: as a context value, as an element of an array, yielded by an iterator, or returned by another object.

Use this carefully: Boiler prints the string conversion of a trusted object as is. `$this->escape($html)` still escapes it.

## Working with arrays, iterators, and objects

Boiler also wraps arrays, traversables, and objects so nested values keep the same escaping behavior inside templates.

That means this stays escaped in a normal render:

```php
<?php foreach ($items as $item) : ?>
    <li><?= $item ?></li>
<?php endforeach; ?>
```

The same applies when values come from object properties, object methods, or iterator items.

Reading a property that a wrapped object does not have fails the render. An object that defines `__get()` answers for such properties itself, as it does outside a template.

### Traversable objects

An object you can loop over, such as a collection or a menu item with children, keeps its properties and methods. Loop over it and call its methods in the same template; both return wrapped values:

```php
<?php foreach ($menu as $item) : ?>
    <li><?= $item->title() ?>
    <?php foreach ($item as $child) : ?>
        <?= $child->title() ?>
    <?php endforeach; ?>
    </li>
<?php endforeach; ?>
```

The wrapped iterator's own methods take precedence over same-named methods of the object: `toArray()`, `getIterator()`, `unwrap()`, `is()`, `in()`, and `jsonSerialize()`. Call such a method on `u($item)` when you need the object's own version.

### Loop keys

Loops over wrapped arrays and iterators wrap keys like values, so a key is escaped when you output it. Integer array keys stay integers.

```php
<?php foreach ($options as $value => $label) : ?>
    <option value="<?= $value ?>"><?= $label ?></option>
<?php endforeach; ?>
```

A wrapped key follows the rules from [comparing wrapped values](#comparing-wrapped-values): compare `u($key)`, and pass it where you need the raw string, such as an offset into an unwrapped array. Wrapped arrays accept wrapped keys directly, so `$items[$key]` works. `iterator_to_array()` cannot collect wrapped string keys; use `u($items)` or `iterator_to_array($items, false)` instead.

Nested loops over the same wrapped array or `IteratorAggregate` each get their own cursor, as with the plain value. A wrapped generator or other `Iterator` keeps its single cursor, as it does without Boiler.

## Unescaped renders

When you use `Engine::unescaped()` or `renderUnescaped()`, Boiler stops wrapping values for automatic escaping.

In that mode:

- `<?= $value ?>` outputs unescaped string content
- `u()` returns values as they are, so templates that use it work in both kinds of render
- `w()` still returns proxies when you need proxy behavior such as string filters or escaped output
- string filter methods such as `->sanitize()` only exist on wrapped string proxies, so plain strings in unescaped renders do not expose them automatically
