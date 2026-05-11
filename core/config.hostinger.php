<?php

/**
 * Hostinger / live server database settings.
 *
 * On Hostinger: hPanel → Websites → Manage (your site) → Databases → MySQL Databases.
 * Create a database + user, then import install.sql into that database (phpMyAdmin → Import).
 *
 * 1) Set IMS_USE_HOSTINGER_DB to true
 * 2) Replace DB_USER, DB_PASS, DB_NAME with the exact values from hPanel (often start with u…)
 * 3) DB_HOST is almost always "localhost" on shared hosting
 *
 * Local XAMPP: leave IMS_USE_HOSTINGER_DB as false (defaults in config.php apply).
 */
define('IMS_USE_HOSTINGER_DB', true);

if (IMS_USE_HOSTINGER_DB) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u204364970_ims');
    define('DB_PASS', 'REPLACE_WITH_YOUR_MYSQL_PASSWORD');
    define('DB_NAME', 'u204364970_ims');
}
