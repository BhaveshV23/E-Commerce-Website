<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$user = currentUser();
$pageTitle = 'My Account | UniShop';
$pageDescription = 'A protected customer account page for UniShop.';
$assetBasePath = '../assets';

require_once __DIR__ . '/../includes/header.php';
?>

<main id="main-content">
    <section class="auth-section" aria-labelledby="account-title">
        <div class="auth-panel">
            <p class="eyebrow">Protected page</p>
            <h1 id="account-title">Welcome, <?php echo htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8'); ?>.</h1>
            <p class="auth-intro">
                This page is only visible after login. Future phases will show orders, checkout history, and account settings here.
            </p>

            <dl class="account-summary">
                <div>
                    <dt>Email</dt>
                    <dd><?php echo htmlspecialchars((string) $user['email'], ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div>
                    <dt>Role</dt>
                    <dd><?php echo htmlspecialchars((string) $user['role'], ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
            </dl>

            <a class="button button-primary" href="catalog.php">Continue shopping</a>
        </div>
    </section>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
