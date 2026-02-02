<?php
/**
 * Database Helper Functions
 * Các hàm hỗ trợ làm việc với Database
 */

require_once __DIR__ . '/connection.php';

/**
 * Execute a query and return the result
 * @param string $query SQL query to execute
 * @param mysqli $conn Database connection (optional)
 * @return mysqli_result|bool Query result or false on failure
 */
function db_query($query, $conn = null) {
    global $connection;
    $conn = $conn ?? $connection;
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        error_log("LỖI TRUY VẤN DATABASE: " . mysqli_error($conn) . " | Query: " . $query);
    }
    
    return $result;
}

/**
 * Fetch all rows as associative array
 * @param string $query SQL query
 * @param mysqli $conn Database connection (optional)
 * @return array Array of results
 */
function db_fetch_all($query, $conn = null) {
    $result = db_query($query, $conn);
    
    if (!$result) {
        return [];
    }
    
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    
    mysqli_free_result($result);
    return $rows;
}

/**
 * Fetch single row as associative array
 * @param string $query SQL query
 * @param mysqli $conn Database connection (optional)
 * @return array|null Single row or null
 */
function db_fetch_one($query, $conn = null) {
    $result = db_query($query, $conn);
    
    if (!$result) {
        return null;
    }
    
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    
    return $row;
}

/**
 * Get number of affected rows
 * @param mysqli $conn Database connection (optional)
 * @return int Number of affected rows
 */
function db_affected_rows($conn = null) {
    global $connection;
    $conn = $conn ?? $connection;
    
    return mysqli_affected_rows($conn);
}

/**
 * Get last inserted ID
 * @param mysqli $conn Database connection (optional)
 * @return int Last insert ID
 */
function db_insert_id($conn = null) {
    global $connection;
    $conn = $conn ?? $connection;
    
    return mysqli_insert_id($conn);
}

/**
 * Escape string for SQL query
 * @param string $value Value to escape
 * @param mysqli $conn Database connection (optional)
 * @return string Escaped string
 */
function db_escape($value, $conn = null) {
    global $connection;
    $conn = $conn ?? $connection;
    
    return mysqli_real_escape_string($conn, $value);
}

/**
 * Close database connection
 * @param mysqli $conn Database connection (optional)
 * @return bool True on success
 */
function db_close($conn = null) {
    global $connection;
    $conn = $conn ?? $connection;
    
    return mysqli_close($conn);
}

/**
 * Begin transaction
 * @param mysqli $conn Database connection (optional)
 * @return bool True on success
 */
function db_begin_transaction($conn = null) {
    global $connection;
    $conn = $conn ?? $connection;
    
    $result = mysqli_begin_transaction($conn);
    
    if (!$result) {
        error_log("LỖI BẮT ĐẦU TRANSACTION: " . mysqli_error($conn));
    }
    
    return $result;
}

/**
 * Commit transaction
 * @param mysqli $conn Database connection (optional)
 * @return bool True on success
 */
function db_commit($conn = null) {
    global $connection;
    $conn = $conn ?? $connection;
    
    $result = mysqli_commit($conn);
    
    if (!$result) {
        error_log("LỖI COMMIT TRANSACTION: " . mysqli_error($conn));
    }
    
    return $result;
}

/**
 * Rollback transaction
 * @param mysqli $conn Database connection (optional)
 * @return bool True on success
 */
function db_rollback($conn = null) {
    global $connection;
    $conn = $conn ?? $connection;
    
    $result = mysqli_rollback($conn);
    
    if (!$result) {
        error_log("LỖI ROLLBACK TRANSACTION: " . mysqli_error($conn));
    }
    
    return $result;
}

?>
