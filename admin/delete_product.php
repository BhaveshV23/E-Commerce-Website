<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/csrf.php';

requireAdmin();

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$productId) {
    http_response_code(404);
    exit('Product not found.');
}

$productStmt = $pdo->prepare('SELECT id, title FROM products WHERE id = :id LIMIT 1');
$productStmt->execute([':id' => $productId]);
$product = $productStmt->fetch();

if (!$product) {
    http_response_code(404);
    exit('Product not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Security check failed.');
    }

    $confirm = (string) ($_POST['confirm_delete'] ?? '');

    if ($confirm === 'yes') {
        $deleteStmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $deleteStmt->execute([':id' => (int) $product['id']]);
        $_SESSION['admin_flash'] = 'Product deleted successfully.';
        header('Location: products.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Product | UniShop Admin</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="admin-body">
    <header class="admin-topbar">
        <a class="brand" href="dashboard.php">UniShop Admin</a>
        <nav aria-label="Admin top navigation"><a href="products.php">Back to Products</a></nav>
    </header>

    <main class="admin-main admin-form-page">
        <p class="eyebrow">Confirm deletion</p>
        <h1>Delete Product</h1>
        <div class="alert alert-error" role="alert">
            Delete <?php echo h((string) $product['title']); ?>? This action cannot be undone.
        </div>

        <form class="admin-form" method="post">
            <?php echo csrfField(); ?>
            <input type="hidden" name="confirm_delete" value="yes">
            <button class="button button-danger" type="submit">Yes, Delete Product</button>
            <a class="button button-secondary catalog-reset" href="products.php">Cancel</a>
        </form>
    </main>
</body>
</html>
