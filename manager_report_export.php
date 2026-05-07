<?php
session_start();
include "db.php";
include "manager_auth.php";

if (!isset($_GET['type'])) {
    header("Location: manager_reports.php");
    exit();
}

$managerTypeMap = [
    'fasteners' => 'fastener',
    'fastener' => 'fastener',
    'inventory' => 'inventory',
    'low_stock' => 'low_stock',
    'supplier' => 'supplier',
    'orders' => 'orders',
    'daily' => 'orders',
    'monthly' => 'orders',
    'yearly' => 'orders',
    'custom' => 'orders',
];

$requestedType = $_GET['type'];
if (!isset($managerTypeMap[$requestedType])) {
    http_response_code(400);
    echo "Invalid report type.";
    exit();
}

if ($requestedType === 'daily') {
    $_GET['from'] = date('Y-m-d');
    $_GET['to'] = date('Y-m-d');
} elseif ($requestedType === 'monthly') {
    $_GET['from'] = date('Y-m-01');
    $_GET['to'] = date('Y-m-d');
} elseif ($requestedType === 'yearly') {
    $_GET['from'] = date('Y-01-01');
    $_GET['to'] = date('Y-m-d');
}

$_GET['type'] = $managerTypeMap[$requestedType];
include "export_report.php";
exit();
?>
