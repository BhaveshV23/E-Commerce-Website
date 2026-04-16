<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/cart.php';
require_once __DIR__ . '/../includes/seo.php';

startSecureSession();

$pageTitle = 'Product Catalog | UniShop';
$pageDescription = 'Search and filter products in the dynamic UniShop PHP catalog.';
$assetBasePath = '../assets';
$extraScripts = ['../assets/js/catalog.js'];
$canonicalUrl = appUrl('catalog.php');
$newsletterRedirect = 'catalog.php#newsletter';
$structuredData = [[
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => 'UniShop Product Catalog',
    'description' => $pageDescription,
    'url' => $canonicalUrl,
]];

/**
 * Escape output before printing untrusted or database-sourced values.
 * Database content can become unsafe if an admin account is compromised.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Keep image URLs constrained to http(s). Later admin uploads should validate
 * file type, file size, and storage location separately.
 */
function safeImageUrl(string $url): string
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return '../assets/images/product-placeholder.svg';
    }

    $scheme = parse_url($url, PHP_URL_SCHEME);

    return in_array($scheme, ['http', 'https'], true) ? $url : '../assets/images/product-placeholder.svg';
}

$searchTerm = trim((string) filter_input(INPUT_GET, 'q', FILTER_UNSAFE_RAW));
$selectedCategory = trim((string) filter_input(INPUT_GET, 'category', FILTER_UNSAFE_RAW));

// Limit search length to reduce unnecessary database work and abusive inputs.
if (strlen($searchTerm) > 80) {
    $searchTerm = substr($searchTerm, 0, 80);
}

// Slugs should contain only lowercase letters, numbers, and hyphens.
if ($selectedCategory !== '' && !preg_match('/^[a-z0-9-]{1,120}$/', $selectedCategory)) {
    $selectedCategory = '';
}

$categoryStmt = $pdo->query(
    'SELECT id, name, slug, description
     FROM categories
     ORDER BY name ASC'
);
$categories = $categoryStmt->fetchAll();

$sql = 'SELECT
            p.id,
            p.title,
            p.slug,
            p.description,
            p.price,
            p.image_url,
            p.stock_qty,
            p.seo_keywords,
            c.name AS category_name,
            c.slug AS category_slug
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id';

$where = [];
$params = [];

if ($searchTerm !== '') {
    $where[] = '(p.title LIKE :search OR p.description LIKE :search OR p.seo_keywords LIKE :search)';
    $params[':search'] = '%' . $searchTerm . '%';
}

if ($selectedCategory !== '') {
    $where[] = 'c.slug = :category';
    $params[':category'] = $selectedCategory;
}

if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY p.created_at DESC, p.id DESC';

$productStmt = $pdo->prepare($sql);
$productStmt->execute($params);
$products = $productStmt->fetchAll();
$flash = consumeCartFlash();

require_once __DIR__ . '/../includes/header.php';
?>

<main id="main-content">
    <section class="catalog-hero" aria-labelledby="catalog-title">
        <div class="section catalog-hero-inner">
            <p class="eyebrow">Dynamic catalog</p>
            <h1 id="catalog-title">Shop smarter with curated essentials for everyday productivity.</h1>
            <p>
                Server-side filtering protects the real data query. JavaScript adds a faster local search
                experience for the products already loaded on this page.
            </p>
        </div>
    </section>

    <section class="section catalog-layout" aria-label="Product catalog controls and results">
        <aside class="catalog-sidebar" aria-labelledby="filter-title">
            <h2 id="filter-title">Filters</h2>

            <form class="catalog-search-form" method="get" action="catalog.php">
                <label for="catalog-search">Search catalog</label>
                <input
                    type="search"
                    id="catalog-search"
                    name="q"
                    value="<?php echo e($searchTerm); ?>"
                    maxlength="80"
                    placeholder="Search by name or keyword"
                    autocomplete="off"
                >

                <?php if ($selectedCategory !== ''): ?>
                    <input type="hidden" name="category" value="<?php echo e($selectedCategory); ?>">
                <?php endif; ?>

                <button class="button button-primary" type="submit">Search</button>
            </form>

            <div class="category-filter-list">
                <h3>Categories</h3>
                <a class="<?php echo $selectedCategory === '' ? 'active-filter' : ''; ?>" href="catalog.php<?php echo $searchTerm !== '' ? '?q=' . urlencode($searchTerm) : ''; ?>">
                    All products
                </a>

                <?php foreach ($categories as $category): ?>
                    <?php
                    $query = ['category' => $category['slug']];
                    if ($searchTerm !== '') {
                        $query['q'] = $searchTerm;
                    }
                    ?>
                    <a
                        class="<?php echo $selectedCategory === $category['slug'] ? 'active-filter' : ''; ?>"
                        href="catalog.php?<?php echo http_build_query($query); ?>"
                    >
                        <?php echo e($category['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>

        <section class="catalog-results" aria-labelledby="results-title">
            <div class="catalog-results-heading">
                <div>
                    <p class="eyebrow">Results</p>
                    <h2 id="results-title"><?php echo count($products); ?> product<?php echo count($products) === 1 ? '' : 's'; ?> found</h2>
                </div>
                <a class="button button-secondary catalog-reset" href="catalog.php">Reset filters</a>
            </div>

            <p class="client-search-status" aria-live="polite"></p>

            <?php if ($flash !== null): ?>
                <div class="alert <?php echo $flash['type'] === 'error' ? 'alert-error' : 'alert-success'; ?>" role="status">
                    <?php echo e((string) $flash['message']); ?>
                </div>
            <?php endif; ?>

            <?php if ($products === []): ?>
                <div class="empty-state">
                    <h3>No products matched your filters.</h3>
                    <p>Try another keyword or view all products.</p>
                </div>
            <?php else: ?>
                <div class="product-grid catalog-product-grid" data-catalog-grid>
                    <?php foreach ($products as $product): ?>
                        <article
                            class="product-card catalog-product-card"
                            data-product-card
                            data-search-text="<?php echo e(strtolower($product['title'] . ' ' . $product['description'] . ' ' . $product['seo_keywords'] . ' ' . $product['category_name'])); ?>"
                        >
                            <img
                                class="catalog-product-image"
                                src="<?php echo e(safeImageUrl($product['image_url'])); ?>"
                                alt="<?php echo e($product['title']); ?>"
                                loading="lazy"
                            >
                            <div class="product-details">
                                <p class="product-category"><?php echo e($product['category_name']); ?></p>
                                <h3><a class="product-title-link" href="<?php echo e(productUrl((string) $product['slug'])); ?>"><?php echo e($product['title']); ?></a></h3>
                                <p><?php echo e($product['description']); ?></p>
                                <div class="product-meta">
                                    <span class="price">$<?php echo e(number_format((float) $product['price'], 2)); ?></span>
                                    <span class="stock">
                                        <?php echo (int) $product['stock_qty'] > 0 ? (int) $product['stock_qty'] . ' in stock' : 'Out of stock'; ?>
                                    </span>
                                </div>

                                <form class="add-to-cart-form" method="post" action="cart_action.php">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <input type="hidden" name="redirect" value="catalog.php">
                                    <button
                                        class="button button-primary"
                                        type="submit"
                                        <?php echo (int) $product['stock_qty'] <= 0 ? 'disabled' : ''; ?>
                                    >
                                        <?php echo (int) $product['stock_qty'] <= 0 ? 'Out of stock' : 'Add to cart'; ?>
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </section>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
