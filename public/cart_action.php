<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/cart.php';

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

$action = (string) ($_POST['action'] ?? '');
$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 0, 'max_range' => 99],
]);

$redirect = (string) ($_POST['redirect'] ?? 'cart.php');
$allowedRedirects = ['catalog.php', 'cart.php'];

if (!in_array($redirect, $allowedRedirects, true)) {
    $redirect = 'cart.php';
}

if (!$productId) {
    cartFlash('Invalid product selected.', 'error');
    header('Location: ' . $redirect);
    exit;
}

$productStmt = $pdo->prepare(
    'SELECT id, title, stock_qty
     FROM products
     WHERE id = :id
     LIMIT 1'
);
$productStmt->execute([':id' => $productId]);
$product = $productStmt->fetch();

if (!$product) {
    cartFlash('That product is no longer available.', 'error');
    header('Location: ' . $redirect);
    exit;
}

$stockQty = (int) $product['stock_qty'];

if ($stockQty <= 0 && $action !== 'remove') {
    cartFlash('That product is currently out of stock.', 'error');
    header('Location: ' . $redirect);
    exit;
}

switch ($action) {
    case 'add':
        addToCart((int) $product['id'], max(1, (int) $quantity), $stockQty);
        cartFlash('Product added to cart.');
        break;

    case 'update':
        updateCartItem((int) $product['id'], (int) $quantity, $stockQty);
        cartFlash('Cart quantity updated.');
        break;

    case 'remove':
        removeCartItem((int) $product['id']);
        cartFlash('Product removed from cart.');
        break;

    default:
        cartFlash('Invalid cart action.', 'error');
        break;
}

header('Location: ' . $redirect);
exit;
