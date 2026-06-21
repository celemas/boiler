# Inserts

Use inserts to include another template inside the current template. They work well for partials such as cards, navigation items, or repeated rows.

Assume the following directory structure:

```text
path
`-- to
    `-- templates
        |-- page.php
        `-- item.php
```

## Insert a partial

Create `page.php`:

```php
<?php $this->insert('item', ['title' => 'Boiler']) ?>
<?php $this->insert('item', ['title' => 'Celemas']) ?>
```

Create `item.php`:

```php
<p><?= $title ?></p>
```

Rendering `page` produces:

```html
<p>Boiler</p>
<p>Celemas</p>
```

## Context inheritance

Inserted templates share the current template context by default. Any values you pass as the second argument are merged on top of that shared context.

```php
<?php
// page.php
$this->insert('item', ['title' => 'Override']);
```

If the parent template already has `$user`, `$items`, or other values, the inserted template can still access them.

## Namespaces and overrides

Insert paths use the same lookup rules as `$engine->render()`:

```php
<?php $this->insert('theme:item') ?>
```

That means inserts support:

- template directories searched in order
- namespaced paths such as `theme:item`
- templates in subdirectories such as `shared/item`

## Escape behavior

Inserts use the current render mode:

- escaped renders keep automatic escaping enabled in the inserted template
- unescaped renders keep automatic escaping disabled in the inserted template

Read [displaying values](values.md) for details.

## Slots

To let an inserted template place and repeat a block of markup you supply at the call site, pass a `slot` closure or `Slot::template()` slot. See [slots](slots.md).
