<?php
declare(strict_types=1);

$pageTitle = 'UniShop | Secure E-Commerce Learning Store';
$pageDescription = 'A responsive PHP e-commerce homepage built with secure, modular architecture.';
$assetBasePath = '../assets';
$canonicalUrl = 'http://localhost:8000/index.php';
$newsletterRedirect = 'index.php#newsletter';
$structuredData = [[
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => 'UniShop',
    'url' => $canonicalUrl,
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => 'http://localhost:8000/catalog.php?q={search_term_string}',
        'query-input' => 'required name=search_term_string',
    ],
]];

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
            <h2 id="featured-title">Popular picks customers love right now.</h2>
        </div>

        <div class="product-grid">
            <article class="product-card">
                <div class="product-image product-image-headphones" role="img" aria-label="Wireless study headphones"></div>
                <div class="product-details">
                    <p class="product-category">Electronics</p>
                    <h3>Wireless Study Headphones</h3>
                    <p>Noise-reducing headphones designed for focused study sessions.</p>
                    <div class="product-meta">
                        <span class="price">$59.99</span>
                        <span class="stock">In stock</span>
                    </div>
                </div>
            </article>

            <article class="product-card">
                <div class="product-image product-image-backpack" role="img" aria-label="Campus backpack"></div>
                <div class="product-details">
                    <p class="product-category">Fashion</p>
                    <h3>Campus Backpack</h3>
                    <p>Durable backpack with organized laptop and book storage.</p>
                    <div class="product-meta">
                        <span class="price">$44.50</span>
                        <span class="stock">In stock</span>
                    </div>
                </div>
            </article>

            <article class="product-card">
                <div class="product-image product-image-desk" role="img" aria-label="Desk organizer set"></div>
                <div class="product-details">
                    <p class="product-category">Home Essentials</p>
                    <h3>Desk Organizer Set</h3>
                    <p>Minimal organizer set for keeping a clean and productive workspace.</p>
                    <div class="product-meta">
                        <span class="price">$24.99</span>
                        <span class="stock">In stock</span>
                    </div>
                </div>
            </article>
        </div>
    </section>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
