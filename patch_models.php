<?php
$orderPath = 'app/Models/ChatGptOrder.php';
$snapPath = 'app/Models/ChatGptSnapshot.php';

// Patch ChatGptOrder
$orderContent = file_get_contents($orderPath);
$orderSearch = <<<'CODE'
        if (!empty($filters['email'])) {
            $where[] = 'o.`customer_email` LIKE ?';
            $params[] = '%' . $filters['email'] . '%';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
CODE;
$orderReplace = <<<'CODE'
        if (!empty($filters['email'])) {
            $where[] = 'o.`customer_email` LIKE ?';
            $params[] = '%' . $filters['email'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'o.`created_at` >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'o.`created_at` <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
CODE;

if (strpos($orderContent, $orderSearch) !== false) {
    $orderContent = str_replace($orderSearch, $orderReplace, $orderContent);
    file_put_contents($orderPath, $orderContent);
    echo "Patched ChatGptOrder.php\n";
} else {
    echo "Could NOT find search string in ChatGptOrder.php\n";
}

// Patch ChatGptSnapshot
$snapContent = file_get_contents($snapPath);
$snapSearch = <<<'CODE'
        if (!empty($filters['source'])) {
            $where[] = 'm.`source` = ?';
            $params[] = $filters['source'];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
CODE;
$snapReplace = <<<'CODE'
        if (!empty($filters['source'])) {
            $where[] = 'm.`source` = ?';
            $params[] = $filters['source'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'm.`first_seen_at` >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'm.`first_seen_at` <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
CODE;

if (strpos($snapContent, $snapSearch) !== false) {
    $snapContent = str_replace($snapSearch, $snapReplace, $snapContent);
    file_put_contents($snapPath, $snapContent);
    echo "Patched ChatGptSnapshot.php\n";
} else {
    echo "Could NOT find search string in ChatGptSnapshot.php\n";
}
