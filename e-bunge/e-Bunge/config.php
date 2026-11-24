
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const APP_NAME = 'e-Bunge';
const DB_PATH = __DIR__ . '/database/ebunge.db';

const ROLES = [
    'mca' => 'Member of County Assembly',
    'speaker' => 'Office of the Speaker',
    'hansard' => 'Hansard Office'
];

if (!is_dir(__DIR__ . '/database')) {
    mkdir(__DIR__ . '/database', 0775, true);
}

function db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        initialize_database($pdo);
    }

    return $pdo;
}

function initialize_database(PDO $pdo): void {
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            work_email TEXT UNIQUE NOT NULL,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            active INTEGER DEFAULT 1
        )",

        "CREATE TABLE IF NOT EXISTS motions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            mover_id VARCHAR(50) NOT NULL,
            seconder_id VARCHAR(50),
            motion_topic VARCHAR(255) NOT NULL,
            motion_details TEXT NOT NULL,
            proposed_resolution TEXT NOT NULL,
            area_of_concern VARCHAR(100) NOT NULL,
            document_url TEXT NOT NULL,
            status VARCHAR(30) DEFAULT 'PENDING',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )",

        "CREATE TABLE IF NOT EXISTS sittings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            sitting_date TEXT NOT NULL,
            venue TEXT NOT NULL,
            agenda TEXT,
            status TEXT DEFAULT 'pending',
            created_by INTEGER,
            approved_by INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(created_by) REFERENCES users(id),
            FOREIGN KEY(approved_by) REFERENCES users(id)
        )",

        "CREATE TABLE IF NOT EXISTS attendance (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sitting_id INTEGER NOT NULL,
            mca_id INTEGER NOT NULL,
            state TEXT NOT NULL CHECK(state IN ('present','absent','missed')),
            note TEXT,
            status TEXT DEFAULT 'submitted',
            created_by INTEGER NOT NULL,
            approved_by INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(sitting_id, mca_id),
            FOREIGN KEY(sitting_id) REFERENCES sittings(id),
            FOREIGN KEY(mca_id) REFERENCES users(id),
            FOREIGN KEY(created_by) REFERENCES users(id),
            FOREIGN KEY(approved_by) REFERENCES users(id)
        )",

        "CREATE TABLE IF NOT EXISTS votes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            motion_id INTEGER,
            title TEXT NOT NULL,
            vote_type TEXT DEFAULT 'open',
            threshold TEXT DEFAULT 'simple',
            duration_seconds INTEGER DEFAULT 60,
            status TEXT DEFAULT 'draft',
            created_by INTEGER NOT NULL,
            opened_at TEXT,
            closed_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(motion_id) REFERENCES motions(id),
            FOREIGN KEY(created_by) REFERENCES users(id)
        )",

        "CREATE TABLE IF NOT EXISTS vote_records (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vote_id INTEGER NOT NULL,
            mca_id INTEGER NOT NULL,
            choice TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(vote_id, mca_id),
            FOREIGN KEY(vote_id) REFERENCES votes(id),
            FOREIGN KEY(mca_id) REFERENCES users(id)
        )",

        "CREATE TABLE IF NOT EXISTS committees (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            committee_name TEXT NOT NULL,
            committee_type TEXT DEFAULT 'sectoral',
            description TEXT,
            chairperson_id INTEGER,
            created_by INTEGER NOT NULL,
            approval_status TEXT DEFAULT 'pending',
            status TEXT DEFAULT 'active',
            approved_by INTEGER,
            approved_at TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(chairperson_id) REFERENCES users(id),
            FOREIGN KEY(created_by) REFERENCES users(id),
            FOREIGN KEY(approved_by) REFERENCES users(id)
        )",

        "CREATE TABLE IF NOT EXISTS committee_members (
            committee_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            role TEXT DEFAULT 'member',
            status TEXT DEFAULT 'active',
            appointed_at TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(committee_id, user_id),
            FOREIGN KEY(committee_id) REFERENCES committees(id) ON DELETE CASCADE,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS committee_meetings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            committee_id INTEGER NOT NULL,
            meeting_title TEXT NOT NULL,
            meeting_date TEXT NOT NULL,
            start_time TEXT NOT NULL,
            venue TEXT NOT NULL,
            agenda TEXT,
            status TEXT DEFAULT 'scheduled',
            created_by INTEGER NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(committee_id) REFERENCES committees(id) ON DELETE CASCADE,
            FOREIGN KEY(created_by) REFERENCES users(id)
        )",

        "CREATE TABLE IF NOT EXISTS petitions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            subject TEXT NOT NULL,
            details TEXT NOT NULL,
            petitioner_name TEXT NOT NULL,
            petitioner_email TEXT NOT NULL,
            source TEXT DEFAULT 'public',
            status TEXT DEFAULT 'submitted',
            review_note TEXT,
            created_by INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(created_by) REFERENCES users(id)
        )",

        "CREATE TABLE IF NOT EXISTS reports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            category TEXT NOT NULL,
            content TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            created_by INTEGER,
            approved_by INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(created_by) REFERENCES users(id),
            FOREIGN KEY(approved_by) REFERENCES users(id)
        )",

        "CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            entity TEXT,
            entity_id INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id)
        )"
    ];

    $pdo->beginTransaction();
    try {
        foreach ($tables as $sql) {
            $pdo->exec($sql);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void {
    $_SESSION['flash'] = compact('type', 'message');
}

function get_flash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(): void {
    if (!current_user()) redirect('login.php');
}

function require_role(array $roles): void {
    require_login();
    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function role_name(string $role): string {
    return ROLES[$role] ?? $role;
}

function audit(string $action, ?string $entity = null, ?int $entity_id = null): void {
    $user = current_user();
    db()->prepare(
        'INSERT INTO audit_logs(user_id, action, entity, entity_id) VALUES(?,?,?,?)'
    )->execute([$user['id'] ?? null, $action, $entity, $entity_id]);
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(): void {
    $session = $_SESSION['csrf'] ?? '';
    $submitted = $_POST['csrf'] ?? '';

    if (!is_string($session) || !is_string($submitted) ||
        $session === '' || !hash_equals($session, $submitted)) {
        http_response_code(419);
        exit('Invalid security token. Refresh and try again.');
    }
}

function status_badge(string $status): string {
    return '<span class="badge badge-' . e($status) . '">' .
        e(ucwords(str_replace('_', ' ', $status))) . '</span>';
}

function count_query(string $sql, array $params = []): int {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}
