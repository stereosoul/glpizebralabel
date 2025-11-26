<?php

function plugin_glpizebralabel_install() {
    $migration = new Migration(PLUGIN_GLPIZEBRALABEL_VERSION);
    
    // Execute all migrations
    do_plugin_migration('glpizebralabel', false);
    
    return true;
}