<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/cart.php';

startSecureSession();
ensureCart();

$pageTitle = 'Shopping Cart | UniShop';
$pageDescription = 'Review and update your UniShop shopping cart.';
$assetBasePath = '../assets';
$newsletterRedirect = 'cart.php#newsletter';

function cartEscape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cartImageUrl(string $url): string
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return '../assets/images/product-placeholder.svg';
    }

    $scheme = parse_url($url, PHP_URL_SCHEME);

    return in_array($scheme, ['http', 'https'], true) ? $url : '../assets/images/product-placeholder.svg';
}

$cart = getCart();
$cartItems = [];
$subtotal = 0.0;
$flash = consumeCartFlash();

if ($cart !== []) {
    $productIds = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    $productStmt = $pdo->prepare(
        "SELECT id, title, description, price, image_url, stock_qty
         FROM products
         WHERE id IN ({$placeholders})"
    );
    $productStmt->execute($productIds);
    $products = $productStmt->fetchAll();

    foreach ($products as $product) {
        $productId = (int) $product['id'];
        $stockQty = (int) $product['stock_qty'];
        $quantity = min((int) ($cart[$productId] ?? 0), max(0, $stockQty));

        if ($quantity <= 0) {
            removeCartItem($productId);
            continue;
        }

        if ($quantity !== (int) $cart[$productId]) {
            updateCartItem($productId, $quantity, $stockQty);
        }

        $lineTotal = (float) $product['price'] * $quantity;
        $subtotal += $lineTotal;

        $cartItems[] = [
            'id' => $productId,
            'title' => (string) $product['title'],
            'description' => (string) $product['description'],
            'price' => (float) $product['price'],
            'image_url' => (string) $product['image_url'],
            'stock_qty' => $stockQty,
            'quantity' => $quantity,
            'line_total' => $lineTotal,
        ];
    }

    foreach ($productIds as $productId) {
        $found = false;

        foreach ($cartItems as $item) {
            if ((int) $item['id'] === $productId) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            removeCartItem($productId);
        }
    }
}

$estimatedTax = round($subtotal * 0.08, 2);
$estimatedTotal = $subtotal + $estimatedTax;

require_once __DIR__ . '/../includes/header.php';
?>

<main id="main-content">
    <section class="section cart-section" aria-labelledby="cart-title">
        <div class="cart-heading">
            <div>
                <p class="eyebrow">Session cart</p>
                <h1 id="cart-title">Your shopping cart.</h1>
                <p class="auth-intro">
                    Quantities live in the PHP session. Prices are loaded from MySQL each time this page opens.
                </p>
            </div>
            <a class="button button-secondary catalog-reset" href="catalog.php">Continue shopping</a>
        </div>

        <?php if ($flash !== null): ?>
            <div class="alert <?php echo $flash['type'] === 'error' ? 'alert-error' : 'alert-success'; ?>" role="status">
                <?php echo cartEscape((string) $flash['message']); ?>
            </div>
        <?php endif; ?>

        <?php if ($cartItems === []): ?>
            <div class="empty-state">
                <h2>Your cart is empty.</h2>
                <p>Add products from the catalog before checkout.</p>
                <a class="button button-primary" href="catalog.php">Browse catalog</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-items" aria-label="Cart items">
                    <?php foreach ($cartItems as $item): ?>
                        <article class="cart-item">
                            <img
                                class="cart-item-image"
                                src="<?php echo cartEscape(cartImageUrl($item['image_url'])); ?>"
                                alt="<?php echo cartEscape($item['title']); ?>"
                                loading="lazy"
                            >

                            <div class="cart-item-details">
                                <h2><?php echo cartEscape($item['title']); ?></h2>
                                <p><?php echo cartEscape($item['description']); ?></p>
                                <p class="stock"><?php echo (int) $item['stock_qty']; ?> available</p>
                            </div>

                            <div class="cart-item-actions">
                                <p class="price">$<?php echo cartEscape(number_format($item['price'], 2)); ?></p>

                                <form class="cart-quantity-form" method="post" action="cart_action.php">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?php echo (int) $item['id']; ?>">
                                    <input type="hidden" name="redirect" value="cart.php">

                                    <label for="quantity-<?php echo (int) $item['id']; ?>">Quantity</label>
                                    <input
                                        type="number"
                                        id="quantity-<?php echo (int) $item['id']; ?>"
                                        name="quantity"
                                        value="<?php echo (int) $item['quantity']; ?>"
                                        min="0"
                                        max="<?php echo min(99, (int) $item['stock_qty']); ?>"
                                    >

                                    <button class="button button-primary" type="submit">Update</button>
                                </form>

                                <form method="post" action="cart_action.php">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?php echo (int) $item['id']; ?>">
                                    <input type="hidden" name="quantity" value="0">
                                    <input type="hidden" name="redirect" value="cart.php">
                                    <button class="button button-danger" type="submit">Remove</button>
                                </form>

                                <p class="line-total">
                                    Line total: $<?php echo cartEscape(number_format($item['line_total'], 2)); ?>
                                </p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <aside class="cart-summary" aria-labelledby="summary-title">
                    <h2 id="summary-title">Order summary</h2>
                    <dl>
                        <div>
                            <dt>Subtotal</dt>
                            <dd>$<?php echo cartEscape(number_format($subtotal, 2)); ?></dd>
                        </div>
                        <div>
                            <dt>Estimated tax</dt>
                            <dd>$<?php echo cartEscape(number_format($estimatedTax, 2)); ?></dd>
                        </div>
                        <div class="cart-summary-total">
                            <dt>Estimated total</dt>
                            <dd>$<?php echo cartEscape(number_format($estimatedTotal, 2)); ?></dd>
                        </div>
                    </dl>
                    <p>Checkout opens Stripe Sandbox. Use Stripe test card 4242 4242 4242 4242.</p>
                    <form method="post" action="create_checkout_session.php">
                        <?php echo csrfField(); ?>
                        <button class="button button-primary" type="submit">Checkout with Stripe</button>
                    </form>
                </aside>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
