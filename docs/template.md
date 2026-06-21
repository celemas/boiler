# Template

Use `Template` when you want to render a single template file directly instead of resolving it through an `Engine`.

## Create a standalone template

Pass the full file path to the constructor:

```php
$template = new \Celemas\Boiler\Template('/path/to/templates/page.php');
```

Boiler creates an internal `Engine` automatically and uses the directory that contains the file as the template root.

## Render the template

Use the same render methods as on `Engine`:

```php
$html = $template->render(['id' => 13]);
$html = $template->renderEscaped(['id' => 13]);
$html = $template->renderUnescaped(['id' => 13]);
```

## Register custom template methods

Register helpers on a standalone template with `method()`. They are available as `$this->methodName()` inside the template, its inserts, and its layouts.

```php
$template = new \Celemas\Boiler\Template('/path/to/templates/page.php');

$template->method('upper', static fn(string $value): string => strtoupper($value));

$html = $template->render(['text' => 'Boiler']);
```

If `page.php` contains:

```php
<h2><?= $this->upper($text) ?></h2>
```

Boiler unwraps proxy arguments before it calls your method, so the callable receives normal PHP values. In escaped renders, Boiler wraps the return value again before exposing it to the template. In unescaped renders, it returns the unwrapped value.

Pass `safe: true` when a helper returns safe HTML:

```php
use function App\Template\icon;

$template = new \Celemas\Boiler\Template('/path/to/templates/page.php');
$template->method('icon', icon(...), safe: true);
```

That lets you render the helper directly in an escaped template:

```php
<?= $this->icon('check') ?>
```

In escaped renders, safe methods must return `string` or `Stringable`. Boiler exposes that return value as a safe wrapped string, so direct output skips auto-escaping and safety-preserving string filters can still be chained. In unescaped renders, Boiler still returns the unwrapped value.

Use `safe: true` only when the helper itself guarantees safe HTML for the values it accepts.

## Layouts, inserts, and slots

Standalone templates can use the same composition helpers as engine-backed renders:

- `$this->layout('layout')`
- `$this->insert('partial')`
- `$this->slot()` and `$this->hasSlot()` inside inserted templates that receive a closure or `Slot::template()` slot
- sections via `$this->begin()` and `$this->section()`

Those template references are resolved relative to the directory that contains the original template file.

## Configure trusted classes

When you render a standalone template, pass the trusted list directly to the render call:

```php
$html = $template->render(
    ['value' => new TrustedHtml()],
    trusted: [TrustedHtml::class],
);
```

## Reuse a template instance

A `Template` instance can be rendered multiple times safely:

```php
$template = new \Celemas\Boiler\Template('/path/to/templates/page.php');

$first = $template->render(['id' => 1]);
$second = $template->render(['id' => 2]);
```

Boiler resets per-render state such as assigned layouts and captured sections between renders.

## Error handling

- Boiler raises `LookupException` when the template file or its directory does not exist.
- Boiler raises `LookupException` when a standalone layout or insert cannot be resolved relative to the template directory.
- Boiler raises `RenderException` when the template itself throws during render, for example because of a parse error or runtime error inside the template.
