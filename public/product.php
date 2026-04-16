<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/seo.php';

startSecureSession();

function productEscape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function productImageUrl(string $url): string
{
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        return in_array($scheme, ['http', 'https'], true) ? $url : '../assets/images/product-placeholder.svg';
    }

    if ($url !== '') {
        return '../' . ltrim($url, '/');
    }

    return '../assets/images/product-placeholder.svg';
}

$slug = (string) filter_input(INPUT_GET, 'slug', FILTER_UNSAFE_RAW);
$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($slug !== '' && !preg_match('/^[a-z0-9-]{1,200}$/', $slug)) {
    http_response_code(404);
    exit('Product not found.');
}

if ($slug === '' && !$productId) {
    http_response_code(404);
    exit('Product not found.');
}

$whereSql = $slug !== '' ? 'p.slug = :slug' : 'p.id = :id';
$productStmt = $pdo->prepare(
    'SELECT
        p.id,
        p.title,
        p.slug,
        p.description,
        p.price,
        p.discount_percent,
        p.image_url,
        p.stock_qty,
        p.seo_keywords,
        c.name AS category_name,
        c.slug AS category_slug
     FROM products p
     INNER JOIN categories c ON c.id = p.category_id
     WHERE ' . $whereSql . '
     LIMIT 1'
);
$productStmt->execute($slug !== '' ? [':slug' => $slug] : [':id' => $productId]);
$product = $productStmt->fetch();

if (!$product) {
    http_response_code(404);
    exit('Product not found.');
}

$pageTitle = $product['title'] . ' | UniShop';
$pageDescription = substr((string) $product['description'], 0, 155);
$assetBasePath = '../assets';
$canonicalUrl = appUrl(productUrl((string) $product['slug']));
$openGraphImage = productImageUrl((string) $product['image_url']);
$structuredData = [buildProductSchema($product)];

require_once __DIR__ . '/../includes/header.php';
?>

<main id="main-content">
    <section class="section product-detail" aria-labelledby="product-title">
        <img
            class="product-detail-image"
            src="<?php echo productEscape(productImageUrl((string) $product['image_url'])); ?>"
            alt="<?php echo productEscape((string) $product['title']); ?>"
        >

        <div class="product-detail-content">
            <p class="eyebrow"><?php echo productEscape((string) $product['category_name']); ?></p>
            <h1 id="product-title"><?php echo productEscape((string) $product['title']); ?></h1>
            <p><?php echo productEscape((string) $product['description']); ?></p>

            <div class="product-meta product-detail-meta">
                <span class="price">
                    <?php
                    $discount = (float) $product['discount_percent'];
                    $price = (float) $product['price'];
                    $salePrice = $discount > 0 ? $price * (1 - ($discount / 100)) : $price;
                    ?>
                    $<?php echo productEscape(number_format($salePrice, 2)); ?>
                    <?php if ($discount > 0): ?>
                        <span class="discount-badge"><?php echo productEscape(number_format($discount, 0)); ?>% off</span>
                    <?php endif; ?>
                </span>
                <span class="stock">
                    <?php echo (int) $product['stock_qty'] > 0 ? (int) $product['stock_qty'] . ' in stock' : 'Out of stock'; ?>
                </span>
            </div>

            <form class="add-to-cart-form product-detail-form" method="post" action="cart_action.php">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                <input type="hidden" name="quantity" value="1">
                <input type="hidden" name="redirect" value="cart.php">
                <button
                    class="button button-primary"
                    type="submit"
                    <?php echo (int) $product['stock_qty'] <= 0 ? 'disabled' : ''; ?>
                >
                    <?php echo (int) $product['stock_qty'] <= 0 ? 'Out of stock' : 'Add to cart'; ?>
                </button>
            </form>

            <a class="product-back-link" href="catalog.php">Back to catalog</a>
        </div>
    </section>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';


