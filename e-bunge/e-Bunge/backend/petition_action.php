<?php 
require_once __DIR__ . '/../config.php'; 
require_login(); 
verify_csrf(); 

$u = current_user(); 
$a = $_POST['action'] ?? '';

if ($a === 'create_mca' && $u['role'] === 'mca') {
    $stmt = db()->prepare('INSERT INTO petitions(subject, details, petitioner_name, petitioner_email, source, created_by) VALUES(?, ?, ?, ?, "mca", ?)');
    $stmt->execute([
        $_POST['subject'],
        $_POST['details'],
        $_POST['petitioner_name'],
        $_POST['petitioner_email'],
        $u['id']
    ]);
    flash('success', 'Petition submitted to Hansard.');

} elseif (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $map = [
        'review'  => 'reviewed',
        'reject'  => 'rejected',
        'approve' => 'approved'
    ];

    if (isset($map[$a])) {
        $stmt = db()->prepare('UPDATE petitions SET status = ? WHERE id = ?');
        $stmt->execute([$map[$a], $id]);
        flash('success', 'Petition status updated.');
    }
}

$entity_id = (int)($_POST['id'] ?? db()->lastInsertId());
audit('Petition action', 'petition', $entity_id);

redirect('../pages/petitions.php');