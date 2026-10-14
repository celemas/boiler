# Quick start

Install Boiler via Composer:

```bash
composer require celema/boiler
```

Then create a directory where your PHP templates live. Assume the following directory structure:

```text
path
`-- to
    `-- templates
        |-- layout.php
        `-- page.php
```

Create `/path/to/templates/page.php`:

```php
<?php $this->layout('layout') ?>
<p>ID <?= $id ?></p>
```

Create `/path/to/templates/layout.php`:

```php
<!doctype html>
<html lang="en">
    <body>
        <?= $this->slot() ?>
    </body>
</html>
```

Now create an `Engine` instance and render the template:

```php
use Celema\Boiler\Engine;

$engine = Engine::create('/path/to/templates');
$html = $engine->render('page', ['id' => 13]);

assert(trim($html) === <<<'HTML'
<!doctype html>
<html lang="en">
    <body>
        <p>ID 13</p>
    </body>
</html>
HTML);
```

Boiler escapes strings automatically. If `id` were `'<b>13</b>'`, the output would contain `&lt;b&gt;13&lt;/b&gt;`.

Boiler escapes by passing strings, arrays, and objects to templates as wrapped values. Printing them needs nothing, but conditions and comparisons on them do: `if ($title)` is always true, for example, and `if (u($title))` tests the value. Read [common pitfalls](pitfalls.md) before you write your first condition.

In templates, prefer PHP's alternative control structure syntax such as `if (...) : ... endif;` and `foreach (...) : ... endforeach;`. It reads better in mixed PHP and HTML files, and the docs use that style in template examples.

## Editor support

Boiler runs a template with a `Celema\Boiler\Context` object as `$this`, which nothing in the template file shows. Declare it in a PHPDoc comment at the top of the template, so that editors and static analysers such as Psalm can complete and check the helpers:

```php
<?php /** @var \Celema\Boiler\Context $this */ ?>
<?php $this->layout('layout') ?>
<p>ID <?= $id ?></p>
```

Every template needs its own comment, as these tools take the type of `$this` only from the file itself. To cover your own template methods too, declare a class that lists them; see [type hints for template methods](engine.md#type-hints-for-template-methods).

When Psalm analyses templates, declare the context variables too, with the types an escaped render passes, such as `@var \Celema\Boiler\Proxy\StringProxy $title`. List `vendor/celema/boiler/src/functions.php` under `<stubs>` in Psalm's configuration, as Boiler defines `u()` and `w()` only when it renders the first template. Filter calls on wrapped values, such as `$title->upper()`, go through `__call()`, and Psalm reports them as `UndefinedMagicMethod` at error levels 1 to 4.

## Next steps

- Read [the engine](engine.md) to learn about multiple template directories, namespaces, default values, and escape modes.
- Read [common pitfalls](pitfalls.md) for what wrapped values change in conditions, comparisons, and PHP functions.
- Read [displaying values](values.md) to learn when to use `u()`, `w()`, and `$this->escape()`.
- Read [layouts](layouts.md), [includes](includes.md), [sections](sections.md), and [slots](slots.md) for the main composition features.
