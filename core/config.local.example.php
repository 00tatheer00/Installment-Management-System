<?php
/**
 * Optional: copy to config.local.php (same folder as config.php).
 * Loaded after config.hostinger.php — overrides any DB_* defines from there.
 *
 * Hostinger: prefer editing core/config.hostinger.php (set IMS_USE_HOSTINGER_DB true).
 * Or use this file instead of config.hostinger.php (not both with duplicate defines).
 */

// define('DB_HOST', 'localhost');
// define('DB_USER', 'u123456789_imsuser');
// define('DB_PASS', 'your_database_password');
// define('DB_NAME', 'u123456789_installment');

/** Only if the API is called from another origin (e.g. separate app subdomain). */
// define('CORS_ALLOWED_ORIGINS', ['https://ims.tech4edges.com']);
