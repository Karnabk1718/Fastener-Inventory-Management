<?php
session_start();
include "db.php";
ensure_user_security_schema();

header('Content-Type: application/json');

$field = $_GET['field'] ?? '';
$value = trim($_GET['value'] ?? '');
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$allowed = ['username', 'email', 'phone'];
if (!in_array($field, $allowed, true) || $value === '') {
    echo json_encode(['exists' => false, 'message' => '']);
    exit;
}

if ($field === 'email' && !valid_email_addr($value)) {
    echo json_encode(['exists' => false, 'message' => 'Invalid email format.']);
    exit;
}

if ($field === 'phone' && !valid_phone($value)) {
    echo json_encode(['exists' => false, 'message' => 'Phone must be 10 digits and start with 6-9.']);
    exit;
}

try {
    $sql = "SELECT id FROM users WHERE `$field` = ?";
    if ($id > 0) {
        $sql .= " AND id <> ?";
    }
    $stmt = mysqli_prepare($conn, $sql);
    if ($id > 0) {
        mysqli_stmt_bind_param($stmt, "si", $value, $id);
    } else {
        mysqli_stmt_bind_param($stmt, "s", $value);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exists = $result && mysqli_num_rows($result) > 0;
    echo json_encode([
        'exists' => $exists,
        'message' => $exists ? ucfirst($field) . ' already exists.' : ucfirst($field) . ' is available.'
    ]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['exists' => false, 'message' => 'Unable to check right now.']);
}

