<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/cart.php';

startSecureSession();

$pageTitle = 'Order Confirmed | UniShop';
$pageDescription = 'Razorpay test-mode checkout confirmation for UniShop.';
$assetBasePath = '../assets';

function successEscape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$sessionOrderId = $_SESSION['checkout_success_order_id'] ?? null;
$order = null;
$orderItems = [];
$message = 'We could not confirm your payment yet.';
$isSuccess = false;

if ($orderId && (int) $sessionOrderId === (int) $orderId) {
    $orderStmt = $pdo->prepare(
        'SELECT id, user_id, total_amount, payment_status, stripe_checkout_session_id, stripe_payment_intent_id
         FROM orders
         WHERE id = :id
         LIMIT 1'
    );
    $orderStmt->execute([':id' => $orderId]);
    $order = $orderStmt->fetch();

    if ($order && $order['payment_status'] === 'paid') {
        $itemsStmt = $pdo->prepare(
            'SELECT oi.quantity, oi.price_at_purchase, p.title
             FROM order_items oi
             INNER JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = :order_id'
        );
        $itemsStmt->execute([':order_id' => (int) $order['id']]);
        $orderItems = $itemsStmt->fetchAll();
        $message = 'Payment confirmed through Razorpay. Your order has been recorded.';
        $isSuccess = true;
        unset($_SESSION['checkout_success_order_id']);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<main id="main-content">
    <section class="auth-section" aria-labelledby="success-title">
        <div class="auth-panel">
            <p class="eyebrow">Razorpay test mode</p>
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
                        <dd><?php echo successEscape(number_format((float) $order['total_amount'], 2)); ?></dd>
                    </div>
                    <div>
                        <dt>Payment status</dt>
                        <dd><?php echo successEscape((string) $order['payment_status']); ?></dd>
                    </div>
                    <div>
                        <dt>Razorpay payment ID</dt>
                        <dd><?php echo successEscape((string) $order['stripe_payment_intent_id']); ?></dd>
                    </div>
                </dl>

                <?php if ($orderItems !== []): ?>
                    <div class="order-confirmation-items">
                        <h2>Order items</h2>
                        <?php foreach ($orderItems as $item): ?>
                            <p>
                                <?php echo successEscape($item['title']); ?>
                                x <?php echo (int) $item['quantity']; ?>
                                at <?php echo successEscape(number_format((float) $item['price_at_purchase'], 2)); ?>
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