<?php

function plugin_glpizebralabel_update($current_version) {
    $migration = new Migration(PLUGIN_GLPIZEBRALABEL_VERSION);
    
    // Execute all migrations
    do_plugin_migration('glpizebralabel', false);
    
    return true;
}