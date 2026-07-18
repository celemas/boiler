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
        <?= $this->body() ?>
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

In templates, prefer PHP's alternative control structure syntax such as `if (...) : ... endif;` and `foreach (...) : ... endforeach;`. It reads better in mixed PHP and HTML files, and the docs use that style in template examples.

## Next steps

- Read [the engine](engine.md) to learn about multiple template directories, namespaces, default values, and escape modes.
- Read [displaying values](values.md) to learn when to use `$this->unwrap()`, `$this->escape()`, and `$this->wrap()`.
- Read [layouts](layouts.md), [inserts](inserts.md), and [sections](sections.md) for the main composition features.
