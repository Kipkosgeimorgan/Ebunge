
<?php
require_once 'config.php';

$pdo = db();

$columns = $pdo->query("PRAGMA table_info(committees)")
                ->fetchAll(PDO::FETCH_COLUMN, 1);

if (!in_array('approval_status', $columns, true)) {
    $pdo->exec("
        ALTER TABLE committees
        ADD COLUMN approval_status TEXT NOT NULL DEFAULT 'approved'
        CHECK (approval_status IN ('pending', 'approved', 'rejected'))
    ");
}

echo "Committee approval column added successfully.";
