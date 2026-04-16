<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/seo.php';

$pageTitle = 'UniShop | Secure E-Commerce Learning Store';
$pageDescription = 'A responsive PHP e-commerce homepage built with secure, modular architecture.';
$assetBasePath = '../assets';
$canonicalUrl = appUrl('index.php');
$newsletterRedirect = 'index.php#newsletter';
$structuredData = [[
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => 'UniShop',
    'url' => $canonicalUrl,
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => appUrl('catalog.php?q={search_term_string}'),
        'query-input' => 'required name=search_term_string',
    ],
]];

function homeEscape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function homeImageUrl(string $path): string
{
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        return $path;
    }

    if ($path !== '') {
        return '../' . ltrim($path, '/');
    }

    return '../assets/images/product-placeholder.svg';
}

$featuredStmt = $pdo->prepare(
    'SELECT p.id, p.title, p.slug, p.description, p.price, p.discount_percent, p.image_url, p.stock_qty, c.name AS category_name
     FROM products p
     INNER JOIN categories c ON c.id = p.category_id
     ORDER BY p.created_at DESC, p.id DESC
     LIMIT 6'
);
$featuredStmt->execute();
$featuredProducts = $featuredStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<main id="main-content">
    <section class="hero" aria-labelledby="hero-title">
        <div class="hero-content">
            <p class="eyebrow">Secure commerce, built step by step</p>
            <h1 id="hero-title">Discover electronics, fashion, and workspace upgrades built for modern lifestyles.</h1>
            <p class="hero-copy">
                UniShop starts simple and grows into a secure e-commerce system with authentication,
                cart management, checkout, SEO, marketing, and security analysis.
            </p>
            <div class="hero-actions">
                <a class="button button-primary" href="#featured-products">Browse featured products</a>
                <a class="button button-secondary" href="#categories">View categories</a>
            </div>
        </div>
    </section>

    <section class="section categories-section" id="categories" aria-labelledby="categories-title">
        <div class="section-heading">
            <p class="eyebrow">Product categories</p>
            <h2 id="categories-title">Browse categories designed for your everyday needs.</h2>
        </div>

        <div class="category-grid">
            <article class="category-card">
                <span class="category-icon" aria-hidden="true">EL</span>
                <h3>Electronics</h3>
                <p>Focused devices and accessories for study, work, and daily productivity.</p>
            </article>

            <article class="category-card">
                <span class="category-icon" aria-hidden="true">FA</span>
                <h3>Fashion</h3>
                <p>Durable essentials designed for campus life and everyday comfort.</p>
            </article>

            <article class="category-card">
                <span class="category-icon" aria-hidden="true">HE</span>
                <h3>Home Essentials</h3>
                <p>Simple tools for organized rooms, calm desks, and better routines.</p>
            </article>
        </div>
    </section>

    <section class="section featured-section" id="featured-products" aria-labelledby="featured-title">
        <div class="section-heading">
            <p class="eyebrow">Featured products</p>
            <h2 id="featured-title">Latest products from the admin catalog.</h2>
        </div>

        <?php if ($featuredProducts === []): ?>
            <div class="empty-state">
                <h3>No featured products yet.</h3>
                <p>Add products from the admin dashboard to publish them here.</p>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($featuredProducts as $product): ?>
                    <?php
                    $discount = (float) $product['discount_percent'];
                    $price = (float) $product['price'];
                    $salePrice = $discount > 0 ? $price * (1 - ($discount / 100)) : $price;
                    ?>
                    <article class="product-card">
                        <img class="catalog-product-image" src="<?php echo homeEscape(homeImageUrl((string) $product['image_url'])); ?>" alt="<?php echo homeEscape((string) $product['title']); ?>" loading="lazy">
                        <div class="product-details">
                            <p class="product-category"><?php echo homeEscape((string) $product['category_name']); ?></p>
                            <h3><a class="product-title-link" href="<?php echo homeEscape(productUrl((string) $product['slug'])); ?>"><?php echo homeEscape((string) $product['title']); ?></a></h3>
                            <p><?php echo homeEscape((string) $product['description']); ?></p>
                            <div class="product-meta">
                                <span class="price">
                                    $<?php echo homeEscape(number_format($salePrice, 2)); ?>
                                    <?php if ($discount > 0): ?>
                                        <span class="discount-badge"><?php echo homeEscape(number_format($discount, 0)); ?>% off</span>
                                    <?php endif; ?>
                                </span>
                                <span class="stock"><?php echo (int) $product['stock_qty'] > 0 ? (int) $product['stock_qty'] . ' in stock' : 'Out of stock'; ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
