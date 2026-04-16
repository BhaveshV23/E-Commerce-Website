<?php
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/newsletter.php';

$newsletterFlash = consumeNewsletterFlash();
$newsletterRedirect = $newsletterRedirect ?? 'index.php#newsletter';
?>
    <footer class="site-footer">
        <div class="newsletter-band" id="newsletter">
            <div class="newsletter-content">
                <div>
                    <p class="eyebrow">Newsletter</p>
                    <h2>Get practical store updates.</h2>
                    <p>Product drops, study-friendly picks, and secure commerce lessons.</p>
                </div>

                <form class="newsletter-form" method="post" action="newsletter_signup.php">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars((string) $newsletterRedirect, ENT_QUOTES, 'UTF-8'); ?>">
                    <label for="newsletter-email">Email address</label>
                    <div>
                        <input
                            type="email"
                            id="newsletter-email"
                            name="email"
                            maxlength="180"
                            required
                            placeholder="you@example.com"
                        >
                        <button class="button button-primary" type="submit">Subscribe</button>
                    </div>

                    <?php if ($newsletterFlash !== null): ?>
                        <p class="<?php echo $newsletterFlash['type'] === 'error' ? 'newsletter-error' : 'newsletter-success'; ?>" role="status">
                            <?php echo htmlspecialchars((string) $newsletterFlash['message'], ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> UniShop. Built for secure e-commerce learning.</p>
            <p>Secure catalog, cart, checkout, SEO, and marketing foundations.</p>
        </div>
    </footer>

    <script>
        const navToggle = document.querySelector('.nav-toggle');
        const primaryMenu = document.querySelector('#primary-menu');

        if (navToggle && primaryMenu) {
            navToggle.addEventListener('click', () => {
                const isExpanded = navToggle.getAttribute('aria-expanded') === 'true';
                navToggle.setAttribute('aria-expanded', String(!isExpanded));
                primaryMenu.classList.toggle('is-open');
            });
        }
    </script>
    <?php if (!empty($extraScripts) && is_array($extraScripts)): ?>
        <?php foreach ($extraScripts as $scriptPath): ?>
            <script src="<?php echo htmlspecialchars((string) $scriptPath, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
