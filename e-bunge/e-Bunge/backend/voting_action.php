
<?php
require_once __DIR__ . '/../config.php';

require_login();
verify_csrf();

$u = current_user();
$action = $_POST['action'] ?? '';
$pdo = db();

$entity_id = (int)($_POST['id'] ?? 0);

try {

    // HANSARD: Create voting subject
    if ($action === 'create' && $u['role'] === 'hansard') {

        $title = trim($_POST['title'] ?? '');
        $motion_id = !empty($_POST['motion_id'])
            ? (int)$_POST['motion_id']
            : null;

        $vote_type = $_POST['vote_type'] ?? 'open';
        $threshold = $_POST['threshold'] ?? 'simple';
        $duration = (int)($_POST['duration_seconds'] ?? 60);

        if ($title === '') {
            throw new Exception('Enter a voting title.');
        }

        if (!in_array($vote_type, ['open', 'secret'], true)) {
            throw new Exception('Invalid voting type.');
        }

        if (!in_array($threshold, ['simple', 'absolute', 'two_thirds'], true)) {
            throw new Exception('Invalid voting threshold.');
        }

        if ($duration < 10 || $duration > 86400) {
            throw new Exception('Invalid voting duration.');
        }

        if ($motion_id !== null) {
            $check = $pdo->prepare(
                "SELECT id FROM motions
                 WHERE id = ? AND LOWER(status) = 'approved'"
            );
            $check->execute([$motion_id]);

            if (!$check->fetch()) {
                throw new Exception('Selected motion is not approved.');
            }
        }

        $stmt = $pdo->prepare(
            "INSERT INTO votes
            (
                subject,
                details,
                motion_id,
                title,
                vote_type,
                threshold,
                duration_seconds,
                status,
                created_by
            )
            VALUES (?, '', ?, ?, ?, ?, ?, 'draft', ?)"
        );

        $stmt->execute([
            $title,
            $motion_id,
            $title,
            $vote_type,
            $threshold,
            $duration,
            $u['id']
        ]);

        $entity_id = (int)$pdo->lastInsertId();

        flash('success', 'Voting subject submitted to Speaker.');
    }

    
    elseif ($action === 'approve' && $u['role'] === 'speaker') {

        $stmt = $pdo->prepare(
            "UPDATE votes
             SET status = 'open',
                 opened_at = CURRENT_TIMESTAMP
             WHERE id = ? AND status = 'draft'"
        );

        $stmt->execute([$entity_id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Voting subject not found or already opened.');
        }

        flash('success', 'Voting is now open for MCAs.');
    }

    // MCA: Cast vote
    elseif ($action === 'vote' && $u['role'] === 'mca') {
$choice = $_POST['choice'] ?? '';

if (!in_array($choice, ['propose', 'oppose', 'abstain'], true)) {
    throw new Exception('Invalid voting choice.');
}

        $stmt = $pdo->prepare(
            "SELECT id, duration_seconds,
                CASE
                    WHEN (strftime('%s','now') -
                          strftime('%s',opened_at)) >= duration_seconds
                    THEN 1 ELSE 0
                END AS expired
             FROM votes
             WHERE id = ? AND status = 'open'"
        );

        $stmt->execute([$entity_id]);
        $vote = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vote) {
            throw new Exception('Voting is not open.');
        }

        if ((int)$vote['expired'] === 1) {
            $close = $pdo->prepare(
                "UPDATE votes
                 SET status = 'closed',
                     closed_at = CURRENT_TIMESTAMP
                 WHERE id = ? AND status = 'open'"
            );
            $close->execute([$entity_id]);

            throw new Exception('Voting time has expired.');
        }

        $stmt = $pdo->prepare(
            "INSERT OR IGNORE INTO vote_records
             (vote_id, mca_id, choice)
             VALUES (?, ?, ?)"
        );

        $stmt->execute([$entity_id, $u['id'], $choice]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('You have already voted.');
        }

        flash('success', 'Your vote has been recorded.');
    }

    else {
        throw new Exception('You are not authorized to perform this action.');
    }

    audit('Voting action', $action, $entity_id);

} catch (Throwable $e) {
    flash('error', $e->getMessage());
}

redirect('../pages/voting.php');
