
<?php

require_once __DIR__ . '/../config.php';

require_login();
verify_csrf();

$u = current_user();
$action = $_POST['action'] ?? '';

if ($action === 'create' && $u['role'] === 'hansard') {

    $stmt = db()->prepare("
        INSERT INTO sittings (
            title, sitting_type, sitting_date, start_time,
            venue, mode, convened_by, purpose, agenda,
            status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'DRAFT', ?)
    ");

    $stmt->execute([
        trim($_POST['title'] ?? ''),
        $_POST['sitting_type'] ?? 'ORDINARY',
        $_POST['sitting_date'] ?? '',
        $_POST['start_time'] ?? '',
        trim($_POST['venue'] ?? ''),
        $_POST['mode'] ?? 'PHYSICAL',
        $_POST['convened_by'] ?? 'CLERK',
        trim($_POST['purpose'] ?? ''),
        trim($_POST['agenda'] ?? ''),
        $u['id']
    ]);

    $id = (int)db()->lastInsertId();

    audit('Scheduled sitting', 'sitting', $id);

    flash('success', 'Sitting submitted for Speaker approval.');

} elseif ($action === 'approve' && $u['role'] === 'speaker') {

    $stmt = db()->prepare("
        UPDATE sittings
        SET status = 'APPROVED',
            approved_by = ?
        WHERE id = ?
          AND status = 'DRAFT'
    ");

    $stmt->execute([
        $u['id'],
        (int)($_POST['id'] ?? 0)
    ]);

    if ($stmt->rowCount() > 0) {
        audit('Approved sitting', 'sitting', (int)$_POST['id']);
        flash('success', 'Sitting approved.');
    } else {
        flash('error', 'Sitting not found or already processed.');
    }

} else {

    http_response_code(403);
    exit('Unauthorized action.');
}

redirect('../pages/sittings.php');
