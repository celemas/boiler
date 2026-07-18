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

## Template slots

Use `Slot::template()` when a slot only renders another template:

```php
<?php
use Celema\Boiler\Slot;

foreach ($fields as $field) {
    $this->insert(
        'field-wrapper',
        context: ['field' => $field],
        slot: Slot::template($field->template, context: ['field' => $field]),
    );
}
```

The slot template receives the caller context, the `Slot::template()` context, and the data passed to `$this->slot([...])`. Later values override earlier values.

## Slot data and escaping

The array you pass to `$this->slot([...])` is handed to the closure as-is or merged into the template slot context. Like every Boiler template, those values are **raw**, so escape them with `$this->escape()` when you output them. Slots keep the caller's render mode: in an escaped render `<?= $value ?>` still auto-escapes; in an [unescaped](values.md) render it does not.

A slot closure can also `return` markup instead of echoing it, and can `insert()` further templates or use the engine's other helpers — `$this` is the calling template throughout. A template slot does not receive the slot again; use a closure if you need to pass a nested slot.

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
