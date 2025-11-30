<?php

function plugin_glpizebralabel_install() {
    global $DB;
    
    $migration = new Migration(PLUGIN_GLPIZEBRALABEL_VERSION);
    
    // Table for plugin configuration
    $table = 'glpi_plugin_glpizebralabel_configs';
    
    if (!$DB->tableExists($table)) {
        $query = "CREATE TABLE `{$table}` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `printer_ip` varchar(255) DEFAULT NULL,
            `printer_port` int DEFAULT 9100,
            `label_width` int DEFAULT 70,
            `label_height` int DEFAULT 30,
            `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `date_mod` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $DB->doQuery($query);

        // Insert default configuration
        $DB->insert($table, [
            'id' => 1,
            'printer_ip' => '192.168.1.100',
            'printer_port' => 9100,
            'label_width' => 70,
            'label_height' => 30
        ]);
    }

    return true;
}

function plugin_glpizebralabel_uninstall() {
    global $DB;

    $tables = [
        'glpi_plugin_glpizebralabel_configs'
    ];

    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            $DB->doQuery("DROP TABLE `$table`");
        }
    }

    return true;
}