<?php
include('../../../inc/includes.php');

Session::checkLoginUser();

$itemtype = $_GET['itemtype'] ?? '';
$items_id = (int)($_GET['items_id'] ?? 0);

if (!$itemtype || !$items_id) {
    Html::displayErrorAndDie(__('Invalid parameters', 'glpizebralabel'));
}

$supported_types = ['Computer', 'Monitor', 'NetworkEquipment', 'Printer', 'Phone', 'Peripheral'];
if (!in_array($itemtype, $supported_types)) {
    Html::displayErrorAndDie(__('Unsupported item type', 'glpizebralabel'));
}

if (!class_exists($itemtype)) {
    Html::displayErrorAndDie(__('Invalid item type', 'glpizebralabel'));
}

$item = new $itemtype();
if (!$item->getFromDB($items_id)) {
    Html::displayErrorAndDie(__('Object not found', 'glpizebralabel'));
}

// Генерируем ZPL коды
$zpl_qr = PluginGlpizebralabelLabel::generateQRZPL($itemtype, $items_id);
$zpl_barcode = PluginGlpizebralabelLabel::generateBarcodeZPL($itemtype, $items_id);

if (!$zpl_qr || !$zpl_barcode) {
    Html::displayErrorAndDie(__('Failed to generate label', 'glpizebralabel'));
}

$title = __('Print label', 'glpizebralabel');
Html::header($title, $_SERVER['PHP_SELF'], 'assets', $itemtype, $items_id);

// Принудительная загрузка переводов плагина
$plugin = new Plugin();
if ($plugin->isActivated('glpizebralabel')) {
    // Перезагружаем переводы для текущей локали
    $locale = $_SESSION['glpilanguage'] ?? 'en_GB';
    $translations = plugin_glpizebralabel_get_add_data();
    if (isset($translations['translations'][$locale])) {
        $mo_file = GLPI_ROOT . '/plugins/glpizebralabel' . $translations['translations'][$locale];
        if (file_exists($mo_file)) {
            // GLPI автоматически загрузит переводы при вызове __() с доменом
        }
    }
}

?>
<style>
.zebralabel-zpl-code {
    background: var(--tblr-bg-surface-secondary) !important;
    border: 1px solid var(--tblr-border-color) !important;
    border-radius: 4px;
    padding: 10px;
    font-family: 'Courier New', monospace;
    font-size: 10px;
    max-height: 200px;
    overflow-y: auto;
    white-space: pre-wrap;
    color: var(--tblr-body-color) !important;
}
.zebralabel-info-box {
    background: var(--tblr-bg-surface-tertiary);
    border-left: 4px solid var(--tblr-primary);
    padding: 10px 15px;
    margin-bottom: 15px;
    border-radius: 0 4px 4px 0;
    color: var(--tblr-body-color);
}
.zebralabel-card {
    background: var(--tblr-bg-surface);
    border: 1px solid var(--tblr-border-color);
}
.zebralabel-card-header {
    background: var(--tblr-bg-surface-secondary) !important;
    border-bottom: 1px solid var(--tblr-border-color);
}
.btn-outline-custom {
    background: var(--tblr-bg-surface);
    border-color: var(--tblr-border-color);
    color: var(--tblr-body-color);
}
.btn-outline-custom:hover {
    background: var(--tblr-bg-surface-secondary);
    border-color: var(--tblr-primary);
}

/* Гарантия видимости в любых условиях */
.zebralabel-zpl-code pre {
    color: inherit !important;
    background: transparent !important;
    margin: 0;
}

/* Резервные стили для проблемных тем */
@media (prefers-color-scheme: light) {
    .zebralabel-zpl-code {
        background: #f8f9fa !important;
        color: #212529 !important;
    }
}

@media (prefers-color-scheme: dark) {
    .zebralabel-zpl-code {
        background: #2d3748 !important;
        color: #e9ecef !important;
    }
}
</style>

<div class='container-fluid zebralabel-print-container'>
<div class='card zebralabel-card'>
<div class='card-header zebralabel-card-header'>
    <h3 class='card-title'><i class='fas fa-print me-2'></i><?= $title ?></h3>
</div>
<div class='card-body'>

