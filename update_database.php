<?php
require_once 'database_connection/connection.php';

echo "Updating database schema...\n";

try {
    // Add api column if it doesn't exist
    $stmt = $pdo->prepare("SHOW COLUMNS FROM automations LIKE 'api'");
    $stmt->execute();
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        echo "Adding 'api' column to automations table...\n";
        $pdo->exec("ALTER TABLE automations ADD COLUMN api VARCHAR(255) NOT NULL UNIQUE AFTER name");
        echo "✓ 'api' column added successfully!\n";
    } else {
        echo "✓ 'api' column already exists.\n";
    }
    
    // Check for other missing columns
    $requiredColumns = [
        'automation_notes' => 'TEXT',
        'pricing' => 'INT NOT NULL',
        'pricing_model' => "ENUM('one_time', 'monthly', 'per_run', 'free_trial', 'first_run_free') DEFAULT 'one_time'",
        'is_trial_available' => 'BOOLEAN DEFAULT FALSE',
        'run_limit' => 'INT DEFAULT NULL',
        'tags' => 'VARCHAR(255)',
        'scheduling' => "ENUM('manual', 'daily', 'weekly', 'monthly') DEFAULT 'manual'"
    ];
    
    foreach ($requiredColumns as $column => $definition) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM automations LIKE ?");
        $stmt->execute([$column]);
        $columnExists = $stmt->fetch();
        
        if (!$columnExists) {
            echo "Adding '$column' column to automations table...\n";
            $pdo->exec("ALTER TABLE automations ADD COLUMN $column $definition");
            echo "✓ '$column' column added successfully!\n";
        } else {
            echo "✓ '$column' column already exists.\n";
        }
    }
    
    echo "\nDatabase schema update completed successfully!\n";
    echo "You can now create automations without the 'api' column error.\n";
    
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
?> 