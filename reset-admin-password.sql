-- Run in phpMyAdmin → SQL. Login after:  admin  /  admin123

USE installment_system;

INSERT INTO users (name, username, password, role)
VALUES (
  'Super Admin',
  'admin',
  '$2y$10$G.sCf3M6294B/VRrQhECH.Qx7NOgJ9H8Bdny1007X83Lwh9KVBseK',
  'admin'
)
ON DUPLICATE KEY UPDATE
  password = VALUES(password),
  name = VALUES(name);
