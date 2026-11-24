<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "<h2>e-Bunge Database Diagnostic</h2>";

try {
    require_once __DIR__ . '/config.php';

    echo "<p>Config loaded successfully.</p>";
    echo "<p>Database path: " . htmlspecialchars(DB_PATH) . "</p>";

    $pdo = db();

    echo "<p>Database connected successfully.</p>";

    $columns = $pdo->query("PRAGMA table_info(sittings)")->fetchAll(PDO::FETCH_ASSOC);

    echo "<h3>Current sittings columns</h3>";

    foreach ($columns as $column) {
        echo htmlspecialchars($column['name']) . " — "
            . htmlspecialchars($column['type']) . "<br>";
    }

    $required = [
        'sitting_type' => "TEXT NOT NULL DEFAULT 'ORDINARY'",
        'start_time'   => "TEXT NOT NULL DEFAULT '09:00'",
        'mode'         => "TEXT NOT NULL DEFAULT 'PHYSICAL'",
        'convened_by'  => "TEXT NOT NULL DEFAULT 'CLERK'",
        'purpose'      => "TEXT"
    ];

    $existing = array_column($columns, 'name');

    echo "<h3>Checking missing columns</h3>";

    foreach ($required as $name => $definition) {
        if (!in_array($name, $existing, true)) {
            $pdo->exec("ALTER TABLE sittings ADD COLUMN $name $definition");
            echo "Added: " . htmlspecialchars($name) . "<br>";
        } else {
            echo "Already exists: " . htmlspecialchars($name) . "<br>";
        }
    }

    echo "<h3>Migration completed</h3>";

} catch (Throwable $e) {
    echo "<h3 style='color:red'>ERROR</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}