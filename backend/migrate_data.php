<?php
header("Content-Type: application/json");
require_once 'config.php';

// This script migrates local data to cloud database
// Run this after setting up the database

echo json_encode([
    'success' => true,
    'message' => 'Data migration script ready',
    'instructions' => [
        '1. Update config.php with your database credentials',
        '2. Run setup_database.php to create tables',
        '3. Use this script to migrate existing data',
        '4. Update app.json API_URL to point to your Hostinger domain'
    ]
]);
?>
