<?php
declare(strict_types=1);

/**
 * Shared site header.
 *
 * Teaching note:
 * Reusable includes reduce duplication and make security improvements easier.
 * For example, a future Content Security Policy can be added here once.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/security_logger.php';

logSuspiciousRequestParameters();

$pageTitle = $pageTitle ?? 'E-Commerce University Store';
$pageDescription = $pageDescription ?? 'A secure, modular PHP and MySQL e-commerce learning project.';
$assetBasePath = $assetBasePath ?? '../assets';
$canonicalUrl = $canonicalUrl ?? null;
$openGraphImage = $openGraphImage ?? null;
$structuredData = $structuredData ?? [];
$currentUser = currentUser();
$cartCount = getCartCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($canonicalUrl !== null): ?>
        <link rel="canonical" href="<?php echo htmlspecialchars((string) $canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:type" content="website">
    <?php if ($openGraphImage !== null): ?>
        <meta property="og:image" content="<?php echo htmlspecialchars((string) $openGraphImage, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBasePath, ENT_QUOTES, 'UTF-8'); ?>/css/styles.css">
    <?php foreach ($structuredData as $schemaBlock): ?>
        <script type="application/ld+json"><?php echo json_encode($schemaBlock, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    <?php endforeach; ?>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <header class="site-header">
        <nav class="navbar" aria-label="Primary navigation">
            <a class="brand" href="index.php" aria-label="E-Commerce University Store home">
                UniShop
            </a>

            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-menu">
                <span class="nav-toggle-line"></span>
                <span class="nav-toggle-line"></span>
                <span class="nav-toggle-line"></span>
                <span class="sr-only">Toggle navigation menu</span>
            </button>

            <ul class="nav-menu" id="primary-menu">
                <li><a href="index.php">Home</a></li>
                <li><a href="catalog.php">Catalog</a></li>
                <li><a href="index.php#categories">Categories</a></li>
                <li><a href="index.php#featured-products">Featured</a></li>
                <li><a href="cart.php">Cart<?php echo $cartCount > 0 ? ' (' . (int) $cartCount . ')' : ''; ?></a></li>
                <?php if ($currentUser !== null): ?>
                    <li><a href="account.php">My Account</a></li>
                    <li><a class="nav-cta" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="register.php">Register</a></li>
                    <li><a class="nav-cta" href="login.php">Sign In</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
