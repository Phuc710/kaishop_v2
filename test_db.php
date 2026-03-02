<?php
require_once __DIR__ . '/app/bootstrap.php';
$db = (new UserTelegramLink())->getConnection();
$stmt = $db->query("SHOW COLUMNS FROM setting");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
