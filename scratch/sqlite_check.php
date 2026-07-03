<?php
try {
    $db = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check tables
    echo "TABLES:\n";
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    print_r($tables);
    
    // Read settings table
    if (in_array('settings', $tables)) {
        echo "\nSETTINGS:\n";
        $settings = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($settings as $s) {
            echo "KEY: " . $s['setting_key'] . "\n";
        }
    } else {
        echo "\nsettings table not found in SQLite db.\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
