
<?php
require_once 'config.php';

$pdo = db();

try {
    $pdo->beginTransaction();

    // Preserve existing committee records.
    $pdo->exec("
        ALTER TABLE committees RENAME TO committees_old
    ");

    $pdo->exec("
        CREATE TABLE committees (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            committee_name TEXT NOT NULL,
            committee_type TEXT DEFAULT 'sectoral'
                CHECK (committee_type IN ('sectoral', 'select', 'watchdog')),
            user_id INTEGER NOT NULL,
            role TEXT DEFAULT 'member'
                CHECK (role IN ('chairperson', 'vice_chairperson', 'member')),
            status TEXT DEFAULT 'active'
                CHECK (status IN ('active', 'discharged')),
            appointed_at TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // Preserve old committee information as much as possible.
    $old = $pdo->query("SELECT * FROM committees_old")->fetchAll(PDO::FETCH_ASSOC);

    $insert = $pdo->prepare("
        INSERT INTO committees
        (id, committee_name, committee_type, user_id, role, status, appointed_at, created_at)
        VALUES (?, ?, 'sectoral', ?, 'chairperson', ?, ?, ?)
    ");

    foreach ($old as $row) {
        $chairperson = trim((string)($row['chairperson'] ?? ''));

        // Try to match the old chairperson to a user account.
        $userId = null;

        if ($chairperson !== '') {
            $find = $pdo->prepare("
                SELECT id FROM users
                WHERE username = ? OR work_email = ?
                LIMIT 1
            ");
            $find->execute([$chairperson, $chairperson]);
            $userId = $find->fetchColumn();
        }

        // Skip records whose chairperson cannot be matched.
        if (!$userId) {
            continue;
        }

        $insert->execute([
            $row['id'],
            $row['name'],
            $userId,
            strtolower($row['status'] ?? '') === 'discharged'
                ? 'discharged' : 'active',
            $row['created_at'] ?? date('Y-m-d H:i:s'),
            $row['created_at'] ?? date('Y-m-d H:i:s')
        ]);
    }

    // Upgrade committee_members.
    $pdo->exec("
        CREATE TABLE committee_members_new (
            committee_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            role TEXT DEFAULT 'member'
                CHECK (role IN ('chairperson', 'vice_chairperson', 'member')),
            status TEXT DEFAULT 'active'
                CHECK (status IN ('active', 'discharged')),
            appointed_at TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (committee_id, user_id),
            FOREIGN KEY (committee_id) REFERENCES committees(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        INSERT OR IGNORE INTO committee_members_new
        (committee_id, user_id, role, status, appointed_at)
        SELECT
            cm.committee_id,
            cm.user_id,
            'member',
            'active',
            datetime('now')
        FROM committee_members cm
        INNER JOIN committees c ON c.id = cm.committee_id
        INNER JOIN users u ON u.id = cm.user_id
    ");

    $pdo->exec("DROP TABLE committee_members");
    $pdo->exec("ALTER TABLE committee_members_new RENAME TO committee_members");
    $pdo->exec("DROP TABLE committees_old");

    $pdo->commit();

    echo "Committee database migration completed successfully.";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "Migration failed: " . htmlspecialchars($e->getMessage());
}
