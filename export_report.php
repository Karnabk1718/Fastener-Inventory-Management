<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once "db.php";
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
function pdf_escape(string $text): string
{
    $text = str_replace("\\", "\\\\", $text);
    $text = str_replace("(", "\\(", $text);
    $text = str_replace(")", "\\)", $text);
    return preg_replace('/[^\x20-\x7E]/', '-', $text);
}
function pdf_rgb_fill(array $rgb): string
{
    return sprintf("%.3F %.3F %.3F rg\n", $rgb[0], $rgb[1], $rgb[2]);
}
function pdf_rgb_stroke(array $rgb): string
{
    return sprintf("%.3F %.3F %.3F RG\n", $rgb[0], $rgb[1], $rgb[2]);
}
function pdf_rect(float $x, float $y, float $w, float $h, ?array $fill = null, ?array $stroke = null): string
{
    $out = "";
    if ($fill !== null) {
        $out .= pdf_rgb_fill($fill);
    }
    if ($stroke !== null) {
        $out .= pdf_rgb_stroke($stroke);
    }
    $mode = ($fill !== null && $stroke !== null) ? "B" : (($fill !== null) ? "f" : "S");
    $out .= sprintf("%.2F %.2F %.2F %.2F re %s\n", $x, $y, $w, $h, $mode);
    return $out;
}
function pdf_line(float $x1, float $y1, float $x2, float $y2, array $stroke, float $width = 1): string
{
    return pdf_rgb_stroke($stroke)
        . sprintf("%.2F w\n", $width)
        . sprintf("%.2F %.2F m %.2F %.2F l S\n", $x1, $y1, $x2, $y2);
}
function pdf_text(float $x, float $y, string $text, int $fontSize = 10, string $font = 'F1', array $rgb = [0.12, 0.12, 0.12]): string
{
    return "BT\n"
        . pdf_rgb_fill($rgb)
        . sprintf("/%s %d Tf\n", $font, $fontSize)
        . sprintf("1 0 0 1 %.2F %.2F Tm\n", $x, $y)
        . "(" . pdf_escape($text) . ") Tj\nET\n";
}
function wrap_pdf_text(string $text, float $width, int $fontSize = 10): array
{
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if ($text === '') {
        return ['-'];
    }
    $maxChars = max(8, (int)floor($width / max(1, ($fontSize * 0.52))));
    $words = preg_split('/\s+/', $text);
    $lines = [];
    $line = '';
    foreach ($words as $word) {
        if (strlen($word) > $maxChars) {
            if ($line !== '') {
                $lines[] = $line;
                $line = '';
            }
            $chunks = str_split($word, $maxChars);
            foreach ($chunks as $index => $chunk) {
                if ($index === count($chunks) - 1) {
                    $line = $chunk;
                } else {
                    $lines[] = $chunk;
                }
            }
            continue;
        }
        $candidate = ($line === '') ? $word : $line . ' ' . $word;
        if (strlen($candidate) <= $maxChars) {
            $line = $candidate;
        } else {
            $lines[] = $line;
            $line = $word;
        }
    }
    if ($line !== '') {
        $lines[] = $line;
    }
    return $lines ?: ['-'];
}
function build_professional_pdf(string $title, string $filename, array $columns, array $rows): array
{
    $pageWidth = 842;
    $pageHeight = 595;
    $margin = 34;
    $usableWidth = $pageWidth - ($margin * 2);
    $lineHeight = 12;
    $headerHeight = 24;
    $detailLabelWidth = 64;
    $detailFontSize = 9;
    $colTotal = array_sum(array_column($columns, 'width'));
    foreach ($columns as &$column) {
        $column['draw_width'] = ($column['width'] / $colTotal) * $usableWidth;
    }
    unset($column);
    $pages = [];
    $pageNo = 1;
    $currentY = 0;
    $content = '';
    $startPage = function () use (&$content, &$currentY, &$pages, &$pageNo, $title, $margin, $pageWidth, $pageHeight, $headerHeight, $columns, $rows) {
        if ($content !== '') {
            $pages[] = $content;
        }
        $content = '';
        $currentY = $pageHeight - $margin;
        $content .= pdf_rect(0, $pageHeight - 86, $pageWidth, 86, [0.48, 0.00, 0.13], null);
        $content .= pdf_text($margin, $pageHeight - 40, $title, 20, 'F2', [1, 1, 1]);
        $content .= pdf_text($margin, $pageHeight - 60, 'Generated on ' . date('d-m-Y H:i:s') . '  |  Prepared for ' . ($_SESSION['username'] ?? 'User'), 9, 'F1', [0.95, 0.95, 0.95]);
        $content .= pdf_text($pageWidth - 120, $pageHeight - 40, 'Page ' . $pageNo, 10, 'F2', [1, 1, 1]);
        $content .= pdf_text($margin, $pageHeight - 80, 'Total records: ' . count($rows), 9, 'F1', [0.95, 0.95, 0.95]);
        $currentY = $pageHeight - 112;
        $x = $margin;
        foreach ($columns as $column) {
            $content .= pdf_rect($x, $currentY - $headerHeight, $column['draw_width'], $headerHeight, [0.93, 0.93, 0.95], [0.82, 0.82, 0.86]);
            $content .= pdf_text($x + 6, $currentY - 16, strtoupper($column['label']), 9, 'F2', [0.18, 0.18, 0.20]);
            $x += $column['draw_width'];
        }
        $currentY -= $headerHeight;
        $pageNo++;
    };
    $startPage();
    foreach ($rows as $index => $row) {
        $mainLines = [];
        $mainLineCount = 1;
        foreach ($columns as $column) {
            $wrapped = wrap_pdf_text((string)($row[$column['key']] ?? '-'), $column['draw_width'] - 12, 9);
            $mainLines[$column['key']] = $wrapped;
            $mainLineCount = max($mainLineCount, count($wrapped));
        }
        $mainHeight = max(24, ($mainLineCount * $lineHeight) + 10);
        $detailHeight = 0;
        $detailLines = [];
        if (!empty($row['_detail_label']) && !empty($row['_detail_value'])) {
            $detailLines = wrap_pdf_text($row['_detail_value'], $usableWidth - $detailLabelWidth - 18, $detailFontSize);
            $detailHeight = (count($detailLines) * 11) + 14;
        }
        $rowHeight = $mainHeight + $detailHeight;
        if (($currentY - $rowHeight) < $margin) {
            $startPage();
        }
        $rowBottom = $currentY - $rowHeight;
        $fill = ($index % 2 === 0) ? [1, 1, 1] : [0.975, 0.975, 0.985];
        $content .= pdf_rect($margin, $rowBottom, $usableWidth, $rowHeight, $fill, [0.84, 0.84, 0.88]);
        $x = $margin;
        foreach ($columns as $column) {
            $content .= pdf_line($x, $currentY, $x, $rowBottom, [0.88, 0.88, 0.90], 0.6);
            $textY = $currentY - 14;
            foreach ($mainLines[$column['key']] as $line) {
                $content .= pdf_text($x + 6, $textY, $line, 9, 'F1', [0.16, 0.16, 0.18]);
                $textY -= $lineHeight;
            }
            $x += $column['draw_width'];
        }
        $content .= pdf_line($margin + $usableWidth, $currentY, $margin + $usableWidth, $rowBottom, [0.88, 0.88, 0.90], 0.6);
        if ($detailHeight > 0) {
            $detailTop = $currentY - $mainHeight;
            $content .= pdf_line($margin, $detailTop, $margin + $usableWidth, $detailTop, [0.86, 0.86, 0.90], 0.6);
            $content .= pdf_text($margin + 6, $detailTop - 12, strtoupper($row['_detail_label']), 8, 'F2', [0.40, 0.40, 0.45]);
            $detailTextY = $detailTop - 12;
            foreach ($detailLines as $detailLine) {
                $content .= pdf_text($margin + $detailLabelWidth, $detailTextY, $detailLine, 9, 'F1', [0.20, 0.20, 0.22]);
                $detailTextY -= 11;
            }
        }
        $currentY = $rowBottom;
    }
    if ($content !== '') {
        $pages[] = $content;
    }
    $objects = [];
    $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[2] = "";
    $objects[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objects[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";
    $pageIds = [];
    $nextId = 5;
    foreach ($pages as $pageContent) {
        $contentId = $nextId++;
        $pageId = $nextId++;
        $objects[$contentId] = "<< /Length " . strlen($pageContent) . " >>\nstream\n" . $pageContent . "endstream";
        $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents {$contentId} 0 R >>";
        $pageIds[] = $pageId;
    }
    $objects[2] = "<< /Type /Pages /Kids [ " . implode(' ', array_map(fn($id) => "{$id} 0 R", $pageIds)) . " ] /Count " . count($pageIds) . " >>";
    ksort($objects);
    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $id => $object) {
        $offsets[$id] = strlen($pdf);
        $pdf .= "{$id} 0 obj\n{$object}\nendobj\n";
    }
    $xrefOffset = strlen($pdf);
    $size = max(array_keys($objects)) + 1;
    $pdf .= "xref\n0 {$size}\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i < $size; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
    }
    $pdf .= "trailer\n<< /Size {$size} /Root 1 0 R >>\n";
    $pdf .= "startxref\n{$xrefOffset}\n%%EOF";
    return [$pdf, $filename];
}
function render_report_choice(string $title): void
{
    $params = $_GET;
    $params['format'] = 'pdf';
    $pdfUrl = htmlspecialchars($_SERVER['PHP_SELF'] . '?' . http_build_query($params));
    $params['format'] = 'excel';
    $excelUrl = htmlspecialchars($_SERVER['PHP_SELF'] . '?' . http_build_query($params));
    $safeTitle = htmlspecialchars($title);
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Download Report</title>
<style>
* {
    box-sizing:border-box
}
body {
    margin:0;
    min-height:100vh;
    display:grid;
    place-items:center;
    font-family:Segoe UI,Arial,sans-serif;
    background:linear-gradient(135deg,#fff7f5,#ffa896);
    color:#38000a
}
.panel {
    width:min(420px,calc(100% - 32px));
    background:#fff7f5;
    border:1px solid #38000a;
    border-radius:12px;
    padding:26px;
    box-shadow:0 18px 44px rgba(56,0,10,.22)
}
h1 {
    font-size:22px;
    margin:0 0 8px
}
p {
    margin:0 0 22px;
    color:#6f2220
}
.actions {
    display:flex;
    gap:12px;
    flex-wrap:wrap
}
a {
    flex:1 1 150px;
    text-align:center;
    text-decoration:none;
    border:1px solid #38000a;
    border-radius:8px;
    padding:12px 16px;
    font-weight:800
}
.pdf {
    background:#cd1c18;
    color:#fff
}
.excel {
    background:#176a36;
    color:#fff
}
.back {
    display:block;
    margin-top:14px;
    color:#38000a;
    font-size:13px;
    font-weight:700
}
</style>
</head>
<body>
<div class="panel">
<h1>{$safeTitle}</h1>
<p>Choose the file format for this report.</p>
<div class="actions">
<a class="pdf" href="{$pdfUrl}">Download PDF</a>
<a class="excel" href="{$excelUrl}">Download Excel</a>
</div>
<a class="back" href="javascript:history.back()">Back</a>
</div>
</body>
</html>
HTML;
}
function build_excel_report(string $title, string $filename, array $columns, array $rows): array
{
    $excelName = preg_replace('/\.pdf$/', '.xls', $filename);
    $html = "<html><head><meta charset=\"UTF-8\"></head><body>";
    $html .= "<h2>" . htmlspecialchars($title) . "</h2>";
    $html .= "<table border=\"1\"><thead><tr>";
    foreach ($columns as $column) {
        $html .= "<th>" . htmlspecialchars($column['label']) . "</th>";
    }
    $html .= "</tr></thead><tbody>";
    foreach ($rows as $row) {
        $html .= "<tr>";
        foreach ($columns as $column) {
            $html .= "<td>" . htmlspecialchars((string)($row[$column['key']] ?? '-')) . "</td>";
        }
        $html .= "</tr>";
        if (!empty($row['_detail_label']) || !empty($row['_detail_value'])) {
            $html .= "<tr><td colspan=\"" . count($columns) . "\"><strong>"
                . htmlspecialchars((string)($row['_detail_label'] ?? 'Details')) . ":</strong> "
                . htmlspecialchars((string)($row['_detail_value'] ?? '-')) . "</td></tr>";
        }
    }
    $html .= "</tbody></table></body></html>";
    return [$html, $excelName];
}
$type = $_GET['type'] ?? '';
$title = '';
$filename = '';
$columns = [];
$rows = [];
switch ($type) {
    case 'fastener':
        $title = 'Fasteners Report';
        $filename = 'fasteners-report.pdf';
        $columns = [
            ['key' => 'id', 'label' => 'ID', 'width' => 8],
            ['key' => 'name', 'label' => 'Name', 'width' => 24],
            ['key' => 'part_number', 'label' => 'Part No.', 'width' => 16],
            ['key' => 'type', 'label' => 'Type', 'width' => 18],
            ['key' => 'size', 'label' => 'Size', 'width' => 16],
            ['key' => 'price', 'label' => 'Unit Price', 'width' => 16],
        ];
        $result = mysqli_query($conn, "SELECT id, name, part_number, type, size, unit_price, description FROM fastener ORDER BY id ASC");
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'part_number' => display_part_number($row['part_number'] ?? '', '-'),
                'type' => $row['type'],
                'size' => $row['size'],
                'price' => 'Rs. ' . number_format((float)$row['unit_price'], 2),
                '_detail_label' => 'Description',
                '_detail_value' => $row['description'] ?: '-',
            ];
        }
        break;
    case 'inventory':
        $title = 'Inventory Report';
        $filename = 'inventory-report.pdf';
        $columns = [
            ['key' => 'id', 'label' => 'ID', 'width' => 7],
            ['key' => 'name', 'label' => 'Fastener', 'width' => 23],
            ['key' => 'part_number', 'label' => 'Part No.', 'width' => 14],
            ['key' => 'type', 'label' => 'Type', 'width' => 16],
            ['key' => 'size', 'label' => 'Size', 'width' => 13],
            ['key' => 'quantity', 'label' => 'Qty', 'width' => 10],
            ['key' => 'price', 'label' => 'Unit Price', 'width' => 14],
            ['key' => 'status', 'label' => 'Status', 'width' => 17],
        ];
        $result = mysqli_query($conn, "
            SELECT s.id, f.name, f.part_number, f.type, f.size, s.quantity, f.unit_price, f.description
            FROM stock s
            JOIN fastener f ON s.fastener_id = f.id
            ORDER BY s.id ASC
        ");
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'part_number' => display_part_number($row['part_number'] ?? '', '-'),
                'type' => $row['type'],
                'size' => $row['size'],
                'quantity' => $row['quantity'],
                'price' => 'Rs. ' . number_format((float)$row['unit_price'], 2),
                'status' => ((int)$row['quantity'] < 20) ? 'Below Minimum' : 'In Stock',
                '_detail_label' => 'Description',
                '_detail_value' => $row['description'] ?: '-',
            ];
        }
        break;
    case 'low_stock':
        $title = 'Low Stock Report';
        $filename = 'low-stock-report.pdf';
        $columns = [
            ['key' => 'id', 'label' => 'ID', 'width' => 7],
            ['key' => 'name', 'label' => 'Fastener', 'width' => 24],
            ['key' => 'part_number', 'label' => 'Part No.', 'width' => 14],
            ['key' => 'type', 'label' => 'Type', 'width' => 17],
            ['key' => 'size', 'label' => 'Size', 'width' => 14],
            ['key' => 'quantity', 'label' => 'Qty', 'width' => 10],
            ['key' => 'price', 'label' => 'Unit Price', 'width' => 14],
            ['key' => 'status', 'label' => 'Status', 'width' => 14],
        ];
        $result = mysqli_query($conn, "
            SELECT s.id, f.name, f.part_number, f.type, f.size, s.quantity, f.unit_price, f.description
            FROM stock s
            JOIN fastener f ON s.fastener_id = f.id
            WHERE s.quantity < 20
            ORDER BY s.quantity ASC, s.id ASC
        ");
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'part_number' => display_part_number($row['part_number'] ?? '', '-'),
                'type' => $row['type'],
                'size' => $row['size'],
                'quantity' => $row['quantity'],
                'price' => 'Rs. ' . number_format((float)$row['unit_price'], 2),
                'status' => 'Low Stock',
                '_detail_label' => 'Description',
                '_detail_value' => $row['description'] ?: '-',
            ];
        }
        break;
    case 'pick_list':
        $title = 'Pick List Report';
        $filename = 'pick-list-report.pdf';
        $columns = [
            ['key' => 'id', 'label' => 'ID', 'width' => 8],
            ['key' => 'name', 'label' => 'Fastener', 'width' => 24],
            ['key' => 'part_number', 'label' => 'Part No.', 'width' => 16],
            ['key' => 'size', 'label' => 'Size', 'width' => 12],
            ['key' => 'quantity', 'label' => 'Picked Qty', 'width' => 12],
            ['key' => 'picked_by', 'label' => 'Picked By', 'width' => 14],
            ['key' => 'picked_at', 'label' => 'Picked At', 'width' => 18],
        ];
        $result = mysqli_query($conn, "
            SELECT p.id, p.quantity, p.picked_by, p.picked_at, f.name, f.part_number, f.size
            FROM pick_list p
            JOIN fastener f ON p.fastener_id = f.id
            ORDER BY p.picked_at DESC, p.id DESC
        ");
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'part_number' => display_part_number($row['part_number'] ?? '', '-'),
                'size' => $row['size'],
                'quantity' => $row['quantity'],
                'picked_by' => $row['picked_by'] ?: '-',
                'picked_at' => $row['picked_at'],
            ];
        }
        break;
    case 'supplier':
        $title = 'Suppliers Report';
        $filename = 'suppliers-report.pdf';
        $columns = [
            ['key' => 'id', 'label' => 'ID', 'width' => 8],
            ['key' => 'name', 'label' => 'Supplier Name', 'width' => 28],
            ['key' => 'contact', 'label' => 'Contact', 'width' => 26],
            ['key' => 'contract_date', 'label' => 'Contract Date', 'width' => 18],
        ];
        $result = mysqli_query($conn, "SELECT id, name, contact, address, contract_date FROM supplier ORDER BY id DESC");
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'contact' => $row['contact'],
                'contract_date' => $row['contract_date'],
                '_detail_label' => 'Address',
                '_detail_value' => $row['address'] ?: '-',
            ];
        }
        break;
    case 'orders':
        $title = 'Orders Report';
        $filename = 'orders-report.pdf';
        $columns = [
            ['key' => 'id', 'label' => 'Order ID', 'width' => 10],
            ['key' => 'fastener', 'label' => 'Fastener', 'width' => 25],
            ['key' => 'part_number', 'label' => 'Part No.', 'width' => 14],
            ['key' => 'supplier', 'label' => 'Supplier', 'width' => 25],
            ['key' => 'quantity', 'label' => 'Qty', 'width' => 10],
            ['key' => 'order_date', 'label' => 'Order Date', 'width' => 14],
            ['key' => 'status', 'label' => 'Status', 'width' => 16],
        ];
        $where = [];
        if (!empty($_GET['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'])) {
            $from = mysqli_real_escape_string($conn, $_GET['from']);
            $where[] = "DATE(o.order_date) >= '$from'";
        }
        if (!empty($_GET['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'])) {
            $to = mysqli_real_escape_string($conn, $_GET['to']);
            $where[] = "DATE(o.order_date) <= '$to'";
        }
        if (!empty($_GET['status']) && $_GET['status'] !== 'all') {
            $status = mysqli_real_escape_string($conn, $_GET['status']);
            $where[] = "o.status = '$status'";
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $result = mysqli_query($conn, "
            SELECT o.order_id, f.name AS fastener_name, f.part_number, s.name AS supplier_name, o.quantity, o.order_date, o.status
            FROM orders o
            JOIN fastener f ON o.fastener_id = f.id
            JOIN supplier s ON o.supplier_id = s.id
            $whereSql
            ORDER BY o.order_id DESC
        ");
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = [
                'id' => $row['order_id'],
                'fastener' => $row['fastener_name'],
                'part_number' => display_part_number($row['part_number'] ?? '', '-'),
                'supplier' => $row['supplier_name'],
                'quantity' => $row['quantity'],
                'order_date' => $row['order_date'],
                'status' => $row['status'] ?: 'Pending',
            ];
        }
        break;

    default:
        http_response_code(400);
        echo "Invalid report type.";
        exit();
}
if (!$rows) {
    $rows[] = [
        $columns[0]['key'] ?? 'id' => '-',
        $columns[1]['key'] ?? 'name' => 'No records found',
    ];
}
$format = strtolower($_GET['format'] ?? '');
if ($format === '') {
    render_report_choice($title);
    exit();
}
if ($format === 'excel') {
    [$excel, $downloadName] = build_excel_report($title, $filename, $columns, $rows);
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . strlen($excel));
    echo $excel;
    exit();
}
[$pdf, $downloadName] = build_professional_pdf($title, $filename, $columns, $rows);
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
exit();
?>
