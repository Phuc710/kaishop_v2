<?php

/**
 * SimpleCache.php — Backward-compatibility shim
 *
 * Class đã được đổi tên thành AppCache (AppCache.php).
 * File này chỉ để đảm bảo autoloader cũ không bị lỗi.
 *
 * @deprecated Dùng AppCache thay thế.
 */

require_once __DIR__ . '/AppCache.php';
// AppCache.php đã tạo alias class_alias('AppCache', 'SimpleCache') bên trong.
