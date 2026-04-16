<?php
declare(strict_types=1);

/**
 * Mailchimp Marketing API configuration.
 *
 * The API key usually ends with a data center suffix like "-us21".
 * Example endpoint shape:
 * https://us21.api.mailchimp.com/3.0/lists/{list_id}/members/{subscriber_hash}
 */

const MAILCHIMP_API_KEY = 'REPLACE_WITH_MAILCHIMP_API_KEY';
const MAILCHIMP_LIST_ID = 'REPLACE_WITH_AUDIENCE_LIST_ID';
const MAILCHIMP_DOUBLE_OPT_IN = true;
