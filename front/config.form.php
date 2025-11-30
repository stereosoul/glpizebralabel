<?php
include('../../../inc/includes.php');

Session::checkRight("config", UPDATE);

$config = new PluginGlpizebralabelConfig();

// Обработка сохранения формы
if (isset($_POST['update'])) {
    $config->update($_POST);
    Session::addMessageAfterRedirect(__('Configuration updated successfully', 'glpizebralabel'));
    Html::back();
}

Html::header(PluginGlpizebralabelConfig::getTypeName(), $_SERVER['PHP_SELF'], "config", "plugins");

// Отображаем форму конфигурации
$config->showForm(1);

Html::footer();
?>