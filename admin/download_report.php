<?php
require_once '../includes/config.php';
checkRole(['admin']);

// Fetch analytics data (example)
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Choose format
$format = $_GET['format'] ?? 'pdf';

if ($format === 'pdf') {
    require_once '../vendor/autoload.php'; // mPDF
    $mpdf = new \Mpdf\Mpdf();
    $html = "<h1>EliteFit Analytics Report</h1><table border='1'><tr><th>Role</th><th>Count</th></tr>";
    foreach ($roles as $row) {
        $html .= "<tr><td>{$row['role']}</td><td>{$row['count']}</td></tr>";
    }
    $html .= "</table>";
    $mpdf->WriteHTML($html);
    $mpdf->Output('elitefit_report.pdf', 'D');
    exit;
}

if ($format === 'docx') {
    require_once '../vendor/autoload.php'; // PHPWord
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $section = $phpWord->addSection();
    $section->addText("EliteFit Analytics Report");
    $table = $section->addTable();
    $table->addRow();
    $table->addCell()->addText("Role");
    $table->addCell()->addText("Count");
    foreach ($roles as $row) {
        $table->addRow();
        $table->addCell()->addText($row['role']);
        $table->addCell()->addText($row['count']);
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment;filename="elitefit_report.docx"');
    header('Cache-Control: max-age=0');
    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save('php://output');
    exit;
}

if ($format === 'xlsx') {
    require_once '../vendor/autoload.php'; // PhpSpreadsheet
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Role');
    $sheet->setCellValue('B1', 'Count');
    $rowNum = 2;
    foreach ($roles as $row) {
        $sheet->setCellValue('A' . $rowNum, $row['role']);
        $sheet->setCellValue('B' . $rowNum, $row['count']);
        $rowNum++;
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="elitefit_report.xlsx"');
    header('Cache-Control: max-age=0');
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;
}

echo "Invalid format.";