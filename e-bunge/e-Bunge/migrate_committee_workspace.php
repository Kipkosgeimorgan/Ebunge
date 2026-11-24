
<?php
/**
 * e-Bunge Committee Workspace Migration
 * Run once from the project root.
 */

declare(strict_types=1);

$dbPath = __DIR__ . '/database/ebunge.db';

if (!file_exists($dbPath)) {
    exit("ERROR: Database not found: $dbPath");
}

// 1. Back up the database
$backupPath = __DIR__ . '/database/ebunge_backup_' . date('Ymd_His') . '.db';

if (!copy($dbPath, $backupPath)) {
    exit("ERROR: Could not back up the database. Migration cancelled.");
}

echo "Database backup created: " . basename($backupPath) . PHP_EOL;

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec('PRAGMA foreign_keys = OFF');

    $tableExists = function (string $table) use ($pdo): bool {
        $stmt = $pdo->prepare(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name = ?"
        );
        $stmt->execute([$table]);
        return (bool) $stmt->fetch();
    };

    $columns = function (string $table) use ($pdo): array {
        return array_column(
            $pdo->query("PRAGMA table_info($table)")->fetchAll(),
            'name'
        );
    };

    if (!$tableExists('users') || !$tableExists('committees')) {
        throw new RuntimeException('Required users or committees table is missing.');
    }

    $committeeColumns = $columns('committees');

    // 2. Check whether the final schema already exists
    $finalSchemaExists =
        in_array('committee_name', $committeeColumns, true) &&
        in_array('created_by', $committeeColumns, true) &&
        in_array('approval_status', $committeeColumns, true) &&
        in_array('chairperson_id', $committeeColumns, true);

    if ($finalSchemaExists) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS committee_meetings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                committee_id INTEGER NOT NULL,
                meeting_title TEXT NOT NULL,
                meeting_date TEXT NOT NULL,
                start_time TEXT NOT NULL,
                venue TEXT NOT NULL,
                agenda TEXT,
                status TEXT NOT NULL DEFAULT 'scheduled'
                    CHECK (status IN ('scheduled', 'completed', 'cancelled')),
                created_by INTEGER NOT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (committee_id)
                    REFERENCES committees(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES users(id)
            )
        ");

        echo "Committee workspace schema already exists." . PHP_EOL;
        echo "Committee meetings table checked/created." . PHP_EOL;
        echo "No existing committee records were changed." . PHP_EOL;
        exit;
    }

    // 3. Read existing records before rebuilding the tables
    $oldCommittees = $pdo->query("SELECT * FROM committees")->fetchAll();

    $oldMembers = [];
    if ($tableExists('committee_members')) {
        $oldMembers = $pdo->query(
            "SELECT * FROM committee_members"
        )->fetchAll();
    }

    $users = $pdo->query(
        "SELECT id, username, role FROM users"
    )->fetchAll();

    if (!$users) {
        throw new RuntimeException(
            'No users exist. Cannot migrate committees safely.'
        );
    }

    // Find a suitable creator for old records that have no created_by.
    $defaultCreator = null;

    foreach ($users as $user) {
        if (($user['role'] ?? '') === 'hansard') {
            $defaultCreator = (int) $user['id'];
            break;
        }
    }

    if ($defaultCreator === null) {
        $defaultCreator = (int) $users[0]['id'];
    }

    $validUserIds = array_map(
        fn($user) => (int) $user['id'],
        $users
    );

    $findUserId = function ($value) use ($pdo, $validUserIds): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value) && in_array((int)$value, $validUserIds, true)) {
            return (int)$value;
        }

        $stmt = $pdo->prepare(
            "SELECT id FROM users WHERE username = ? LIMIT 1"
        );
        $stmt->execute([(string)$value]);

        $result = $stmt->fetchColumn();
        return $result !== false ? (int)$result : null;
    };

    $pdo->beginTransaction();

    // 4. Remove old tables after saving their records in memory
    $pdo->exec("DROP TABLE IF EXISTS committee_members");
    $pdo->exec("DROP TABLE IF EXISTS committees");

    // 5. Create the final committees table
    $pdo->exec("
        CREATE TABLE committees (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            committee_name TEXT NOT NULL,
            committee_type TEXT NOT NULL DEFAULT 'sectoral'
                CHECK (committee_type IN ('sectoral', 'select', 'watchdog')),
            description TEXT,
            chairperson_id INTEGER,
            created_by INTEGER NOT NULL,
            approval_status TEXT NOT NULL DEFAULT 'pending'
                CHECK (approval_status IN ('pending', 'approved', 'rejected')),
            status TEXT NOT NULL DEFAULT 'active'
                CHECK (status IN ('active', 'discharged')),
            approved_by INTEGER,
            approved_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (chairperson_id) REFERENCES users(id),
            FOREIGN KEY (created_by) REFERENCES users(id),
            FOREIGN KEY (approved_by) REFERENCES users(id)
        )
    ");

    // 6. Create the final committee_members table
    $pdo->exec("
        CREATE TABLE committee_members (
            committee_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            role TEXT NOT NULL DEFAULT 'member'
                CHECK (role IN ('chairperson', 'vice_chairperson', 'member')),
            status TEXT NOT NULL DEFAULT 'active'
                CHECK (status IN ('active', 'discharged')),
            appointed_at TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (committee_id, user_id),
            FOREIGN KEY (committee_id)
                REFERENCES committees(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)
                REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // 7. Group old appointment rows into one committee per name/type
    $groupedCommittees = [];
    $oldIdMap = [];

    foreach ($oldCommittees as $old) {
        $name = trim((string)(
            $old['committee_name'] ?? $old['name'] ?? ''
        ));

        if ($name === '') {
            $name = 'Unnamed Committee';
        }

        $type = $old['committee_type'] ?? 'sectoral';

        if (!in_array($type, ['sectoral', 'select', 'watchdog'], true)) {
            $type = 'sectoral';
        }

        $key = strtolower($name) . '|' . $type;

        if (!isset($groupedCommittees[$key])) {
            $creator = $findUserId($old['created_by'] ?? null)
                ?? $defaultCreator;

            $chairpersonId = $findUserId(
                $old['chairperson_id'] ?? null
            );

            // Support the original schema's chairperson username field.
            if ($chairpersonId === null && !empty($old['chairperson'])) {
                $chairpersonId = $findUserId($old['chairperson']);
            }

            $approval = $old['approval_status'] ?? 'approved';
            if (!in_array($approval, ['pending', 'approved', 'rejected'], true)) {
                $approval = 'approved';
            }

            $status = 'active';
            if (
                !isset($old['user_id']) &&
                in_array($old['status'] ?? '', ['active', 'discharged'], true)
            ) {
                $status = $old['status'];
            }

            $stmt = $pdo->prepare("
                INSERT INTO committees (
                    committee_name, committee_type, description,
                    chairperson_id, created_by, approval_status,
                    status, approved_by, approved_at, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $name,
                $type,
                $old['description'] ?? null,
                $chairpersonId,
                $creator,
                $approval,
                $status,
                $findUserId($old['approved_by'] ?? null),
                $old['approved_at'] ?? null,
                $old['created_at'] ?? date('Y-m-d H:i:s')
            ]);

            $newCommitteeId = (int)$pdo->lastInsertId();

            $groupedCommittees[$key] = $newCommitteeId;
        } else {
            $newCommitteeId = $groupedCommittees[$key];

            // Preserve a chairperson found on a later appointment row.
            if (
                !empty($old['user_id']) &&
                ($old['role'] ?? '') === 'chairperson'
            ) {
                $chairpersonId = $findUserId($old['user_id']);

                if ($chairpersonId !== null) {
                    $stmt = $pdo->prepare("
                        UPDATE committees
                        SET chairperson_id = ?
                        WHERE id = ? AND chairperson_id IS NULL
                    ");
                    $stmt->execute([$chairpersonId, $newCommitteeId]);
                }
            }
        }

        $oldIdMap[(string)$old['id']] = $newCommitteeId;

        // Old intermediate schema stored each MCA directly in committees.
        if (!empty($old['user_id'])) {
            $userId = $findUserId($old['user_id']);

            if ($userId !== null) {
                $role = $old['role'] ?? 'member';
                if (!in_array($role, ['chairperson', 'vice_chairperson', 'member'], true)) {
                    $role = 'member';
                }

                $memberStatus = $old['status'] ?? 'active';
                if (!in_array($memberStatus, ['active', 'discharged'], true)) {
                    $memberStatus = 'active';
                }

                $stmt = $pdo->prepare("
                    INSERT OR REPLACE INTO committee_members (
                        committee_id, user_id, role, status, appointed_at
                    ) VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $newCommitteeId,
                    $userId,
                    $role,
                    $memberStatus,
                    $old['appointed_at'] ?? date('Y-m-d H:i:s')
                ]);

                if ($role === 'chairperson') {
                    $pdo->prepare("
                        UPDATE committees
                        SET chairperson_id = ?
                        WHERE id = ?
                    ")->execute([$userId, $newCommitteeId]);
                }
            }
        }
    }

    // 8. Copy members from the old committee_members table
    foreach ($oldMembers as $member) {
        $oldCommitteeId = (string)($member['committee_id'] ?? '');

        if (!isset($oldIdMap[$oldCommitteeId])) {
            continue;
        }

        $userId = $findUserId($member['user_id'] ?? null);

        if ($userId === null) {
            continue;
        }

        $role = $member['role'] ?? 'member';
        if (!in_array($role, ['chairperson', 'vice_chairperson', 'member'], true)) {
            $role = 'member';
        }

        $memberStatus = $member['status'] ?? 'active';
        if (!in_array($memberStatus, ['active', 'discharged'], true)) {
            $memberStatus = 'active';
        }

        $stmt = $pdo->prepare("
            INSERT OR IGNORE INTO committee_members (
                committee_id, user_id, role, status, appointed_at
            ) VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $oldIdMap[$oldCommitteeId],
            $userId,
            $role,
            $memberStatus,
            $member['appointed_at'] ?? date('Y-m-d H:i:s')
        ]);
    }

    // 9. Create committee meetings
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS committee_meetings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            committee_id INTEGER NOT NULL,
            meeting_title TEXT NOT NULL,
            meeting_date TEXT NOT NULL,
            start_time TEXT NOT NULL,
            venue TEXT NOT NULL,
            agenda TEXT,
            status TEXT NOT NULL DEFAULT 'scheduled'
                CHECK (status IN ('scheduled', 'completed', 'cancelled')),
            created_by INTEGER NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (committee_id)
                REFERENCES committees(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )
    ");

    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_committee_members_user
        ON committee_members(user_id)
    ");

    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_committee_meetings_committee
        ON committee_meetings(committee_id)
    ");

    $pdo->commit();

    $pdo->exec('PRAGMA foreign_keys = ON');

    echo PHP_EOL;
    echo "SUCCESS: Committee workspace migration completed." . PHP_EOL;
    echo "Committees created: " . count($groupedCommittees) . PHP_EOL;
    echo "Committee members preserved where matching users were found." . PHP_EOL;
    echo "Committee meetings table is ready." . PHP_EOL;
    echo "Backup: " . basename($backupPath) . PHP_EOL;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (isset($pdo)) {
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    echo PHP_EOL . "MIGRATION FAILED: " . $e->getMessage() . PHP_EOL;
    echo "Your original database backup is: " . basename($backupPath) . PHP_EOL;
    exit(1);
}
