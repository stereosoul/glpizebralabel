<?php
define('PLUGIN_GLPIZEBRALABEL_VERSION', '1.0.0');
define('PLUGIN_GLPIZEBRALABEL_MIN_GLPI', '11.0.0');
define('PLUGIN_GLPIZEBRALABEL_MAX_GLPI', '11.99.99');

function plugin_init_glpizebralabel() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['glpizebralabel'] = true;
    $PLUGIN_HOOKS['add_css']['glpizebralabel'] = 'glpizebralabel.css';

    if (Plugin::isPluginActive('glpizebralabel')) {
        
        if (Session::getLoginUserID()) {
            // Регистрируем только нужные классы
            Plugin::registerClass('PluginGlpizebralabelConfig');
            Plugin::registerClass('PluginGlpizebralabelLabel', [
                'addtabon' => ['Computer', 'Monitor', 'NetworkEquipment', 'Printer', 'Phone', 'Peripheral']
            ]);

            // Хук для кнопки печати на форме объекта
            $PLUGIN_HOOKS['post_item_form']['glpizebralabel'] = [
                'PluginGlpizebralabelLabel',
                'postItemForm'
            ];
        }
    }
}

function plugin_version_glpizebralabel() {
    return [
        'name'           => 'GLPI Zebra Label',
        'version'        => PLUGIN_GLPIZEBRALABEL_VERSION,
        'author'         => 'vibecoded by Aleksei Meshkov',
        'license'        => 'GPL-3.0-or-later',
        'homepage'       => 'https://github.com/stereosoul/glpizebralabel',
        'description'    => __('Plugin for generating ZPL labels for Zebra printers with QR codes and barcodes', 'glpizebralabel'),
        'minGlpiVersion' => PLUGIN_GLPIZEBRALABEL_MIN_GLPI,
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_GLPIZEBRALABEL_MIN_GLPI,
                'max' => PLUGIN_GLPIZEBRALABEL_MAX_GLPI,
            ],
            'php' => [
                'min' => '7.4'
            ]
        ]
    ];
}

function plugin_glpizebralabel_check_prerequisites() {
    if (version_compare(GLPI_VERSION, PLUGIN_GLPIZEBRALABEL_MIN_GLPI, 'lt')) {
        echo "This plugin requires GLPI >= " . PLUGIN_GLPIZEBRALABEL_MIN_GLPI;
        return false;
    }
    
    return true;
}

function plugin_glpizebralabel_check_config($verbose = false) {
    return true;
}

// Функция для загрузки локалей
function plugin_glpizebralabel_get_add_data() {
    return [
        'translations' => [
            'ru_RU' => '/locales/ru_RU.mo',
            'en_GB' => '/locales/en_GB.mo'
        ]
    ];
}