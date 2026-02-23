<?php
require_once __DIR__ . '/hethong/config.php';
$sql = "CREATE TABLE IF NOT EXISTS `banned_fingerprints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fingerprint_hash` varchar(64) NOT NULL,
  `reason` text DEFAULT NULL,
  `banned_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_bf_hash` (`fingerprint_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
$connection->query($sql);
echo "Done";
