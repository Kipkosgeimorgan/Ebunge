
<?php
require_once __DIR__ . '/../config.php';

require_login();
verify_csrf();

$u = current_user();
$action = $_POST['action'] ?? '';

if ($action === 'mark' && $u['role'] === 'hansard') {

    $sittingId = (int)($_POST['sitting_id'] ?? 0);
    $mcaUsername = trim($_POST['mca_username'] ?? '');
    $state = strtolower(trim($_POST['state'] ?? ''));
    $note = trim($_POST['note'] ?? '');

    // Validate attendance state
    if (!in_array($state, ['present', 'absent', 'missed'], true)) {
        flash('error', 'Invalid attendance status.');
        redirect('../pages/attendance.php');
    }

    // Confirm the sitting exists and is approved
    $q = db()->prepare("
        SELECT id FROM sittings
        WHERE id = ? AND UPPER(status) = 'APPROVED'
    ");
    $q->execute([$sittingId]);
    $sitting = $q->fetch();

    if (!$sitting) {
        flash('error', 'The selected sitting is not approved or does not exist.');
        redirect('../pages/attendance.php');
    }

    // Find MCA account
    $q = db()->prepare("
        SELECT id FROM users
        WHERE username = ? AND role = 'mca' AND active = 1
    ");
    $q->execute([$mcaUsername]);
    $mca = $q->fetch();

    if (!$mca) {
        flash('error', 'MCA account was not found.');
        redirect('../pages/attendance.php');
    }

    // Check whether attendance already exists
    $q = db()->prepare("
        SELECT id FROM attendance
        WHERE sitting_id = ? AND mca_id = ?
    ");
    $q->execute([$sittingId, $mca['id']]);
    $existing = $q->fetch();

    if ($existing) {
        // Update existing record and send it for approval again
        $stmt = db()->prepare("
            UPDATE attendance
            SET state = ?,
                note = ?,
                status = 'submitted',
                created_by = ?,
                approved_by = NULL
            WHERE id = ?
        ");

        $stmt->execute([
            $state,
            $note,
            $u['id'],
            $existing['id']
        ]);

        $attendanceId = (int)$existing['id'];

    } else {
        // Create new attendance record
        $stmt = db()->prepare("
            INSERT INTO attendance
            (sitting_id, mca_id, state, note, status, created_by)
            VALUES (?, ?, ?, ?, 'submitted', ?)
        ");

        $stmt->execute([
            $sittingId,
            $mca['id'],
            $state,
            $note,
            $u['id']
        ]);

        $attendanceId = (int)db()->lastInsertId();
    }

    audit('Submitted attendance', 'attendance', $attendanceId);

    flash('success', 'Attendance submitted to Speaker.');

} elseif ($action === 'approve' && $u['role'] === 'speaker') {

    $id = (int)($_POST['id'] ?? 0);

    $stmt = db()->prepare("
        UPDATE attendance
        SET status = 'approved',
            approved_by = ?
        WHERE id = ? AND status = 'submitted'
    ");

    $stmt->execute([$u['id'], $id]);

    if ($stmt->rowCount() > 0) {
        audit('Approved attendance', 'attendance', $id);
        flash('success', 'Attendance approved. The MCA can now view the approved record.');
    } else {
        flash('error', 'Attendance record not found or already approved.');
    }

} else {
    http_response_code(403);
    exit('Unauthorized action.');
}

redirect('../pages/attendance.php');
