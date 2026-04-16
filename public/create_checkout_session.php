<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../config/stripe.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../includes/checkout.php';

startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    cartFlash('Security check failed. Please try again.', 'error');
    header('Location: cart.php');
    exit;
}

$autoloadPath = __DIR__ . '/../vendor/autoload.php';

if (!is_file($autoloadPath)) {
    cartFlash('Stripe SDK is not installed. Run composer install before checkout.', 'error');
    header('Location: cart.php');
    exit;
}

if (STRIPE_SECRET_KEY === 'sk_test_REPLACE_WITH_YOUR_SECRET_KEY') {
    cartFlash('Add your Stripe sandbox secret key in config/stripe.php before checkout.', 'error');
    header('Location: cart.php');
    exit;
}

require_once $autoloadPath;

$checkoutData = fetchCartItemsForCheckout($pdo);

if ($checkoutData['items'] === []) {
    cartFlash('Your cart is empty.', 'error');
    header('Location: cart.php');
    exit;
}

$user = currentUser();
$userId = $user !== null ? (int) $user['id'] : null;
$orderId = createPendingOrder($pdo, $userId, $checkoutData);

$lineItems = [];

foreach ($checkoutData['items'] as $item) {
    $lineItems[] = [
        'price_data' => [
            'currency' => STRIPE_CURRENCY,
            'product_data' => [
                'name' => $item['title'],
                'description' => substr($item['description'], 0, 250),
            ],
            'unit_amount' => moneyToCents((float) $item['price']),
        ],
        'quantity' => (int) $item['quantity'],
    ];
}

if ($checkoutData['tax'] > 0) {
    $lineItems[] = [
        'price_data' => [
            'currency' => STRIPE_CURRENCY,
            'product_data' => [
                'name' => 'Estimated tax',
            ],
            'unit_amount' => moneyToCents((float) $checkoutData['tax']),
        ],
        'quantity' => 1,
    ];
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    $checkoutSession = \Stripe\Checkout\Session::create([
        'mode' => 'payment',
        'payment_method_types' => ['card'],
        'line_items' => $lineItems,
        'success_url' => APP_BASE_URL . '/checkout_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => APP_BASE_URL . '/checkout_cancel.php?order_id=' . $orderId,
        'metadata' => [
            'order_id' => (string) $orderId,
        ],
    ]);

    markOrderStripeSession($pdo, $orderId, $checkoutSession->id);
    $_SESSION['pending_order_ids'][$orderId] = true;

    header('Location: ' . $checkoutSession->url, true, 303);
    exit;
} catch (Throwable $exception) {
    error_log('Stripe Checkout Session creation failed: ' . $exception->getMessage());
    cartFlash('Unable to start Stripe checkout. Please try again.', 'error');
    header('Location: cart.php');
    exit;
}
