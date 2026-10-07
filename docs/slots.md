# Slots

A slot is what a template wraps, which it prints wherever it calls `<?= $this->slot() ?>`: the page in a [layout](layouts.md), or a block of markup you hand to an inserted template, which the template can place — and repeat. Where [sections](sections.md) push content _up_ into a layout as a fixed string, a slot passes a block _down_ into a partial that decides where and how often to render it, with different data each time.

Slots are the tool for a reusable wrapper around a varying control: a field row, a table row, a card body. Pass the wrapper once and keep the varying markup at the call site.

Assume the following directory structure:

```text
path
`-- to
    `-- templates
        |-- page.php
        |-- card.php
        `-- rows.php
```

## Pass a block to a component

Use `component()` … `end()` to insert a template with a block of markup as its slot.

Create `page.php`:

```php
<?php $this->component('card', ['title' => 'News']) ?>
<p><?= $text ?></p>
<?php $this->end() ?>
```

Create `card.php`:

```php
<div class="card">
    <h2><?= $title ?></h2>
    <?= $this->slot() ?>
</div>
```

The block runs once, at the call site, before `card.php` renders, so it uses the page's variables. The card receives the finished markup: it can print it anywhere, check it with `hasSlot()`, or place it in a section. Any template can serve as a component; there are no component classes or prop declarations. Like `insert()`, `component()` shares the calling template's context and merges the values you pass on top.

Sections and components share one stack of open blocks, and `end()` closes the innermost one. Pass the template name to check which block it closes: `$this->end('card')`.

## Fill a slot from a loop

Insert the partial with `each()` and write the slot as the body of a `foreach` loop. The partial renders it with `$this->slot()`.

Create `page.php`:

```php
<?php foreach ($this->each('rows', ['items' => $items]) as $row): ?>
<input name="<?= $row['name'] ?>" value="<?= $row['value'] ?>">
<?php endforeach; ?>
```

Create `rows.php`:

```php
<ul>
<?php foreach ($this->unwrap($items) as $row): ?>
    <li><?= $this->slot($row) ?></li>
<?php endforeach; ?>
</ul>
```

The partial owns the repeated structure (the list, the row wrapper); the call site owns the control. `each()` renders `rows.php` first. Every `$this->slot($row)` call in it becomes one iteration of the loop, with the data passed to `slot()`, and the iteration's output takes the place of that call. The result appears where the loop is, once the loop has finished. Destructuring works as in any `foreach`: `as ['name' => $name, 'value' => $value]`.

A template written for `each()` also works as a component or a layout: fixed content ignores the data passed to `slot()`, so every call prints the same block.

Because the inserted template has finished when the loop body runs, it can print its slot but not inspect it. Calling `$this->slot()` inside a section capture fails the render. Sections the loop body captures come after the ones the inserted template captured.

You can leave the loop early: iterations that `continue`, `break`, `return`, or a caught exception skip stay empty. The render fails if a loop never runs, or if you break out of a loop kept in a variable, such as `$rows = $this->each(...)`. The rest of the template would otherwise end up inside the pending iteration.

## Slot data and escaping

The data passed to `$this->slot([...])` reaches the loop wrapped like other template values, so `<?= $row['name'] ?>` is escaped in an escaped render. In an [unescaped](values.md) render the loop receives the raw values. The loop body is part of the calling template: `$this` is the caller, with its context and helpers.

## Optional slots

Use `hasSlot()` when a template should work with or without a slot:

```php
<div>
<?php if ($this->hasSlot()): ?>
    <?= $this->slot() ?>
<?php else: ?>
    <em>No content</em>
<?php endif; ?>
</div>
```

`hasSlot()` is false when the slot holds nothing but whitespace, such as a page that only captures sections, and `slot()` then returns an empty string. The slot of an `each()` template always counts as filled.

Calling `$this->slot()` on a template that was inserted without one, such as with `insert()`, fails the render. Guard with `hasSlot()` whenever the slot is optional.
