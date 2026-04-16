<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../config/stripe.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/cart.php';

startSecureSession();

$pageTitle = 'Order Confirmed | UniShop';
$pageDescription = 'Stripe sandbox checkout confirmation for UniShop.';
$assetBasePath = '../assets';

function successEscape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$sessionId = (string) filter_input(INPUT_GET, 'session_id', FILTER_UNSAFE_RAW);
$order = null;
$orderItems = [];
$message = 'We could not confirm your payment yet.';
$isSuccess = false;

if ($sessionId !== '' && preg_match('/^cs_(test|live)_[A-Za-z0-9_]+$/', $sessionId)) {
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';

    if (is_file($autoloadPath) && STRIPE_SECRET_KEY !== 'sk_test_REPLACE_WITH_YOUR_SECRET_KEY') {
        require_once $autoloadPath;
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

        try {
            $checkoutSession = \Stripe\Checkout\Session::retrieve($sessionId);

            $orderStmt = $pdo->prepare(
                'SELECT id, user_id, total_amount, payment_status, stripe_checkout_session_id
                 FROM orders
                 WHERE stripe_checkout_session_id = :session_id
                 LIMIT 1'
            );
            $orderStmt->execute([':session_id' => $sessionId]);
            $order = $orderStmt->fetch();

            if ($order && $checkoutSession->payment_status === 'paid') {
                $expectedTotalCents = (int) round((float) $order['total_amount'] * 100);
                $stripeTotalCents = (int) $checkoutSession->amount_total;

                if ($expectedTotalCents === $stripeTotalCents) {
                    if ($order['payment_status'] !== 'paid') {
                        $pdo->beginTransaction();

                        try {
                            $paidStmt = $pdo->prepare(
                                'UPDATE orders
                                 SET payment_status = :payment_status,
                                     stripe_payment_intent_id = :payment_intent
                                 WHERE id = :id'
                            );
                            $paidStmt->execute([
                                ':payment_status' => 'paid',
                                ':payment_intent' => (string) $checkoutSession->payment_intent,
                                ':id' => (int) $order['id'],
                            ]);

                            $itemsStmt = $pdo->prepare(
                                'SELECT product_id, quantity
                                 FROM order_items
                                 WHERE order_id = :order_id'
                            );
                            $itemsStmt->execute([':order_id' => (int) $order['id']]);
                            $itemsForStock = $itemsStmt->fetchAll();

                            $stockStmt = $pdo->prepare(
                                'UPDATE products
                                 SET stock_qty = GREATEST(stock_qty - :quantity, 0)
                                 WHERE id = :product_id'
                            );

                            foreach ($itemsForStock as $item) {
                                $stockStmt->execute([
                                    ':quantity' => (int) $item['quantity'],
                                    ':product_id' => (int) $item['product_id'],
                                ]);
                            }

                            $pdo->commit();
                            $order['payment_status'] = 'paid';
                        } catch (Throwable $exception) {
                            $pdo->rollBack();
                            throw $exception;
                        }
                    }

                    clearCart();
                    unset($_SESSION['pending_order_ids'][(int) $order['id']]);
                    $message = 'Payment confirmed. Your order has been recorded.';
                    $isSuccess = true;
                } else {
                    error_log('Stripe amount mismatch for order ' . $order['id']);
                    $message = 'Payment amount verification failed. Please contact support.';
                }
            }

            if ($order) {
                $displayItemsStmt = $pdo->prepare(
                    'SELECT oi.quantity, oi.price_at_purchase, p.title
                     FROM order_items oi
                     INNER JOIN products p ON p.id = oi.product_id
                     WHERE oi.order_id = :order_id'
                );
                $displayItemsStmt->execute([':order_id' => (int) $order['id']]);
                $orderItems = $displayItemsStmt->fetchAll();
            }
        } catch (Throwable $exception) {
            error_log('Stripe Checkout confirmation failed: ' . $exception->getMessage());
            $message = 'Stripe confirmation failed. Please refresh or contact support.';
        }
    } else {
        $message = 'Stripe SDK or sandbox key is missing. Install dependencies and configure config/stripe.php.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<main id="main-content">
    <section class="auth-section" aria-labelledby="success-title">
        <div class="auth-panel">
            <p class="eyebrow">Stripe sandbox</p>
            <h1 id="success-title"><?php echo $isSuccess ? 'Order confirmed.' : 'Confirmation pending.'; ?></h1>

            <div class="alert <?php echo $isSuccess ? 'alert-success' : 'alert-error'; ?>" role="status">
                <?php echo successEscape($message); ?>
            </div>

            <?php if ($order): ?>
                <dl class="account-summary">
                    <div>
                        <dt>Order ID</dt>
                        <dd>#<?php echo (int) $order['id']; ?></dd>
                    </div>
                    <div>
                        <dt>Total paid</dt>
                        <dd>$<?php echo successEscape(number_format((float) $order['total_amount'], 2)); ?></dd>
                    </div>
                    <div>
                        <dt>Payment status</dt>
                        <dd><?php echo successEscape((string) $order['payment_status']); ?></dd>
                    </div>
                </dl>

                <?php if ($orderItems !== []): ?>
                    <div class="order-confirmation-items">
                        <h2>Order items</h2>
                        <?php foreach ($orderItems as $item): ?>
                            <p>
                                <?php echo successEscape($item['title']); ?>
                                x <?php echo (int) $item['quantity']; ?>
                                at $<?php echo successEscape(number_format((float) $item['price_at_purchase'], 2)); ?>
                            </p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <a class="button button-primary" href="catalog.php">Continue shopping</a>
        </div>
    </section>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
