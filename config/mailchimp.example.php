<?php
declare(strict_types=1);

/**
 * Mailchimp setup notes.
 *
 * 1. Create an audience in Mailchimp.
 * 2. Create an API key.
 * 3. Copy the audience/list ID.
 * 4. Paste both values into config/mailchimp.php.
 *
 * If these values are not configured, UniShop stores signups in newsletter_queue.
 */

const EXAMPLE_MAILCHIMP_API_KEY = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-us21';
const EXAMPLE_MAILCHIMP_LIST_ID = 'audience-id-here';
