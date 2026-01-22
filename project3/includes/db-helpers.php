<?php
/**
 * Database helper functions using mysqli prepared statements.
 *
 * These helpers centralize common patterns:
 *  - Preparing & executing parameterized queries
 *  - Fetching a single row or all rows
 *  - Executing INSERT/UPDATE/DELETE safely
 */

/**
 * Infer mysqli bind_param types string from an array of PHP values.
 * i = integer, d = double, s = string, b = blob
 *
 * Note: Kept PHP 5.x compatible (no scalar return types).
 */
function db_param_types($params) {
    $types = '';
    foreach ($params as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } elseif (is_null($value)) {
            // Treat NULL as string; MySQL will handle it as NULL when bound
            $types .= 's';
        } else {
            $types .= 's';
        }
    }
    return $types;
}

/**
 * Prepare and execute a query with optional parameters.
 *
 * @param mysqli     $conn   Active mysqli connection
 * @param string     $sql    SQL with ? placeholders
 * @param array<int,mixed> $params Parameters to bind in order
 * @return mysqli_stmt|false Executed statement or false on error
 */
function db_prepare_and_execute($conn, $sql, $params = array()) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }

    if (!empty($params)) {
        $types = db_param_types($params);
        // Use argument unpacking to bind all parameters (PHP 5.6+)
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }

    return $stmt;
}

/**
 * Fetch a single row as an associative array, or null if none.
 */
function db_fetch_one($conn, $sql, $params = array()) {
    $stmt = db_prepare_and_execute($conn, $sql, $params);
    if (!$stmt) {
        return null;
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        mysqli_stmt_close($stmt);
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

/**
 * Fetch all rows as an array of associative arrays.
 */
function db_fetch_all($conn, $sql, $params = array()) {
    $stmt = db_prepare_and_execute($conn, $sql, $params);
    if (!$stmt) {
        return array();
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        mysqli_stmt_close($stmt);
        return array();
    }

    $rows = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    mysqli_free_result($result);
    mysqli_stmt_close($stmt);

    return $rows;
}

/**
 * Execute a write (INSERT/UPDATE/DELETE) query.
 */
function db_execute($conn, $sql, $params = array()) {
    $stmt = db_prepare_and_execute($conn, $sql, $params);
    if (!$stmt) {
        return false;
    }
    mysqli_stmt_close($stmt);
    return true;
}
