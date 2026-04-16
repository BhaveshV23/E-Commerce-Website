<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/cart.php';

startSecureSession();

$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if ($orderId && !empty($_SESSION['pending_order_ids'][$orderId])) {
    $cancelStmt = $pdo->prepare(
        'UPDATE orders
         SET payment_status = :payment_status
         WHERE id = :id
           AND payment_status = :pending_status'
    );
    $cancelStmt->execute([
        ':payment_status' => 'failed',
        ':id' => $orderId,
        ':pending_status' => 'pending',
    ]);

    unset($_SESSION['pending_order_ids'][$orderId]);
}

cartFlash('Checkout was cancelled. Your cart is still available.', 'error');
header('Location: cart.php');
exit;
