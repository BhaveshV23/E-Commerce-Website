<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

$admin = currentAdmin();

function adminEscape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | UniShop</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="admin-body">
    <header class="admin-topbar">
        <a class="brand" href="dashboard.php">UniShop Admin</a>
        <nav aria-label="Admin top navigation">
            <a href="../public/index.php">View Store</a>
            <a href="../public/logout.php">Logout</a>
        </nav>
    </header>

    <div class="admin-shell">
        <aside class="admin-sidebar" aria-label="Admin sidebar navigation">
            <a class="active-filter" href="dashboard.php">Dashboard</a>
            <a href="products.php">Products</a>
            <a href="#">Orders</a>
            <a href="#">Users</a>
            <a href="#">Security Logs</a>
        </aside>

        <main class="admin-main">
            <p class="eyebrow">Role-based dashboard</p>
            <h1>Welcome, <?php echo adminEscape((string) $admin['name']); ?>.</h1>
            <p class="auth-intro">Manage store data from one protected admin area.</p>

            <section class="admin-card-grid" aria-label="Dashboard shortcuts">
                <a class="admin-card" href="products.php">
                    <span>Products</span>
                    <strong>Manage Products</strong>
                    <p>Add, edit, delete, and publish catalog products.</p>
                </a>

                <a class="admin-card" href="#">
                    <span>Orders</span>
                    <strong>Manage Orders</strong>
                    <p>Review customer purchases in a future admin phase.</p>
                </a>

                <a class="admin-card" href="#">
                    <span>Users</span>
                    <strong>Manage Users</strong>
                    <p>Audit customers and admin accounts later.</p>
                </a>

                <a class="admin-card" href="#">
                    <span>Logs</span>
                    <strong>Security Logs</strong>
                    <p>Inspect suspicious requests and login failures later.</p>
                </a>
            </section>
        </main>
    </div>
</body>
</html>