<!-- Информация об активе / Asset information -->
<div class='alert alert-info mb-4'>
    <h4 class='alert-heading'><i class='fas fa-info-circle me-2'></i><?= __('Asset information', 'glpizebralabel') ?></h4>
    <table class='table table-sm table-borderless mb-0'>
        <tr><td class='fw-bold'><?= _n('Type', 'Types', 1) ?></td><td><?= $itemtype ?></td></tr>
        <tr><td class='fw-bold'><?= __('Name') ?></td><td><?= htmlspecialchars($item->fields['name'] ?? 'N/A') ?></td></tr>
        <tr><td class='fw-bold'><?= __('ID') ?></td><td><?= $items_id ?></td></tr>
        <tr><td class='fw-bold'><?= __('Inventory number') ?></td><td><?= htmlspecialchars($item->fields['otherserial'] ?? 'N/A') ?></td></tr>
        <tr><td class='fw-bold'><?= __('Serial number') ?></td><td><?= htmlspecialchars($item->fields['serial'] ?? 'N/A') ?></td></tr>
    </table>
</div>

<div class='row'>

<!-- QR код -->
<div class='col-md-6 mb-4'>
    <div class='card h-100 zebralabel-card'>
        <div class='card-header text-white' style='background: var(--tblr-primary);'>
            <h5 class='card-title mb-0'><i class='fas fa-qrcode me-2'></i><?= __('QR Code Label', 'glpizebralabel') ?></h5>
        </div>
        <div class='card-body'>
            
            <div class='zebralabel-info-box' style='border-left-color: var(--tblr-primary);'>
                <i class='fas fa-info-circle me-2' style='color: var(--tblr-primary);'></i>
                <?= __('QR code contains scan URL for inventory updates', 'glpizebralabel') ?>
            </div>

            <details class='mb-3'>
                <summary class='btn btn-sm btn-outline-custom'>
                    <i class='fas fa-code me-1'></i><?= __('Show ZPL code', 'glpizebralabel') ?>
                </summary>
                <div class='zebralabel-zpl-code mt-2'><?= htmlspecialchars($zpl_qr) ?></div>
            </details>

            <div class='d-grid gap-2'>
                <a href='data:text/plain;charset=utf-8,<?= urlencode($zpl_qr) ?>' download='qr_<?= $itemtype ?>_<?= $items_id ?>.zpl' class='btn btn-success'>
                    <i class='fas fa-download me-1'></i><?= __('Download QR ZPL', 'glpizebralabel') ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Штрихкод -->
<div class='col-md-6 mb-4'>
    <div class='card h-100 zebralabel-card'>
        <div class='card-header text-white' style='background: var(--tblr-success);'>
            <h5 class='card-title mb-0'><i class='fas fa-barcode me-2'></i><?= __('Barcode Label', 'glpizebralabel') ?></h5>
        </div>
        <div class='card-body'>
            
            <div class='zebralabel-info-box' style='border-left-color: var(--tblr-success);'>
                <i class='fas fa-info-circle me-2' style='color: var(--tblr-success);'></i>
                <?= __('Barcode contains inventory number for quick scanning', 'glpizebralabel') ?>
            </div>

            <details class='mb-3'>
                <summary class='btn btn-sm btn-outline-custom'>
                    <i class='fas fa-code me-1'></i><?= __('Show ZPL code', 'glpizebralabel') ?>
                </summary>
                <div class='zebralabel-zpl-code mt-2'><?= htmlspecialchars($zpl_barcode) ?></div>
            </details>

            <div class='d-grid gap-2'>
                <a href='data:text/plain;charset=utf-8,<?= urlencode($zpl_barcode) ?>' download='barcode_<?= $itemtype ?>_<?= $items_id ?>.zpl' class='btn btn-success'>
                    <i class='fas fa-download me-1'></i><?= __('Download Barcode ZPL', 'glpizebralabel') ?>
                </a>
            </div>
        </div>
    </div>
</div>

</div>

<!-- Инструкция / Instructions -->
<div class='alert alert-warning mt-4'>
    <h5 class='alert-heading'><i class='fas fa-lightbulb me-2'></i><?= __('How to use', 'glpizebralabel') ?></h5>
    <ol class='mb-0'>
        <li><?= __('Click "Download ZPL" to save the label file', 'glpizebralabel') ?></li>
        <li><?= __('Transfer the .zpl file to your computer', 'glpizebralabel') ?></li>
        <li><?= __('Send the file to Zebra printer using any method:', 'glpizebralabel') ?>
            <ul>
                <li><?= __('Print via network (lpr command)', 'glpizebralabel') ?></li>
                <li><?= __('Copy to shared folder', 'glpizebralabel') ?></li>
                <li><?= __('Use Zebra printer software', 'glpizebralabel') ?></li>
            </ul>
        </li>
    </ol>
</div>

</div>

<div class='card-footer zebralabel-card-header'>
    <a href='<?= $item->getLinkURL() ?>' class='btn btn-outline-secondary'>
        <i class='fas fa-arrow-left me-1'></i><?= __('Back to asset', 'glpizebralabel') ?>
    </a>
</div>

</div>
</div>

<?php
Html::footer();