<?php
/**
 * App root entry (e.g. public_html/ims/index.php).
 * Sends visitors to the SPA under frontend/ so asset paths resolve correctly.
 */
$dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$prefix = ($dir === '' || $dir === '/') ? '' : $dir;
header('Location: ' . $prefix . '/frontend/', true, 302);
exit;
