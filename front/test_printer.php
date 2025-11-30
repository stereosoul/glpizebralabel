<?php
include('../../../inc/includes.php');
Session::checkLoginUser();
header('Content-Type: application/json');

$printer_ip = $_POST['printer_ip'] ?? '';
$printer_port = (int)($_POST['printer_port'] ?? 9100);

if (empty($printer_ip)) {
    echo json_encode(['success' => false, 'error' => 'Printer IP not provided']);
    exit;
}

$config = ['printer_ip' => $printer_ip, 'printer_port' => $printer_port];
$result = PluginGlpizebralabelLabel::detailedPrinterCheck($config);

echo json_encode($result);
?>