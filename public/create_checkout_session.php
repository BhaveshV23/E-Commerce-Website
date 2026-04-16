<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../includes/checkout.php';

use Razorpay\Api\Api;

startSecureSession();
header('Content-Type: application/json');

function jsonResponse(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function razorpayAmountInPaise(float $amount): int
{
    return (int) round($amount * 100);
}

function verifyRazorpaySignature(string $orderId, string $paymentId, string $signature, string $secret): bool
{
    $payload = $orderId . '|' . $paymentId;
    $expected = hash_hmac('sha256', $payload, $secret);

    return hash_equals($expected, $signature);
}

function markOrderPaid(PDO $pdo, int $orderId, string $razorpayOrderId, string $razorpayPaymentId): void
{
    $paidStmt = $pdo->prepare(
        'UPDATE orders
         SET payment_status = :payment_status,
             stripe_checkout_session_id = :razorpay_order_id,
             stripe_payment_intent_id = :razorpay_payment_id
         WHERE id = :id'
    );
    $paidStmt->execute([
        ':payment_status' => 'paid',
        ':razorpay_order_id' => $razorpayOrderId,
        ':razorpay_payment_id' => $razorpayPaymentId,
        ':id' => $orderId,
    ]);
}

function reduceStockForOrder(PDO $pdo, int $orderId): void
{
    $itemsStmt = $pdo->prepare(
        'SELECT product_id, quantity
         FROM order_items
         WHERE order_id = :order_id'
    );
    $itemsStmt->execute([':order_id' => $orderId]);
    $items = $itemsStmt->fetchAll();

    $stockStmt = $pdo->prepare(
        'UPDATE products
         SET stock_qty = GREATEST(stock_qty - :quantity, 0)
         WHERE id = :product_id'
    );

    foreach ($items as $item) {
        $stockStmt->execute([
            ':quantity' => (int) $item['quantity'],
            ':product_id' => (int) $item['product_id'],
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    jsonResponse(['success' => false, 'message' => 'Security check failed.'], 403);
}

$action = (string) ($_POST['action'] ?? 'create_order');
$razorpayConfig = require __DIR__ . '/../config/razorpay.php';
$keyId = (string) ($razorpayConfig['key_id'] ?? '');
$keySecret = (string) ($razorpayConfig['key_secret'] ?? '');
$currency = (string) ($razorpayConfig['currency'] ?? 'INR');

if ($keyId === 'rzp_test_REPLACE_WITH_KEY_ID' || $keySecret === 'REPLACE_WITH_KEY_SECRET') {
    jsonResponse(['success' => false, 'message' => 'Add Razorpay test keys in config/razorpay.php.'], 500);
}

$autoloadPath = __DIR__ . '/../vendor/autoload.php';

if (!is_file($autoloadPath)) {
    jsonResponse(['success' => false, 'message' => 'Razorpay SDK is not installed. Run composer require razorpay/razorpay.'], 500);
}

require_once $autoloadPath;

try {
    if ($action === 'create_order') {
        $checkoutData = fetchCartItemsForCheckout($pdo);

        if ($checkoutData['items'] === []) {
            jsonResponse(['success' => false, 'message' => 'Your cart is empty.'], 400);
        }

        $amount = razorpayAmountInPaise((float) $checkoutData['total']);
        $receipt = 'cart_' . session_id() . '_' . time();
        $api = new Api($keyId, $keySecret);
        $razorpayOrder = $api->order->create([
            'receipt' => substr($receipt, 0, 40),
            'amount' => $amount,
            'currency' => $currency,
            'payment_capture' => 1,
        ]);

        $_SESSION['razorpay_checkout'] = [
            'order_id' => (string) $razorpayOrder['id'],
            'amount' => $amount,
            'currency' => $currency,
            'cart_snapshot' => getCart(),
            'created_at' => time(),
        ];

        $user = currentUser();

        jsonResponse([
            'success' => true,
            'key_id' => $keyId,
            'order_id' => (string) $razorpayOrder['id'],
            'amount' => $amount,
            'currency' => $currency,
            'name' => 'UniShop',
            'description' => 'UniShop order payment',
            'customer_name' => $user['name'] ?? '',
            'customer_email' => $user['email'] ?? '',
        ]);
    }

    if ($action === 'verify_payment') {
        $razorpayOrderId = (string) ($_POST['razorpay_order_id'] ?? '');
        $razorpayPaymentId = (string) ($_POST['razorpay_payment_id'] ?? '');
        $razorpaySignature = (string) ($_POST['razorpay_signature'] ?? '');
        $checkoutSession = $_SESSION['razorpay_checkout'] ?? null;

        if (!is_array($checkoutSession) || ($checkoutSession['order_id'] ?? '') !== $razorpayOrderId) {
            jsonResponse(['success' => false, 'message' => 'Payment session expired.'], 400);
        }

        if (!verifyRazorpaySignature($razorpayOrderId, $razorpayPaymentId, $razorpaySignature, $keySecret)) {
            unset($_SESSION['razorpay_checkout']);
            jsonResponse(['success' => false, 'message' => 'Payment verification failed.'], 400);
        }

        $checkoutData = fetchCartItemsForCheckout($pdo);

        if ($checkoutData['items'] === []) {
            jsonResponse(['success' => false, 'message' => 'Your cart is empty.'], 400);
        }

        $expectedAmount = razorpayAmountInPaise((float) $checkoutData['total']);

        if ((int) ($checkoutSession['amount'] ?? 0) !== $expectedAmount) {
            unset($_SESSION['razorpay_checkout']);
            jsonResponse(['success' => false, 'message' => 'Payment amount changed. Please try again.'], 400);
        }

        $user = currentUser();
        $userId = $user !== null ? (int) $user['id'] : null;
        $orderId = createPendingOrder($pdo, $userId, $checkoutData);

        $pdo->beginTransaction();

        try {
            markOrderPaid($pdo, $orderId, $razorpayOrderId, $razorpayPaymentId);
            reduceStockForOrder($pdo, $orderId);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        clearCart();
        unset($_SESSION['razorpay_checkout']);
        $_SESSION['checkout_success_order_id'] = $orderId;

        jsonResponse([
            'success' => true,
            'redirect_url' => 'checkout_success.php?order_id=' . $orderId,
        ]);
    }

    jsonResponse(['success' => false, 'message' => 'Invalid checkout action.'], 400);
} catch (Throwable $exception) {
    error_log('Razorpay checkout error: ' . $exception->getMessage());
    jsonResponse(['success' => false, 'message' => 'Unable to process payment. Please try again.'], 500);
}