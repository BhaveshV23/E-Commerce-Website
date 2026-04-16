<?php
declare(strict_types=1);

/**
 * Central security configuration.
 *
 * Teaching note:
 * Real production keys should come from environment variables and must never be
 * committed to a public repository. These placeholders keep local development clear.
 */

const RECAPTCHA_SITE_KEY = 'YOUR_RECAPTCHA_V2_SITE_KEY';
const RECAPTCHA_SECRET_KEY = 'YOUR_RECAPTCHA_V2_SECRET_KEY';

const SESSION_NAME = 'UNISHOP_SESSION';
const SESSION_IDLE_TIMEOUT_SECONDS = 1800;
