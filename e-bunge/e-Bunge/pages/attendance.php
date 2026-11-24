
<?php
$title = 'Attendance';
include __DIR__ . '/../includes/page_top.php';

$u = current_user();

// Fetch only approved sittings
$sittings = db()->query("
    SELECT id, title, sitting_date
    FROM sittings
    WHERE LOWER(status) = 'approved'
    ORDER BY sitting_date DESC
")->fetchAll();

// Fetch all active MCA accounts
$mcas = db()->query("
    SELECT id, username
    FROM users
    WHERE role = 'mca' AND active = 1
    ORDER BY username ASC
")->fetchAll();

// Fetch attendance records according to user role
if ($u['role'] === 'mca') {

    // MCA can only view their own attendance
    $stmt = db()->prepare("
        SELECT a.*, s.title, s.sitting_date
        FROM attendance a
        JOIN sittings s ON s.id = a.sitting_id
        WHERE a.mca_id = ?
        ORDER BY a.created_at DESC
    ");

    $stmt->execute([$u['id']]);

} else {

    // Hansard and Speaker can view all attendance records
    $stmt = db()->query("
        SELECT a.*, s.title, s.sitting_date, u.username
        FROM attendance a
        JOIN sittings s ON s.id = a.sitting_id
        JOIN users u ON u.id = a.mca_id
        ORDER BY a.created_at DESC
    ");
}

$rows = $stmt->fetchAll();
?>

<div class="page-heading">
    <div>
        <p class="eyebrow">PRESENCE RECORDS</p>
        <h1>Attendance</h1>
        <p class="muted">
            Record and track MCA attendance during approved sittings.
        </p>
    </div>
</div>

<?php if ($u['role'] === 'hansard'): ?>

<section class="panel form-panel">

    <h2>Record Sitting Attendance</h2>

    <form method="post" action="../backend/attendance_action.php">

        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="mark">

        <div class="two-col">

            <label>
                Approved Sitting

                <select name="sitting_id" required>
                    <option value="">-- Select Approved Sitting --</option>

                    <?php foreach ($sittings as $s): ?>
                        <option value="<?= (int)$s['id'] ?>">
                            <?= e($s['title']) ?>
                            (<?= e($s['sitting_date']) ?>)
                        </option>
                    <?php endforeach; ?>

                </select>
            </label>

            <label>
                Select MCA

                <select name="mca_username" required>
                    <option value="">-- Select MCA --</option>

                    <?php foreach ($mcas as $mca): ?>
                        <option value="<?= e($mca['username']) ?>">
                            <?= e($mca['username']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </label>

        </div>

        <div class="two-col">

            <label>
                Attendance Status

                <select name="state" required>
                    <option value="">-- Select Attendance --</option>
                    <option value="present">Present</option>
                    <option value="absent">Absent</option>
                    <option value="missed">Missed</option>
                </select>
            </label>

            <label>
                Note

                <input
                    type="text"
                    name="note"
                    placeholder="Optional attendance remarks"
                >
            </label>

        </div>

        <?php if (empty($sittings)): ?>

            <p class="muted">
                No approved sittings are currently available.
            </p>

        <?php elseif (empty($mcas)): ?>

            <p class="muted">
                No active MCA accounts were found.
            </p>

        <?php else: ?>

            <button class="btn btn-primary" type="submit">
                Submit Attendance to Speaker
            </button>

        <?php endif; ?>

    </form>

</section>

<?php endif; ?>


<section class="panel table-panel">

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>Sitting</th>

                    <?php if ($u['role'] !== 'mca'): ?>
                        <th>Member</th>
                    <?php endif; ?>

                    <th>Attendance</th>
                    <th>Approval Status</th>

                    <?php if ($u['role'] === 'speaker'): ?>
                        <th>Action</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($rows as $r): ?>

                    <tr>

                        <td>
                            <strong><?= e($r['title']) ?></strong>
                            <small><?= e($r['sitting_date']) ?></small>
                        </td>

                        <?php if ($u['role'] !== 'mca'): ?>
                            <td><?= e($r['username']) ?></td>
                        <?php endif; ?>

                        <td>
                            <?= status_badge($r['state']) ?>
                        </td>

                        <td>
                            <?= status_badge($r['status']) ?>
                        </td>

                        <?php if ($u['role'] === 'speaker'): ?>
                            <td>

                                <?php if ($r['status'] === 'submitted'): ?>

                                    <form
                                        method="post"
                                        action="../backend/attendance_action.php"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf"
                                            value="<?= e(csrf_token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$r['id'] ?>"
                                        >

                                        <button
                                            name="action"
                                            value="approve"
                                            class="btn btn-small"
                                            type="submit"
                                        >
                                            Approve
                                        </button>

                                    </form>

                                <?php else: ?>

                                    &mdash;

                                <?php endif; ?>

                            </td>
                        <?php endif; ?>

                    </tr>

                <?php endforeach; ?>

                <?php if (empty($rows)): ?>

                    <tr>
                        <td
                            colspan="<?= $u['role'] === 'mca' ? 3 : ($u['role'] === 'speaker' ? 5 : 4) ?>"
                            class="empty-state"
                        >
                            No attendance records found.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>

<?php include __DIR__ . '/../includes/page_bottom.php'; ?>
