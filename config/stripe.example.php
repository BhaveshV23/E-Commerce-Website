<?php
declare(strict_types=1);

/**
 * Stripe sandbox setup notes.
 *
 * 1. Create or open your Stripe account.
 * 2. Turn on test mode in the Stripe Dashboard.
 * 3. Copy your test Secret key and Publishable key.
 * 4. Paste them into config/stripe.php.
 * 5. Install the PHP SDK with: composer install
 *
 * Test card for sandbox payments:
 * 4242 4242 4242 4242
 * Any future expiry date, any CVC, any postal code.
 */

const EXAMPLE_STRIPE_SECRET_KEY = 'sk_test_...';
const EXAMPLE_STRIPE_PUBLISHABLE_KEY = 'pk_test_...';
