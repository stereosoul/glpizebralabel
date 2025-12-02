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
.print-direct-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
#print-status .alert {
    transition: all 0.3s ease;
}
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
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

<!-- Настройки принтера (с сохранением в cookies) -->
<div class='card mb-4 zebralabel-card'>
    <div class='card-header text-white' style='background: var(--tblr-info);'>
        <h5 class='card-title mb-0'><i class='fas fa-print me-2'></i><?= __('Printer Settings', 'glpizebralabel') ?></h5>
    </div>
    <div class='card-body'>
        <div class='zebralabel-info-box' style='border-left-color: var(--tblr-info);'>
            <i class='fas fa-info-circle me-2' style='color: var(--tblr-info);'></i>
            <?= __('Enter your Zebra printer IP address and port', 'glpizebralabel') ?>
        </div>
        
        <div class='row g-3 mt-2'>
            <div class='col-md-6'>
                <label class='form-label'><?= __('Printer IP', 'glpizebralabel') ?></label>
                <input type='text' id='global-printer-ip' class='form-control' placeholder='192.168.1.100'>
                <small class='form-text text-muted'><?= __('Example: 192.168.1.100 or zebra-printer.local', 'glpizebralabel') ?></small>
            </div>
            <div class='col-md-6'>
                <label class='form-label'><?= __('Port', 'glpizebralabel') ?></label>
                <input type='number' id='global-printer-port' class='form-control' placeholder='9100' min='1' max='65535'>
                <small class='form-text text-muted'><?= __('Default port for Zebra printers is 9100', 'glpizebralabel') ?></small>
            </div>
        </div>
        
        <div class='alert alert-warning mt-3'>
            <i class='fas fa-exclamation-triangle me-2'></i>
            <strong><?= __('Note:', 'glpizebralabel') ?></strong>
            <?= __('The printer must be accessible from the GLPI server. If printing fails, check firewall settings and network connectivity.', 'glpizebralabel') ?>
        </div>
    </div>
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
                <a href='data:text/plain;charset=utf-8,<?= $zpl_qr ?>' download='qr_<?= $itemtype ?>_<?= $items_id ?>.zpl' class='btn btn-success'>
                    <i class='fas fa-download me-1'></i><?= __('Download QR ZPL', 'glpizebralabel') ?>
                </a>
                <button type='button' class='btn btn-primary print-direct-btn' data-label-type='qr'>
                    <i class='fas fa-print me-1'></i><?= __('Print QR Directly', 'glpizebralabel') ?>
                </button>
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
                <a href='data:text/plain;charset=utf-8,<?= $zpl_barcode ?>' download='barcode_<?= $itemtype ?>_<?= $items_id ?>.zpl' class='btn btn-success'>
                    <i class='fas fa-download me-1'></i><?= __('Download Barcode ZPL', 'glpizebralabel') ?>
                </a>
                <button type='button' class='btn btn-primary print-direct-btn' data-label-type='barcode'>
                    <i class='fas fa-print me-1'></i><?= __('Print Barcode Directly', 'glpizebralabel') ?>
                </button>
            </div>
        </div>
    </div>
</div>

</div>

<!-- Область для отображения статуса печати -->
<div id='print-status' class='mt-4' style='display: none;'>
    <div class='alert alert-info'>
        <i class='fas fa-sync fa-spin me-2'></i>
        <span id='print-status-text'><?= __('Sending to printer...', 'glpizebralabel') ?></span>
    </div>
</div>

<!-- Инструкция / Instructions -->
<div class='alert alert-warning mt-4'>
    <h5 class='alert-heading'><i class='fas fa-lightbulb me-2'></i><?= __('How to use', 'glpizebralabel') ?></h5>
    <ol class='mb-0'>
        <li><?= __('Enter the IP address and port of your Zebra printer', 'glpizebralabel') ?></li>
        <li><?= __('Click "Download ZPL" to save the label file for manual printing', 'glpizebralabel') ?></li>
        <li><?= __('Or click "Print Directly" to send label directly to the printer', 'glpizebralabel') ?></li>
        <li><?= __('Status of the print job will be shown below', 'glpizebralabel') ?></li>
        <li><?= __('For manual printing, you can:', 'glpizebralabel') ?>
            <ul>
                <li><?= __('Use command: <code>lpr -S [printer_ip] -P raw [file.zpl]</code>', 'glpizebralabel') ?></li>
                <li><?= __('Copy .zpl file to printer shared folder', 'glpizebralabel') ?></li>
                <li><?= __('Use Zebra Setup Utilities software', 'glpizebralabel') ?></li>
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

