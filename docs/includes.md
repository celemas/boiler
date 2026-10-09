# Includes

Use `include()` to render another template inside the current template, as `include` does in Twig and Blade. Includes work well for partials such as cards, navigation items, or repeated rows.

Assume the following directory structure:

```text
path
`-- to
    `-- templates
        |-- page.php
        `-- item.php
```

## Include a partial

Create `page.php`:

```php
<?php $this->include('item', ['title' => 'Boiler']) ?>
<?php $this->include('item', ['title' => 'Celema']) ?>
```

Create `item.php`:

```php
<p><?= $title ?></p>
```

Rendering `page` produces:

```html
<p>Boiler</p>
<p>Celema</p>
```

## Context inheritance

Included templates share the current template context by default. Any values you pass as the second argument are merged on top of that shared context.

```php
<?php
// page.php
$this->include('item', ['title' => 'Override']);
```

If the parent template already has `$user`, `$items`, or other values, the included template can still access them.

## Namespaces and overrides

Include paths use the same lookup rules as `$engine->render()`:

```php
<?php $this->include('theme:item') ?>
```

That means includes support:

- template directories searched in order
- namespaced paths such as `theme:item`
- templates in subdirectories such as `shared/item`

## Escape behavior

Includes use the current render mode:

- escaped renders keep automatic escaping enabled in the included template
- unescaped renders keep automatic escaping disabled in the included template

Read [displaying values](values.md) for details.

## Pass a block of markup

`include()` passes values. To pass a block of markup that the included template prints with `$this->slot()`, include it as a component:

```php
<?php $this->component('card', ['title' => 'News']) ?>
<p>Text</p>
<?php $this->end() ?>
```

To repeat a block for every item of a list, see [slots](slots.md#repeat-a-block).
