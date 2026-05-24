<?php
$conn = mysqli_connect("localhost", "root", "", "fastener_db");
if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");
function db_exec_safe($sql)
{
    global $conn;
    try {
        return mysqli_query($conn, $sql);
    } catch (Throwable $e) {
        error_log($e->getMessage());
        return false;
    }
}
function db_has_column($table, $column)
{
    global $conn;
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $res && mysqli_num_rows($res) > 0;
}
function db_has_index($table, $index)
{
    global $conn;
    $table = mysqli_real_escape_string($conn, $table);
    $index = mysqli_real_escape_string($conn, $index);
    $res = mysqli_query($conn, "SHOW INDEX FROM `$table` WHERE Key_name='$index'");
    return $res && mysqli_num_rows($res) > 0;
}
function ensure_user_security_schema()
{
    if (!db_has_column('users', 'email')) {
        db_exec_safe("ALTER TABLE users ADD email VARCHAR(120) NULL AFTER username");
    }
    if (!db_has_column('users', 'phone')) {
        db_exec_safe("ALTER TABLE users ADD phone VARCHAR(15) NULL AFTER email");
    }
    if (!db_has_column('users', 'reset_otp')) {
        db_exec_safe("ALTER TABLE users ADD reset_otp VARCHAR(255) NULL");
    }
    if (!db_has_column('users', 'reset_otp_expires')) {
        db_exec_safe("ALTER TABLE users ADD reset_otp_expires DATETIME NULL");
    }
    if (!db_has_index('users', 'users_username_unique')) {
        db_exec_safe("ALTER TABLE users ADD UNIQUE users_username_unique (username)");
    }
    if (!db_has_index('users', 'users_email_unique')) {
        db_exec_safe("ALTER TABLE users ADD UNIQUE users_email_unique (email)");
    }
    if (!db_has_index('users', 'users_phone_unique')) {
        db_exec_safe("ALTER TABLE users ADD UNIQUE users_phone_unique (phone)");
    }
}
function ensure_fastener_schema()
{
    if (!db_has_column('fastener', 'part_number')) {
        db_exec_safe("ALTER TABLE fastener ADD part_number VARCHAR(80) NULL AFTER name");
    }
}
ensure_fastener_schema();
function ensure_pick_list_schema()
{
    db_exec_safe("CREATE TABLE IF NOT EXISTS pick_list (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fastener_id INT NOT NULL,
        quantity INT NOT NULL,
        picked_by VARCHAR(100) NULL,
        picked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX pick_list_fastener_idx (fastener_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
ensure_pick_list_schema();
function generate_captcha()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $a = random_int(2, 9);
    $b = random_int(1, 9);
    $_SESSION['captcha_answer'] = (string)($a + $b);
    return "$a + $b";
}
function valid_name($name)
{
    return (bool)preg_match('/^[A-Za-z][A-Za-z .\'-]{1,78}$/', trim($name));
}
function valid_phone($phone)
{
    return (bool)preg_match('/^[6-9][0-9]{9}$/', trim($phone));
}
function valid_email_addr($email)
{
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
}
function valid_part_number($partNumber)
{
    return strpos((string)$partNumber, '#') === false;
}
function part_number_without_hashtags($partNumber)
{
    return str_replace('#', '', trim((string)$partNumber));
}
function display_part_number($partNumber, $fallback = '-')
{
    $cleanPartNumber = part_number_without_hashtags($partNumber);
    return $cleanPartNumber === '' ? $fallback : $cleanPartNumber;
}
?>
