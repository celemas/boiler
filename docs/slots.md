# Slots

A slot is what a template wraps. The template prints it with `<?= $this->slot() ?>`, wherever and as often as it likes. Three helpers give a template a slot:

| Helper | The slot is |
| --- | --- |
| [`layout()`](layouts.md) in the page | the page the layout wraps |
| `component('card')` … `end()` at the call site | the block between the two calls |
| `foreach ($this->each('rows') as $row)` at the call site | the body of the loop, once per row |

Where [sections](sections.md) let any template push named content _up_ into a layout, a slot passes one block _down_ into the template that wraps it. Slots are the tool for a reusable wrapper around varying markup: a card body, a field row, a table row. Write the wrapper once and keep the varying markup at the call site.

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

Use `component()` … `end()` to insert a template with the block in between as its slot.

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

The block runs once, at the call site, before `card.php` renders, so it uses the page's variables. The card receives the finished markup: it can print it anywhere, check it with `hasSlot()`, or place it in a section. Any template can serve as a component; there are no component classes or prop declarations. Like `insert()`, `component()` shares the calling template's context and merges the values you pass on top. Plain values such as the title go in as data.

Sections and components share one stack of open blocks, and `end()` closes the innermost one. Pass the template name to check which block it closes: `$this->end('card')` fails the render at that line when another block is open.

## Repeat a block with the partial's data

Use `each()` when the partial owns the repeated structure and the call site writes the markup of a row. Insert the partial with `each()` and write the row as the body of a `foreach` loop.

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

`each()` renders `rows.php` first. Every `$this->slot($row)` call in it becomes one iteration of the loop, with the data passed to `slot()`, and the iteration's output takes the place of that call. The result appears where the loop is, once the loop has finished. Destructuring works as in any `foreach`: `as ['name' => $name, 'value' => $value]`.

The data passed to `slot([...])` reaches the loop wrapped like other template values, so `<?= $row['name'] ?>` is escaped in an escaped render; in an [unescaped](values.md) render the loop receives the raw values. The loop body is part of the calling template: `$this` is the caller, with its context and helpers.

Because the inserted template has finished when the loop body runs, it can place its slot but not inspect it: `slot()` returns a placeholder, which the output of the loop body replaces once the loop has finished, in the template's output and in the sections it captured. Functions applied to the result, such as `trim()`, escaping, or case filters, change the placeholder, not the output of the loop body. Sections the loop body captures come after the ones the inserted template captured.

You can leave the loop early: iterations that `continue`, `break`, `return`, or a caught exception skip stay empty. The render fails if a loop never runs, or if you break out of a loop kept in a variable, such as `$rows = $this->each(...)`. The rest of the template would otherwise end up inside the pending iteration.

## One template for every kind of slot

A layout's page and a component's block are fixed markup, so they ignore the data passed to `slot()`. A template written for `each()` therefore also works as a component or a layout, and every `slot($row)` call prints the same block:

```php
<?php $this->component('rows', ['items' => $items]) ?>
<b>Same in every row</b>
<?php $this->end() ?>
```

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

`hasSlot()` is false when the slot holds nothing but whitespace, such as an empty component block or a page that only captures sections, and `slot()` then returns an empty string. The slot of an `each()` template always counts as filled.

## Error handling

- Calling `$this->slot()` in a template that has no slot, such as one inserted with `insert()`, raises a render error. Guard with `hasSlot()` whenever the slot is optional.
- A `component()` block must be closed with `$this->end()` in the template that opened it. An unclosed block raises a render error that points to the `component()` call.
- `component()` resolves its template right away, so a missing template fails at that call, before the block runs.
- An `each()` loop that never runs, or one kept in a variable and left with `break`, raises a render error that points to the `each()` call.
