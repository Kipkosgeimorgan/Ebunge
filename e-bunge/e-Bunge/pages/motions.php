
<?php
$title = 'Motions';
include __DIR__ . '/../includes/page_top.php';

$u = current_user();

// Retrieve motions
if ($u['role'] === 'mca') {

    // All approved motions + motions awaiting Speaker approval
    $stmt = db()->query("
        SELECT m.*, u.username
        FROM motions m
        LEFT JOIN users u ON CAST(u.id AS TEXT) = m.mover_id
        WHERE LOWER(m.status) IN ('approved', 'reviewed')
        ORDER BY m.id DESC
    ");

} else {

    // Hansard and Speaker can view all motions
    $stmt = db()->query("
        SELECT m.*, u.username
        FROM motions m
        LEFT JOIN users u ON CAST(u.id AS TEXT) = m.mover_id
        ORDER BY m.id DESC
    ");
}

$rows = $stmt->fetchAll();

// Registered MCAs for seconder dropdown
$mcaStmt = db()->prepare("
    SELECT id, username
    FROM users
    WHERE role = 'mca' AND active = 1 AND id != ?
    ORDER BY username ASC
");
$mcaStmt->execute([$u['id']]);
$mcas = $mcaStmt->fetchAll();

$areas = [
    'Roads',
    'Infrastructure',
    'Security',
    'Education',
    'Social Welfare',
    'Gender Department',
    'Finance',
    'Agriculture',
    'Health',
    'Water and Sanitation',
    'Environment',
    'Other'
];
?>

<div class="page-heading">
    <div>
        
        <h1>Motions register</h1>
        <p class="muted">
            View approved assembly motions.
        </p>
    </div>

    <?php if ($u['role'] === 'mca'): ?>
        <button class="btn btn-primary" data-open="motion-form">
            New motion <span>＋</span>
        </button>
    <?php endif; ?>
</div>

<?php if ($u['role'] === 'mca'): ?>

<section class="panel form-panel hidden" id="motion-form">

    <h2>Submit a motion</h2>

    <form method="post"
          action="../backend/motion_action.php"
          enctype="multipart/form-data">

        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="create">

        <label>
            Motion mover
            <input type="text" value="<?= e($u['username']) ?>" readonly>
        </label>

        <label>
            Motion seconder
            <select name="seconder_id">
                <option value="">Select a seconder (optional)</option>

                <?php foreach ($mcas as $mca): ?>
                    <option value="<?= e($mca['id']) ?>">
                        <?= e($mca['username']) ?>
                    </option>
                <?php endforeach; ?>

            </select>
        </label>

        <label>
            Motion topic
            <input type="text"
                   name="motion_topic"
                   maxlength="255"
                   required
                   placeholder="Enter motion topic">
        </label>

        <label>
            Motion details
            <textarea name="motion_details" rows="5" required></textarea>
        </label>

        <label>
            Proposed resolution
            <textarea name="proposed_resolution" rows="4" required></textarea>
        </label>

        <label>
            Area of concern
            <select name="area_of_concern" required>
                <option value="">Select department or area</option>

                <?php foreach ($areas as $area): ?>
                    <option value="<?= e($area) ?>">
                        <?= e($area) ?>
                    </option>
                <?php endforeach; ?>

            </select>
        </label>

        <label>
            Supporting document (PDF, DOC or DOCX)
            <input type="file"
                   name="motion_document"
                   accept=".pdf,.doc,.docx"
                   required>
        </label>

        <button class="btn btn-primary" type="submit">
            Submit for review
        </button>

    </form>
</section>

<?php endif; ?>


<section class="panel table-panel">

    <div class="section-head">
        <h2>
            <?= $u['role'] === 'mca'
                ? 'Approved & reviewed Motions'
                : 'Motion Review Queue' ?>
        </h2>

        <span class="muted"><?= count($rows) ?> records</span>
    </div>

    <div class="table-wrap">

        <table>
            <thead>
                <tr>
                    <th>Motion Topic</th>
                    <th>Submitted By</th>
                    <th>Area of Concern</th>
                    <th>Seconder</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($rows as $r): ?>

                    <tr>

                        <td>
                            <strong>
                                <?= e($r['motion_topic'] ?? 'Untitled motion') ?>
                            </strong>

                            <small>
                                <?= e(mb_strimwidth(
                                    $r['motion_details'] ?? '',
                                    0,
                                    90,
                                    '…'
                                )) ?>
                            </small>
                        </td>

                        <td>
                            <?= e($r['username'] ?? $r['mover_id'] ?? 'Unknown') ?>
                        </td>

                        <td>
                            <?= e($r['area_of_concern'] ?? 'Not specified') ?>
                        </td>

                        <td>
                            <?= e($r['seconder_id'] ?? 'Not specified') ?>
                        </td>

                        <td>
                            <?= status_badge($r['status']) ?>
                        </td>

                        <td>
                            <?= e($r['created_at']) ?>
                        </td>

                        <td>

                            <?php if ($u['role'] === 'hansard' && $r['status'] === 'submitted'): ?>

                                <form method="post"
                                      action="../backend/motion_action.php"
                                      class="inline-form">

                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">

                                    <button name="action" value="review"
                                            class="btn btn-small" type="submit">
                                        Forward
                                    </button>

                                    <button name="action" value="return"
                                            class="btn btn-small btn-soft" type="submit">
                                        Return
                                    </button>

                                </form>

                            <?php elseif ($u['role'] === 'speaker' && $r['status'] === 'reviewed'): ?>

                                <form method="post"
                                      action="../backend/motion_action.php"
                                      class="inline-form">

                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">

                                    <button name="action" value="approve"
                                            class="btn btn-small" type="submit">
                                        Approve
                                    </button>

                                    <button name="action" value="deny"
                                            class="btn btn-small btn-soft" type="submit">
                                        Deny
                                    </button>

                                </form>

                            <?php else: ?>

                                <span class="muted">No action</span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            No approved or reviewed motions found.
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>
        </table>

    </div>
</section>

<?php include __DIR__ . '/../includes/page_bottom.php'; ?>
