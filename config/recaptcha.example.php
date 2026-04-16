<?php
declare(strict_types=1);

/**
 * Google reCAPTCHA v2 setup notes.
 *
 * 1. Visit https://www.google.com/recaptcha/admin/create
 * 2. Choose reCAPTCHA v2, then "I'm not a robot" Checkbox.
 * 3. Add your local or hosted domain.
 * 4. Copy the site key and secret key into config/security.php.
 *
 * Do not commit real production secrets to a public repository.
 */

const EXAMPLE_RECAPTCHA_SITE_KEY = 'paste-site-key-here';
const EXAMPLE_RECAPTCHA_SECRET_KEY = 'paste-secret-key-here';
