<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';

/**
 * Session-based shopping cart helpers.
 *
 * Teaching note:
 * The cart stores product IDs and quantities only. Prices and product names are
 * loaded from the database when needed so users cannot submit fake prices.
 */

function ensureCart(): void
{
    startSecureSession();

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function getCart(): array
{
    ensureCart();

    return $_SESSION['cart'];
}

function getCartCount(): int
{
    ensureCart();

    return array_sum(array_map('intval', $_SESSION['cart']));
}

function addToCart(int $productId, int $quantity, int $stockQty): void
{
    ensureCart();

    if ($productId <= 0 || $quantity <= 0 || $stockQty <= 0) {
        return;
    }

    $currentQuantity = (int) ($_SESSION['cart'][$productId] ?? 0);
    $_SESSION['cart'][$productId] = min($currentQuantity + $quantity, $stockQty);
}

function updateCartItem(int $productId, int $quantity, int $stockQty): void
{
    ensureCart();

    if ($productId <= 0) {
        return;
    }

    if ($quantity <= 0) {
        removeCartItem($productId);
        return;
    }

    $_SESSION['cart'][$productId] = min($quantity, max(0, $stockQty));
}

function removeCartItem(int $productId): void
{
    ensureCart();
    unset($_SESSION['cart'][$productId]);
}

function clearCart(): void
{
    ensureCart();
    $_SESSION['cart'] = [];
}

function cartFlash(string $message, string $type = 'success'): void
{
    startSecureSession();
    $_SESSION['cart_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function consumeCartFlash(): ?array
{
    startSecureSession();

    if (empty($_SESSION['cart_flash']) || !is_array($_SESSION['cart_flash'])) {
        return null;
    }

    $flash = $_SESSION['cart_flash'];
    unset($_SESSION['cart_flash']);

    return $flash;
}
