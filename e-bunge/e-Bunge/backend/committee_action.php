
<?php
require_once __DIR__ . '/../config.php';

require_login();
verify_csrf();

$u = current_user();
$pdo = db();
$action = $_POST['action'] ?? '';

try {
    if ($action === 'create' && $u['role'] === 'hansard') {

        $name = trim($_POST['committee_name'] ?? '');
        $type = $_POST['committee_type'] ?? 'sectoral';
        $userId = (int)($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? 'member';
        $date = $_POST['appointed_at'] ?? '';

        if (
            $name === '' ||
            !in_array($type, ['sectoral', 'select', 'watchdog'], true) ||
            !in_array($role, ['chairperson', 'vice_chairperson', 'member'], true) ||
            $userId <= 0 ||
            $date === ''
        ) {
            throw new Exception('Please provide valid appointment details.');
        }

        $check = $pdo->prepare("
            SELECT id FROM users
            WHERE id = ? AND role = 'mca' AND active = 1
        ");
        $check->execute([$userId]);

        if (!$check->fetchColumn()) {
            throw new Exception('Selected MCA is invalid or inactive.');
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO committees
            (committee_name, committee_type, user_id, role,
             status, approval_status, appointed_at)
            VALUES (?, ?, ?, ?, 'active', 'pending', ?)
        ");

        $stmt->execute([$name, $type, $userId, $role, $date]);

        $committeeId = (int)$pdo->lastInsertId();

        $member = $pdo->prepare("
            INSERT INTO committee_members
            (committee_id, user_id, role, status, appointed_at)
            VALUES (?, ?, ?, 'active', ?)
        ");

        $member->execute([$committeeId, $userId, $role, $date]);

        $pdo->commit();

        audit('Committee appointment created', 'committee', $committeeId);
        flash('success', 'Committee appointment submitted to Speaker.');

    } elseif ($action === 'approve' && $u['role'] === 'speaker') {

        $id = (int)($_POST['id'] ?? 0);

        $stmt = $pdo->prepare("
            UPDATE committees
            SET approval_status = 'approved'
            WHERE id = ? AND approval_status = 'pending'
        ");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Appointment not found or already processed.');
        }

        audit('Committee appointment approved', 'committee', $id);
        flash('success', 'Committee appointment approved.');

    } elseif ($action === 'reject' && $u['role'] === 'speaker') {

        $id = (int)($_POST['id'] ?? 0);

        $stmt = $pdo->prepare("
            UPDATE committees
            SET approval_status = 'rejected'
            WHERE id = ? AND approval_status = 'pending'
        ");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Appointment not found or already processed.');
        }

        audit('Committee appointment rejected', 'committee', $id);
        flash('success', 'Committee appointment rejected.');

    } else {
        throw new Exception('You are not authorized to perform this action.');
    }

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash('error', $e->getMessage());
}

redirect('../pages/committees.php');
