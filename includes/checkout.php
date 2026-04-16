<?php
declare(strict_types=1);

require_once __DIR__ . '/cart.php';

/**
 * Checkout helper functions.
 *
 * Teaching note:
 * Checkout code must load prices from the database. Never accept a total amount
 * from a form field, query string, or JavaScript calculation.
 */

function moneyToCents(float $amount): int
{
    return (int) round($amount * 100);
}

function fetchCartItemsForCheckout(PDO $pdo): array
{
    $cart = getCart();

    if ($cart === []) {
        return [
            'items' => [],
            'subtotal' => 0.0,
            'tax' => 0.0,
            'total' => 0.0,
        ];
    }

    $productIds = array_map('intval', array_keys($cart));
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    $productStmt = $pdo->prepare(
        "SELECT id, title, description, price, image_url, stock_qty
         FROM products
         WHERE id IN ({$placeholders})"
    );
    $productStmt->execute($productIds);
    $products = $productStmt->fetchAll();

    $items = [];
    $subtotal = 0.0;

    foreach ($products as $product) {
        $productId = (int) $product['id'];
        $quantity = min((int) ($cart[$productId] ?? 0), (int) $product['stock_qty']);

        if ($quantity <= 0) {
            removeCartItem($productId);
            continue;
        }

        $price = (float) $product['price'];
        $lineTotal = $price * $quantity;
        $subtotal += $lineTotal;

        $items[] = [
            'id' => $productId,
            'title' => (string) $product['title'],
            'description' => (string) $product['description'],
            'price' => $price,
            'quantity' => $quantity,
            'stock_qty' => (int) $product['stock_qty'],
            'line_total' => $lineTotal,
        ];
    }

    $tax = round($subtotal * 0.08, 2);

    return [
        'items' => $items,
        'subtotal' => $subtotal,
        'tax' => $tax,
        'total' => $subtotal + $tax,
    ];
}

function createPendingOrder(PDO $pdo, ?int $userId, array $checkoutData): int
{
    $pdo->beginTransaction();

    try {
        $orderStmt = $pdo->prepare(
            'INSERT INTO orders (user_id, total_amount, payment_status)
             VALUES (:user_id, :total_amount, :payment_status)'
        );
        $orderStmt->execute([
            ':user_id' => $userId,
            ':total_amount' => $checkoutData['total'],
            ':payment_status' => 'pending',
        ]);

        $orderId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase)
             VALUES (:order_id, :product_id, :quantity, :price_at_purchase)'
        );

        foreach ($checkoutData['items'] as $item) {
            $itemStmt->execute([
                ':order_id' => $orderId,
                ':product_id' => $item['id'],
                ':quantity' => $item['quantity'],
                ':price_at_purchase' => $item['price'],
            ]);
        }

        $pdo->commit();

        return $orderId;
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function markOrderPaymentReference(PDO $pdo, int $orderId, string $gatewayOrderId): void
{
    $stmt = $pdo->prepare(
        'UPDATE orders
         SET stripe_checkout_session_id = :stripe_checkout_session_id
         WHERE id = :id'
    );
    $stmt->execute([
        ':stripe_checkout_session_id' => $gatewayOrderId,
        ':id' => $orderId,
    ]);
}
