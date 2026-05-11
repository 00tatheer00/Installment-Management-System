<?php

/**
 * On localhost only: ensure `admin` exists with password `admin123` (matches install.sql).
 * Does nothing on production hosts (e.g. ims.tech4edges.com).
 */
function ims_dev_seed_default_admin(mysqli $conn): void
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (!preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $host)) {
        return;
    }

    $hash = '$2y$10$G.sCf3M6294B/VRrQhECH.Qx7NOgJ9H8Bdny1007X83Lwh9KVBseK';
    try {
        $stmt = $conn->prepare(
            'INSERT INTO users (name, username, password, role) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE password = VALUES(password), name = VALUES(name)'
        );
        if (!$stmt) {
            return;
        }
        $name = 'Super Admin';
        $user = 'admin';
        $role = 'admin';
        $stmt->bind_param('ssss', $name, $user, $hash, $role);
        $stmt->execute();
    } catch (Throwable $e) {
        // Table missing or DB issue — normal login flow will surface errors
    }
}
