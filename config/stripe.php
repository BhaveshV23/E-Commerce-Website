<?php
declare(strict_types=1);

/**
 * Stripe sandbox configuration.
 *
 * Teaching note:
 * Use Stripe test keys during development. Production keys belong in environment
 * variables or a server-side secrets manager, not in source control.
 */

const STRIPE_SECRET_KEY = 'sk_test_REPLACE_WITH_YOUR_SECRET_KEY';
const STRIPE_PUBLISHABLE_KEY = 'pk_test_REPLACE_WITH_YOUR_PUBLISHABLE_KEY';
const STRIPE_CURRENCY = 'usd';

/**
 * This base URL must match how you open the project locally.
 * Example for PHP built-in server from the project root:
 * php -S localhost:8000 -t public
 */
const APP_BASE_URL = 'http://localhost:8000';
