<?php
include('../../../inc/includes.php');

Session::checkLoginUser();

header('Content-Type: application/json');

if ($_POST['action'] ?? '' === 'print') {
    $itemtype = $_POST['itemtype'] ?? '';
    $items_id = (int)($_POST['items_id'] ?? 0);
    $label_type = $_POST['label_type'] ?? 'barcode';
    
    if (!$itemtype || !$items_id) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        exit;
    }
    
    // Генерируем ZPL код
    if ($label_type === 'qr') {
        $zpl = PluginGlpizebralabelLabel::generateQRZPL($itemtype, $items_id);
    } else {
        $zpl = PluginGlpizebralabelLabel::generateBarcodeZPL($itemtype, $items_id);
    }
    
    if (!$zpl) {
        echo json_encode(['success' => false, 'error' => 'Failed to generate ZPL']);
        exit;
    }
    
    // Получаем настройки принтера из конфигурации
    $config = new PluginGlpizebralabelConfig();
    $printer_config = $config->getConfig();
    
    // Отправляем на принтер
    $result = PluginGlpizebralabelLabel::sendToPrinter($zpl, $printer_config);
    
    echo json_encode($result);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>