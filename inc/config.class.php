<?php
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginGlpizebralabelConfig extends CommonDBTM {
    
    static function getTypeName($nb = 0) {
        return __('Zebra Label Configuration', 'glpizebralabel');
    }
    
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if (!$withtemplate) {
            if ($item->getType() == 'Config') {
                return __('Zebra Label', 'glpizebralabel');
            }
        }
        return '';
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'Config') {
            $config = new self();
            $config->showConfigForm();
        }
        return true;
    }

    function showConfigForm() {
        global $DB;
        
        $config = $DB->request([
            'FROM' => 'glpi_plugin_glpizebralabel_configs',
            'WHERE' => ['id' => 1]
        ])->current();
        
        echo "<div class='center'>";
        echo "<form method='post' action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='2'>".__('Zebra Printer Configuration', 'glpizebralabel')."</th></tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>".__('Printer IP Address', 'glpizebralabel')."</td>";
        echo "<td><input type='text' name='printer_ip' value='".($config['printer_ip'] ?? '')."' size='30'></td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>".__('Printer Port', 'glpizebralabel')."</td>";
        echo "<td><input type='number' name='printer_port' value='".($config['printer_port'] ?? 9100)."' min='1' max='65535'></td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>".__('Label Width (mm)', 'glpizebralabel')."</td>";
        echo "<td><input type='number' name='label_width' value='".($config['label_width'] ?? 70)."' min='10' max='100'></td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_1'>";
        echo "<td>".__('Label Height (mm)', 'glpizebralabel')."</td>";
        echo "<td><input type='number' name='label_height' value='".($config['label_height'] ?? 30)."' min='10' max='100'></td>";
        echo "</tr>";
        
        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='2' class='center'>";
        echo "<input type='submit' name='update' value='"._sx('button', 'Save')."' class='btn btn-primary'>";
        echo "</td>";
        echo "</tr>";
        
        echo "</table>";
        Html::closeForm();
        echo "</div>";
    }
}