<script>
$(document).ready(function() {
    // Функция для чтения cookie
    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for(var i=0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
    
    // Функция для установки cookie (на 30 дней)
    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax";
    }
    
    // Загрузка сохраненных настроек из cookies
    function loadSavedSettings() {
        var savedIp = getCookie('zebra_printer_ip');
        var savedPort = getCookie('zebra_printer_port');
        
        if (savedIp) {
            $('#global-printer-ip').val(savedIp);
        } else {
            // Значение по умолчанию
            $('#global-printer-ip').val('192.168.1.100');
        }
        
        if (savedPort) {
            $('#global-printer-port').val(savedPort);
        } else {
            // Значение по умолчанию
            $('#global-printer-port').val('9100');
        }
    }
    
    // Сохранение настроек в cookies
    function saveSettings() {
        var ip = $('#global-printer-ip').val().trim();
        var port = $('#global-printer-port').val().trim();
        
        if (ip) {
            setCookie('zebra_printer_ip', ip, 30);
        }
        if (port) {
            setCookie('zebra_printer_port', port, 30);
        }
    }
    
    // Код для печати
    $('.print-direct-btn').on('click', function() {
        var button = $(this);
        var labelType = button.data('label-type');
        var originalText = button.html();
        
        // Получаем настройки принтера
        var printerIp = $('#global-printer-ip').val().trim();
        var printerPort = $('#global-printer-port').val().trim();
        
        // Проверяем настройки принтера
        if (!printerIp) {
            showTempAlert('<?= __('Please enter printer IP address', 'glpizebralabel') ?>', 'danger');
            return;
        }
        
        if (!printerPort) {
            printerPort = 9100; // Значение по умолчанию
        }
        
        // Сохраняем настройки в cookies перед печатью
        saveSettings();
        
        // Показываем статус
        $('#print-status').show();
        $('#print-status-text').html(
            '<i class="fas fa-sync fa-spin me-2"></i>' +
            '<?= __('Sending to printer...', 'glpizebralabel') ?>' +
            ' <small>(' + printerIp + ':' + printerPort + ')</small>'
        );
        
        // Блокируем кнопки
        $('.print-direct-btn').prop('disabled', true);
        button.html('<i class="fas fa-spinner fa-spin me-1"></i><?= __('Printing...', 'glpizebralabel') ?>');
        
        // Отправляем запрос на печать
        $.ajax({
            url: '<?= Plugin::getWebDir('glpizebralabel') ?>/front/print_direct.php',
            type: 'POST',
            data: {
                action: 'print',
                itemtype: '<?= $itemtype ?>',
                items_id: '<?= $items_id ?>',
                label_type: labelType,
                printer_ip: printerIp,
                printer_port: printerPort
            },
            success: function(response) {
                if (response.success) {
                    $('#print-status-text').html(
                        '<i class="fas fa-check text-success me-2"></i>' +
                        '<strong><?= __('Label sent successfully!', 'glpizebralabel') ?></strong>' +
                        ' <small>(' + response.bytes_sent + ' <?= __('bytes', 'glpizebralabel') ?>)</small>'
                    );
                    $('#print-status .alert').removeClass('alert-info').addClass('alert-success');
                    
                    // Автоматически скрываем через 5 секунд
                    setTimeout(function() {
                        $('#print-status').fadeOut();
                    }, 5000);
                } else {
                    $('#print-status-text').html(
                        '<i class="fas fa-exclamation-triangle text-danger me-2"></i>' +
                        '<strong><?= __('Print error:', 'glpizebralabel') ?></strong> ' + 
                        (response.error || '<?= __('Unknown error', 'glpizebralabel') ?>')
                    );
                    $('#print-status .alert').removeClass('alert-info').addClass('alert-danger');
                    
                    // Не скрываем автоматически при ошибке
                }
            },
            error: function(xhr, status, error) {
                $('#print-status-text').html(
                    '<i class="fas fa-exclamation-triangle text-danger me-2"></i>' +
                    '<strong><?= __('Network error:', 'glpizebralabel') ?></strong> ' + 
                    (error || '<?= __('Cannot connect to server', 'glpizebralabel') ?>')
                );
                $('#print-status .alert').removeClass('alert-info').addClass('alert-danger');
            },
            complete: function() {
                // Восстанавливаем кнопки через 2 секунды
                setTimeout(function() {
                    $('.print-direct-btn').prop('disabled', false);
                    button.html(originalText);
                }, 2000);
            }
        });
    });
    
    // Всплывающее уведомление
    function showTempAlert(message, type) {
        var alertClass = 'alert-' + (type || 'info');
        var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                       message +
                       '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                       '</div>';
        
        var $alert = $(alertHtml);
        $('#print-status').before($alert);
        
        // Автоматическое скрытие через 3 секунды
        setTimeout(function() {
            $alert.alert('close');
        }, 3000);
    }
    
    // Сохраняем настройки при изменении полей (с задержкой)
    var saveTimeout;
    $('#global-printer-ip, #global-printer-port').on('input', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(function() {
            saveSettings();
        }, 1000); // Сохраняем через 1 секунду после окончания ввода
    });
    
    // Автофокус на поле IP при загрузке страницы
    $('#global-printer-ip').focus();
    
    // Обработка нажатия Enter в полях ввода
    $('#global-printer-ip, #global-printer-port').keypress(function(e) {
        if (e.which === 13) { // Enter
            e.preventDefault();
            // Если есть активная кнопка печати, нажимаем ее
            var activePrintButton = $('.print-direct-btn').first();
            if (activePrintButton.length && !activePrintButton.prop('disabled')) {
                activePrintButton.click();
            }
        }
    });
    
    // Загружаем сохраненные настройки при загрузке страницы
    loadSavedSettings();
});
</script>

<?php
Html::footer();
?>
