<?php 
require_once __DIR__ . '/../config.php'; 
require_login(); 
$u = current_user(); 

if (isset($_GET['download'])) {
    $s = db()->prepare('SELECT * FROM reports WHERE id = ? AND status = "approved"');
    $s->execute([(int)$_GET['id']]);
    $r = $s->fetch();

    if (!$r) {
        exit('Report unavailable');
    }

    $filename = preg_replace('/[^a-z0-9]+/i', '-', strtolower($r['title'])) . '.txt';

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo APP_NAME . "\n" . $r['title'] . "\nCategory: " . $r['category'] . "\n\n" . $r['content'];
    exit;
}

verify_csrf(); 
$action = $_POST['action'] ?? '';

if ($action === 'create' && $u['role'] === 'hansard') {
    $stmt = db()->prepare('INSERT INTO reports(title, category, content, created_by) VALUES(?, ?, ?, ?)');
    $stmt->execute([
        $_POST['title'],
        $_POST['category'],
        $_POST['content'],
        $u['id']
    ]);
    flash('success', 'Report submitted for approval.');

} elseif ($action === 'approve' && $u['role'] === 'speaker') {
    $stmt = db()->prepare('UPDATE reports SET status = "approved", approved_by = ? WHERE id = ?');
    $stmt->execute([
        $u['id'],
        (int)$_POST['id']
    ]);
    flash('success', 'Report approved for download.');
}

redirect('../pages/reports.php');