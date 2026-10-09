<tr>
    <td><?= $product['id'] ?></td>
    <td><?= $this->escape($this->wrap($product['sku'])->trim()->upper()) ?></td>
    <td><?= $this->escape($product['name']) ?></td>
    <td><?= $this->escape($this->wrap($product['vendor'])->trim()->upper()) ?></td>
    <td><?php $this->include('price-cell', ['product' => $product]) ?></td>
    <td>
        <?php if ($product['inStock'] && $product['stock'] > 5): ?>
            <span class="in-stock">In Stock</span>
        <?php elseif ($product['inStock']): ?>
            <span class="low-stock">Low Stock (<?= $product['stock'] ?>)</span>
        <?php elseif ($product['preorder']): ?>
            <span class="preorder">Preorder</span>
        <?php else: ?>
            <span class="out-of-stock">Out of Stock</span>
        <?php endif ?>
    </td>
    <td><?php $this->include('rating-stars', ['product' => $product]) ?></td>
    <td>
        <?php foreach ($product['tags'] as $tag): ?>
            <span class="tag"><?= $this->escape($tag) ?></span>
        <?php endforeach ?>
    </td>
    <td>
        <?php if (count($product['badges']) > 0): ?>
            <?php foreach ($product['badges'] as $badge): ?>
                <span class="badge"><?= $this->escape($this->wrap($badge)->trim()->upper()) ?></span>
            <?php endforeach ?>
        <?php else: ?>
            <span class="badge none">NONE</span>
        <?php endif ?>
    </td>
</tr>
