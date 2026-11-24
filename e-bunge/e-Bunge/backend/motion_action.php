
<?php
require_once __DIR__ . '/../config.php';

require_login();
verify_csrf();

$a = $_POST['action'] ?? '';
$u = current_user();

if ($a === 'create' && $u['role'] === 'mca') {

    $motionTopic = trim($_POST['motion_topic'] ?? '');
    $motionDetails = trim($_POST['motion_details'] ?? '');
    $proposedResolution = trim($_POST['proposed_resolution'] ?? '');
    $areaOfConcern = trim($_POST['area_of_concern'] ?? '');

    $seconderId = !empty($_POST['seconder_id'])
        ? (int) $_POST['seconder_id']
        : null;

    if (
        $motionTopic === '' ||
        $motionDetails === '' ||
        $proposedResolution === '' ||
        $areaOfConcern === ''
    ) {
        flash('error', 'Please complete all required motion fields.');
        redirect('../pages/motions.php');
    }

    if (mb_strlen($motionTopic) > 255 || mb_strlen($areaOfConcern) > 100) {
        flash('error', 'The motion topic or department name is too long.');
        redirect('../pages/motions.php');
    }

    // Validate seconder
    if ($seconderId !== null) {

        if ($seconderId === (int) $u['id']) {
            flash('error', 'You cannot second your own motion.');
            redirect('../pages/motions.php');
        }

        $check = db()->prepare("
            SELECT id FROM users
            WHERE id = ? AND role = 'mca' AND active = 1
        ");
        $check->execute([$seconderId]);

        if (!$check->fetch()) {
            flash('error', 'The selected seconder is not an active registered MCA.');
            redirect('../pages/motions.php');
        }
    }

    // Validate uploaded document
    if (
        !isset($_FILES['motion_document']) ||
        $_FILES['motion_document']['error'] !== UPLOAD_ERR_OK
    ) {
        flash('error', 'Please upload a valid supporting document.');
        redirect('../pages/motions.php');
    }

    $file = $_FILES['motion_document'];

    $maxSize = 8 * 1024 * 1024;
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    $allowedExtensions = ['pdf', 'doc', 'docx'];

    if (!in_array($extension, $allowedExtensions, true)) {
        flash('error', 'Only PDF, DOC and DOCX documents are allowed.');
        redirect('../pages/motions.php');
    }

    if ($file['size'] > $maxSize) {
        flash('error', 'The supporting document must not exceed 8 MB.');
        redirect('../pages/motions.php');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowedMimes = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/x-ole-storage'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip'
        ]
    ];

    if (!in_array($mime, $allowedMimes[$extension], true)) {
        flash('error', 'The uploaded file type does not match its extension.');
        redirect('../pages/motions.php');
    }

    $uploadDirectory = __DIR__ . '/../uploads/motions';

    if (
        !is_dir($uploadDirectory) &&
        !mkdir($uploadDirectory, 0755, true) &&
        !is_dir($uploadDirectory)
    ) {
        flash('error', 'Could not create the document upload directory.');
        redirect('../pages/motions.php');
    }

    $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = $uploadDirectory . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        flash('error', 'Unable to save the supporting document.');
        redirect('../pages/motions.php');
    }

    $documentUrl = 'uploads/motions/' . $fileName;

    try {
        $stmt = db()->prepare("
            INSERT INTO motions (
                mover_id,
                seconder_id,Z
                motion_topic,
                motion_details,
                proposed_resolution,
                area_of_concern,
                document_url,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, 'submitted')
        ");

        $stmt->execute([
            (string) $u['id'],
            $seconderId,
            $motionTopic,
            $motionDetails,
            $proposedResolution,
            $areaOfConcern,
            $documentUrl
        ]);

        $motionId = (int) db()->lastInsertId();

        audit('Submitted motion', 'motion', $motionId);

        flash('success', 'Motion submitted successfully to Hansard for review.');

    } catch (Throwable $e) {

        if (is_file($destination)) {
            unlink($destination);
        }

        error_log('Motion submission error: ' . $e->getMessage());

        flash('error', 'Motion could not be saved. Please check the application error log.');
    }

} elseif (isset($_POST['id'])) {

    $id = (int) $_POST['id'];
    error_log('Forwarded motion ID: ' . $id);
    error_log('Database path: ' . DB_PATH);

  
    $map = [
        'review' => [
            'new_status' => 'reviewed',
            'required_role' => 'hansard',
            'current_status' => 'submitted'
        ],
        'return' => [
            'new_status' => 'returned',
            'required_role' => 'hansard',
            'current_status' => 'submitted'
        ],
        'approve' => [
            'new_status' => 'approved',
            'required_role' => 'speaker',
            'current_status' => 'reviewed'
        ],
        'deny' => [
            'new_status' => 'denied',
            'required_role' => 'speaker',
            'current_status' => 'reviewed'
        ]
    ];

    if (!isset($map[$a])) {
        flash('error', 'Invalid motion action.');
        redirect('../pages/motions.php');
    }

    $workflow = $map[$a];

    if ($u['role'] !== $workflow['required_role']) {
        http_response_code(403);
        exit('You are not authorised to perform this action.');
    }

    try {
       
        $check = db()->prepare("
            SELECT id, status
            FROM motions
            WHERE id = ?
        ");
        $check->execute([$id]);

        $motion = $check->fetch(PDO::FETCH_ASSOC);

        if (!$motion) {
            flash('error', 'The selected motion does not exist.');
            redirect('../pages/motions.php');
        }

        // Check that the motion is at the correct workflow stage
        $currentStatus = strtolower(trim($motion['status'] ?? ''));

        if ($currentStatus !== $workflow['current_status']) {

            flash(
                'error',
                'This motion cannot be processed because its current status is ' .
                ($motion['status'] ?: 'unknown') . '.'
            );

            redirect('../pages/motions.php');
        }

        // Update only if the motion is still at the expected stage
        $stmt = db()->prepare("
            UPDATE motions
            SET status = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
              AND LOWER(status) = ?
        ");

        $stmt->execute([
            $workflow['new_status'],
            $id,
            $workflow['current_status']
        ]);

        if ($stmt->rowCount() > 0) {

            audit(ucfirst($a) . ' motion', 'motion', $id);

            $messages = [
                'review' => 'Motion successfully forwarded to the Speaker.',
                'return' => 'Motion returned to the MCA.',
                'approve' => 'Motion approved successfully.',
                'deny' => 'Motion denied successfully.'
            ];

            flash('success', $messages[$a]);

        } else {
            flash('error', 'The motion could not be updated. Please refresh and try again.');
        }

    } catch (Throwable $e) {

        error_log('Motion workflow error: ' . $e->getMessage());

        flash('error', 'A database error occurred while processing the motion.');
    }

} else {

    flash('error', 'Invalid motion request.');
}

redirect('../pages/motions.php');
