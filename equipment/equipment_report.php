<?php
require_once '../includes/config.php';
require_once '../vendor/autoload.php';
checkRole(['equipment', 'admin']);

$from = $_GET['from_date'] ?? null;
$to = $_GET['to_date'] ?? null;
$type = $_GET['type'] ?? 'status';
$format = $_GET['format'] ?? 'pdf';

if (!$from || !$to) {
    die("Invalid date range.");
}

if ($type === 'status') {
    $stmt = $pdo->prepare("SELECT name, status, last_maintenance FROM equipment WHERE last_maintenance BETWEEN ? AND ? ORDER BY name");
    $stmt->execute([$from, $to]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $title = "Equipment Status Report ($from to $to)";
} else {
    $stmt = $pdo->prepare("SELECT name, description, status, last_maintenance FROM equipment WHERE last_maintenance BETWEEN ? AND ? ORDER BY last_maintenance DESC");
    $stmt->execute([$from, $to]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $title = "Equipment Maintenance Report ($from to $to)";
}

// Generate report
if ($format === 'pdf') {
    $mpdf = new \Mpdf\Mpdf();
    $html = "<h2>$title</h2><table border='1' cellpadding='5'><tr>";
    foreach (array_keys($data[0] ?? ['No data' => '']) as $col) {
        $html .= "<th>" . htmlspecialchars(ucwords(str_replace('_', ' ', $col))) . "</th>";
    }
    $html .= "</tr>";
    foreach ($data as $row) {
        $html .= "<tr>";
        foreach ($row as $cell) {
            $html .= "<td>" . htmlspecialchars($cell) . "</td>";
        }
        $html .= "</tr>";
    }
    $html .= "</table>";
    $mpdf->WriteHTML($html);
    $mpdf->Output('equipment_report.pdf', 'D');
    exit;
} elseif ($format === 'xlsx') {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    // Header
    $col = 'A';
    foreach (array_keys($data[0] ?? ['No data' => '']) as $header) {
        $sheet->setCellValue($col . '1', ucwords(str_replace('_', ' ', $header)));
        $col++;
    }
    // Data
    $rowNum = 2;
    foreach ($data as $row) {
        $col = 'A';
        foreach ($row as $cell) {
            $sheet->setCellValue($col . $rowNum, $cell);
            $col++;
        }
        $rowNum++;
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="equipment_report.xlsx"');
    header('Cache-Control: max-age=0');
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;
} elseif ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="equipment_report.csv"');
    $output = fopen('php://output', 'w');
    // Header
    fputcsv($output, array_keys($data[0] ?? ['No data']));
    // Data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
} else {
    echo "Invalid format.";
}