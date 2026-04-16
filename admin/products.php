<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/admin_auth.php';

requireAdmin();

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function adminImageSrc(string $path): string
{
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        return $path;
    }

    return '../' . ltrim($path, '/');
}

$productsStmt = $pdo->prepare(
    'SELECT p.id, p.title, p.price, p.discount_percent, p.stock_qty, p.image_url, c.name AS category_name
     FROM products p
     INNER JOIN categories c ON c.id = p.category_id
     ORDER BY p.created_at DESC, p.id DESC'
);
$productsStmt->execute();
$products = $productsStmt->fetchAll();
$flash = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products | UniShop Admin</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="admin-body">
    <header class="admin-topbar">
        <a class="brand" href="dashboard.php">UniShop Admin</a>
        <nav aria-label="Admin top navigation">
            <a href="../public/catalog.php">View Catalog</a>
            <a href="../public/logout.php">Logout</a>
        </nav>
    </header>

    <div class="admin-shell">
        <aside class="admin-sidebar" aria-label="Admin sidebar navigation">
            <a href="dashboard.php">Dashboard</a>
            <a class="active-filter" href="products.php">Products</a>
            <a href="#">Orders</a>
            <a href="#">Users</a>
            <a href="#">Security Logs</a>
        </aside>

        <main class="admin-main">
            <div class="admin-page-heading">
                <div>
                    <p class="eyebrow">Product management</p>
                    <h1>Products</h1>
                    <p class="auth-intro">Changes here update the homepage, catalog, and product pages automatically.</p>
                </div>
                <a class="button button-primary" href="add_product.php">Add Product</a>
            </div>

            <?php if ($flash !== ''): ?>
                <div class="alert alert-success" role="status"><?php echo h($flash); ?></div>
            <?php endif; ?>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Price</th>
                            <th>Discount</th>
                            <th>Stock</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo (int) $product['id']; ?></td>
                                <td>
                                    <img class="admin-thumb" src="<?php echo h(adminImageSrc((string) $product['image_url'])); ?>" alt="<?php echo h((string) $product['title']); ?>">
                                </td>
                                <td><?php echo h((string) $product['title']); ?></td>
                                <td>$<?php echo h(number_format((float) $product['price'], 2)); ?></td>
                                <td><?php echo h(number_format((float) $product['discount_percent'], 2)); ?>%</td>
                                <td><?php echo (int) $product['stock_qty']; ?></td>
                                <td><?php echo h((string) $product['category_name']); ?></td>
                                <td class="admin-actions">
                                    <a class="button button-secondary admin-button" href="edit_product.php?id=<?php echo (int) $product['id']; ?>">Edit</a>
                                    <a class="button button-danger admin-button" href="delete_product.php?id=<?php echo (int) $product['id']; ?>">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if ($products === []): ?>
                            <tr>
                                <td colspan="8">No products found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
