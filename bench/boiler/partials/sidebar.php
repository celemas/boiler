<nav class="category-tree" aria-label="Categories">
    <h2>Categories</h2>
    <ul>
        <?php foreach ($categories as $entry): ?>
            <li class="<?= $entry['current'] ? 'is-current' : 'is-other' ?>">
                <a href="<?= $entry['url'] ?>"><?= $entry['name'] ?></a>
                <small>(<?= $entry['count'] ?>)</small>
            </li>
        <?php endforeach ?>
    </ul>
</nav>

<?php $this->component('components/card', ['title' => 'Your cart', 'tone' => 'cart']) ?>
    <?php if (count($cart['lines']) > 0): ?>
        <ul class="cart-lines">
            <?php foreach ($cart['lines'] as $line): ?>
                <li><?= $line['qty'] ?> × <?= $line['name'] ?> <span><?= $this->money($line['total']) ?></span></li>
            <?php endforeach ?>
        </ul>
        <p class="cart-total">Subtotal: <?= $this->money($cart['subtotal']) ?></p>
        <?php if ($cart['subtotal'] >= $campaign['threshold']): ?>
            <p class="cart-shipping">You get free shipping.</p>
        <?php else: ?>
            <p class="cart-shipping">Add <?= $this->money($campaign['threshold'] - $cart['subtotal']) ?> for free shipping.</p>
        <?php endif ?>
    <?php else: ?>
        <p>Your cart is empty.</p>
    <?php endif ?>
<?php $this->end() ?>

<?php $this->component('components/card', ['title' => 'Need help?', 'tone' => 'support']) ?>
    <p><a href="mailto:<?= $site->support->email ?>"><?= $site->support->email ?></a></p>
    <p><?= $site->support->phone ?> · <?= $site->support->hours ?></p>
<?php $this->end() ?>
