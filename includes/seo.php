<?php
declare(strict_types=1);

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value !== '' ? $value : 'product';
}

function appUrl(string $path = ''): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';

    return $scheme . '://' . $host . '/' . ltrim($path, '/');
}

function productUrl(string $slug): string
{
    return 'product.php?slug=' . urlencode($slug);
}

function buildProductSchema(array $product): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => (string) $product['title'],
        'description' => (string) $product['description'],
        'image' => (string) $product['image_url'],
        'sku' => 'UNI-' . (int) $product['id'],
        'category' => (string) $product['category_name'],
        'offers' => [
            '@type' => 'Offer',
            'priceCurrency' => 'USD',
            'price' => number_format((float) $product['price'], 2, '.', ''),
            'availability' => (int) $product['stock_qty'] > 0
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'url' => appUrl(productUrl((string) $product['slug'])),
        ],
    ];
}
