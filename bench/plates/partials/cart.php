<?php $this->layout('components/card', ['title' => 'Your cart', 'tone' => 'cart']) ?>

<?php if ($cart['lines']): ?>
    <ul class="cart-lines">
        <?php foreach ($cart['lines'] as $line): ?>
            <li><?= $line['qty'] ?> × <?= $this->e($line['name']) ?> <span><?= $this->money($line['total']) ?></span></li>
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
