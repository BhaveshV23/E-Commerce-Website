<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/seo.php';

requireAdmin();

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function loadCategories(PDO $pdo): array
{
    $stmt = $pdo->prepare('SELECT id, name FROM categories ORDER BY name ASC');
    $stmt->execute();

    return $stmt->fetchAll();
}

function uniqueProductSlug(PDO $pdo, string $title, ?int $ignoreId = null): string
{
    $baseSlug = slugify($title);
    $slug = $baseSlug;
    $counter = 2;

    while (true) {
        $sql = 'SELECT id FROM products WHERE slug = :slug';
        $params = [':slug' => $slug];

        if ($ignoreId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $ignoreId;
        }

        $stmt = $pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        if (!$stmt->fetch()) {
            return $slug;
        }

        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
}

function handleProductImageUpload(array $file, bool $required): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, $required ? 'Product image is required.' : null];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return [null, 'Image upload failed.'];
    }

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return [null, 'Image must be 2 MB or smaller.'];
    }

    $tmpName = (string) $file['tmp_name'];
    $mimeType = mime_content_type($tmpName);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mimeType])) {
        return [null, 'Only JPG, PNG, and WEBP images are allowed.'];
    }

    $uploadDir = __DIR__ . '/../assets/uploads/products/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = bin2hex(random_bytes(16)) . '.' . $allowed[$mimeType];
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        return [null, 'Image upload failed.'];
    }

    return ['assets/uploads/products/' . $fileName, null];
}

$categories = loadCategories($pdo);
$errors = [];
$form = [
    'title' => '',
    'description' => '',
    'price' => '',
    'discount_percent' => '0',
    'category_id' => '',
    'stock_qty' => '',
    'seo_keywords' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Security check failed. Please try again.';
    }
    $form['title'] = trim((string) ($_POST['title'] ?? ''));
    $form['description'] = trim((string) ($_POST['description'] ?? ''));
    $form['price'] = trim((string) ($_POST['price'] ?? ''));
    $form['discount_percent'] = trim((string) ($_POST['discount_percent'] ?? '0'));
    $form['category_id'] = trim((string) ($_POST['category_id'] ?? ''));
    $form['stock_qty'] = trim((string) ($_POST['stock_qty'] ?? ''));
    $form['seo_keywords'] = trim((string) ($_POST['seo_keywords'] ?? ''));

    if ($form['title'] === '') {
        $errors[] = 'Title is required.';
    }

    if ($form['description'] === '') {
        $errors[] = 'Description is required.';
    }

    $price = filter_var($form['price'], FILTER_VALIDATE_FLOAT);
    $discount = filter_var($form['discount_percent'], FILTER_VALIDATE_FLOAT);
    $categoryId = filter_var($form['category_id'], FILTER_VALIDATE_INT);
    $stockQty = filter_var($form['stock_qty'], FILTER_VALIDATE_INT);

    if ($price === false || $price < 0) {
        $errors[] = 'Price must be a valid positive number.';
    }

    if ($discount === false || $discount < 0 || $discount > 100) {
        $errors[] = 'Discount must be between 0 and 100.';
    }

    if ($categoryId === false || $categoryId <= 0) {
        $errors[] = 'Category is required.';
    }

    if ($stockQty === false || $stockQty < 0) {
        $errors[] = 'Stock quantity must be zero or greater.';
    }

    [$imagePath, $imageError] = handleProductImageUpload($_FILES['image'] ?? [], true);

    if ($imageError !== null) {
        $errors[] = $imageError;
    }

    if ($errors === []) {
        $slug = uniqueProductSlug($pdo, $form['title']);
        $stmt = $pdo->prepare(
            'INSERT INTO products (category_id, title, slug, description, price, discount_percent, image_url, stock_qty, seo_keywords)
             VALUES (:category_id, :title, :slug, :description, :price, :discount_percent, :image_url, :stock_qty, :seo_keywords)'
        );
        $stmt->execute([
            ':category_id' => $categoryId,
            ':title' => $form['title'],
            ':slug' => $slug,
            ':description' => $form['description'],
            ':price' => $price,
            ':discount_percent' => $discount,
            ':image_url' => $imagePath,
            ':stock_qty' => $stockQty,
            ':seo_keywords' => $form['seo_keywords'],
        ]);

        $_SESSION['admin_flash'] = 'Product added successfully.';
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
    <title>Add Product | UniShop Admin</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="admin-body">
    <header class="admin-topbar">
        <a class="brand" href="dashboard.php">UniShop Admin</a>
        <nav aria-label="Admin top navigation"><a href="products.php">Back to Products</a></nav>
    </header>

    <main class="admin-main admin-form-page">
        <p class="eyebrow">Create product</p>
        <h1>Add Product</h1>

        <?php if ($errors !== []): ?>
            <div class="alert alert-error" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo h($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form class="admin-form" method="post" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <label for="title">Title</label>
            <input id="title" name="title" value="<?php echo h($form['title']); ?>" required>

            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5" required><?php echo h($form['description']); ?></textarea>

            <label for="price">Price</label>
            <input id="price" name="price" type="number" min="0" step="0.01" value="<?php echo h($form['price']); ?>" required>

            <label for="discount_percent">Discount percent</label>
            <input id="discount_percent" name="discount_percent" type="number" min="0" max="100" step="0.01" value="<?php echo h($form['discount_percent']); ?>">

            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required>
                <option value="">Select category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo (int) $category['id']; ?>" <?php echo (string) $category['id'] === $form['category_id'] ? 'selected' : ''; ?>><?php echo h((string) $category['name']); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="image">Product image</label>
            <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp" required>

            <label for="stock_qty">Stock quantity</label>
            <input id="stock_qty" name="stock_qty" type="number" min="0" value="<?php echo h($form['stock_qty']); ?>" required>

            <label for="seo_keywords">SEO keywords</label>
            <input id="seo_keywords" name="seo_keywords" value="<?php echo h($form['seo_keywords']); ?>">

            <button class="button button-primary" type="submit">Save Product</button>
        </form>
    </main>
</body>
</html>
