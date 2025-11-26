<?php
include('../../../inc/includes.php');

function updateInventoryDate($itemtype, $items_id) {
    global $DB;
    
    $current_date = date('Y-m-d H:i:s');
    
    // Проверяем существует ли запись в glpi_infocoms
    $infocom = new Infocom();
    
    // Получаем или создаем запись в infocom
    if (!$infocom->getFromDBforDevice($itemtype, $items_id)) {
        // Создаем новую запись
        $input = [
            'itemtype' => $itemtype,
            'items_id' => $items_id,
            'inventory_date' => $current_date
        ];
        return $infocom->add($input);
    } else {
        // Обновляем существующую запись
        $input = [
            'id' => $infocom->getID(),
            'inventory_date' => $current_date
        ];
        return $infocom->update($input);
    }
}

$itemtype = $_GET['itemtype'] ?? '';
$items_id = (int)($_GET['items_id'] ?? 0);

if (!$itemtype || !$items_id) {
    Session::addMessageAfterRedirect(__('Invalid parameters', 'glpizebralabel'), true, ERROR);
    Html::redirect($_SERVER['HTTP_REFERER'] ?? $CFG_GLPI['url_base']);
    exit;
}

$item = new $itemtype();
if (!$item->getFromDB($items_id)) {
    Session::addMessageAfterRedirect(__('Object not found', 'glpizebralabel'), true, ERROR);
    Html::redirect($_SERVER['HTTP_REFERER'] ?? $CFG_GLPI['url_base']);
    exit;
}

// Если пользователь не авторизован - редирект на логин
if (!Session::getLoginUserID()) {
    $redirect_url = urlencode($_SERVER['REQUEST_URI']);
    Html::redirect($CFG_GLPI['url_base'] . "/front/login.php?redirect=$redirect_url");
    exit;
}

// Обновляем поле inventory_date в таблице glpi_infocoms
if (updateInventoryDate($itemtype, $items_id)) {
    Session::addMessageAfterRedirect(
        sprintf(__('Date of last physical inventory updated: %s', 'glpizebralabel'), date('d-m-Y H:i:s')),
        true,
        INFO
    );
} else {
    Session::addMessageAfterRedirect(
        __('Error updating physical inventory date', 'glpizebralabel'),
        true,
        ERROR
    );
}

// Редирект на страницу актива
Html::redirect($item->getLinkURL());