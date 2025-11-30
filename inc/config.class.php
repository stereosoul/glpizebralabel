<?php
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginGlpizebralabelConfig extends CommonDBTM {
    
    static $rightname = 'config';
    
    static function getTypeName($nb = 0) {
        return __('Zebra Label Configuration', 'glpizebralabel');
    }
    
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item->getType() == 'Config') {
            return __('Zebra Label', 'glpizebralabel');
        }
        return '';
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'Config') {
            $config = new self();
            $config->showForm(1);
            return true;
        }
        return false;
    }

    /**
     * Получение конфигурации принтера
     */
    function getConfig() {
        global $DB;
        
        $config = $DB->request([
            'FROM' => 'glpi_plugin_glpizebralabel_configs',
            'WHERE' => ['id' => 1]
        ])->current();
        
        if (!$config) {
            // Возвращаем настройки по умолчанию
            return [
                'printer_ip' => '192.168.1.100',
                'printer_port' => 9100,
                'label_width' => 70,
                'label_height' => 30
            ];
        }
        
        return [
            'printer_ip' => $config['printer_ip'] ?? '',
            'printer_port' => $config['printer_port'] ?? 9100,
            'label_width' => $config['label_width'] ?? 70,
            'label_height' => $config['label_height'] ?? 30
        ];
    }

    function showForm($ID, $options = []) {
        global $CFG_GLPI;
        
        $config = $this->getConfig();
        
        echo "<div class='center'>";
        echo "<form method='post' action='".$CFG_GLPI['root_doc']."/plugins/glpizebralabel/front/config.form.php'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='2'>".__('Zebra Printer Configuration', 'glpizebralabel')."</th></tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td width='30%'><label for='printer_ip'>".__('Printer IP Address', 'glpizebralabel')."</label></td>";
        echo "<td width='70%'><input type='text' id='printer_ip' name='printer_ip' value='".htmlentities($config['printer_ip'] ?? '')."' size='40'></td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='printer_port'>".__('Printer Port', 'glpizebralabel')."</label></td>";
        echo "<td><input type='number' id='printer_port' name='printer_port' value='".($config['printer_port'] ?? 9100)."' min='1' max='65535'></td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='label_width'>".__('Label Width (mm)', 'glpizebralabel')."</label></td>";
        echo "<td><input type='number' id='label_width' name='label_width' value='".($config['label_width'] ?? 70)."' min='10' max='100'></td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td><label for='label_height'>".__('Label Height (mm)', 'glpizebralabel')."</label></td>";
        echo "<td><input type='number' id='label_height' name='label_height' value='".($config['label_height'] ?? 30)."' min='10' max='100'></td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='2' class='center'>";
        echo "<input type='hidden' name='id' value='1'>";
        echo "<input type='submit' name='update' value='"._sx('button', 'Save')."' class='btn btn-primary'>";
        echo "</td>";
        echo "</tr>";
        
        echo "</table>";
        Html::closeForm();
        echo "</div>";
    }

    /**
     * Обработка сохранения формы
     */
    function prepareInputForUpdate($input) {
        // Убедимся, что ID всегда равен 1
        $input['id'] = 1;
        return $input;
    }
}
?>