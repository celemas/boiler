# Slots

A slot is a block of markup you hand to an inserted template, which the template can place — and repeat — wherever it calls `$this->slot()`. Where [sections](sections.md) push content _up_ into a layout as a fixed string, a slot passes a block _down_ into a partial that decides where and how often to render it, with different data each time.

Slots are the tool for a reusable wrapper around a varying control: a field row, a table row, a card body. Pass the wrapper once and keep the varying markup at the call site.

Assume the following directory structure:

```text
path
`-- to
    `-- templates
        |-- page.php
        `-- rows.php
```

## Pass a slot

Give `insert()` a closure as the `slot` argument. The inserted template renders it with `$this->slot()`.

Create `page.php`:

```php
<?php $this->insert('rows', ['items' => $items], slot: function (array $row): void { ?>
<input name="<?= $this->escape($row['name']) ?>" value="<?= $this->escape($row['value']) ?>">
<?php }); ?>
```

Create `rows.php`:

```php
<ul>
<?php foreach ($this->unwrap($items) as $row): ?>
    <li><?php $this->slot($row); ?></li>
<?php endforeach; ?>
</ul>
```

The partial owns the repeated structure (the list, the row wrapper); the call site owns the control. `rows.php` calls `$this->slot()` once per item, passing that item's data, and the closure renders with it.

## Fill a slot from a loop

Use `each()` instead of `insert()` to write the slot as the body of a `foreach` loop, without a closure. `rows.php` stays the same:

```php
<?php foreach ($this->each('rows', ['items' => $items]) as $row): ?>
<input name="<?= $row['name'] ?>" value="<?= $row['value'] ?>">
<?php endforeach; ?>
```

`each()` renders `rows.php` first. Every `$this->slot($row)` call in it becomes one iteration of the loop, with the data passed to `slot()`, and the iteration's output takes the place of that call. The result appears where the loop is, once the loop has finished. Unlike the data passed to a closure, the loop data is wrapped like other template values, so `<?= $row['name'] ?>` is escaped in an escaped render. Destructuring works as in any `foreach`: `as ['name' => $name, 'value' => $value]`.

Because the inserted template has finished when the loop body runs, it can print its slot but not inspect it. Calling `$this->slot()` inside a section capture fails the render; use a closure slot for that. Sections the loop body captures come after the ones the inserted template captured.

You can leave the loop early: iterations that `continue`, `break`, `return`, or a caught exception skip stay empty. The render fails if a loop never runs, or if you break out of a loop kept in a variable, such as `$rows = $this->each(...)`. The rest of the template would otherwise end up inside the pending iteration.

## Slot data and escaping

The array you pass to `$this->slot([...])` is handed to the closure as-is. Like every Boiler template, those values are **raw**, so escape them with `$this->escape()` when you output them. Slots keep the caller's render mode: in an escaped render `<?= $value ?>` still auto-escapes; in an [unescaped](values.md) render it does not.

A slot closure can also `return` markup instead of echoing it, and can `insert()` further templates or use the engine's other helpers — `$this` is the calling template throughout.

## Optional slots

Use `hasSlot()` when a template should work with or without a slot:

```php
<div>
<?php if ($this->hasSlot()): ?>
    <?php $this->slot(); ?>
<?php else: ?>
    <em>No content</em>
<?php endif; ?>
</div>
```

Calling `$this->slot()` on a template that was inserted without one throws a `RuntimeException`. Guard with `hasSlot()` whenever the slot is optional.
