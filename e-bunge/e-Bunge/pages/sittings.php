
<?php
$title = 'Sittings';

include __DIR__ . '/../includes/page_top.php';

$u = current_user();

$rows = db()->query("
    SELECT *
    FROM sittings
    ORDER BY sitting_date DESC, start_time DESC
")->fetchAll();
?>

<div class="page-heading">
    <div>
        <p class="eyebrow">HOUSE CALENDAR</p>
        <h1>Sittings</h1>
        <p class="muted">
            Past, current and future sittings with approved assembly information.
        </p>
    </div>

    <?php if ($u['role'] === 'hansard'): ?>
        <button class="btn btn-primary" data-open="sitting-form">
            Schedule sitting <span>＋</span>
        </button>
    <?php endif; ?>
</div>

<?php if ($u['role'] === 'hansard'): ?>
<section class="panel form-panel hidden" id="sitting-form">

    <h2>Schedule a sitting</h2>

    <form method="POST" action="../backend/sitting_action.php">

        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="create">

        <div class="two-col">

            <label>
                Sitting Title
                <input type="text" name="title" maxlength="200"
                       placeholder="Enter sitting title" required>
            </label>

            <label>
                Sitting Type
                <select name="sitting_type" required>
                    <option value="">Select sitting type</option>
                    <option value="ORDINARY">Ordinary</option>
                    <option value="SPECIAL">Special</option>
                    <option value="COMMITTEE_OF_WHOLE_HOUSE">
                        Committee of the Whole House
                    </option>
                </select>
            </label>

        </div>

        <div class="two-col">

            <label>
                Sitting Date
                <input type="date" name="sitting_date" required>
            </label>

            <label>
                Start Time
                <input type="time" name="start_time" required>
            </label>

        </div>

        <div class="two-col">

            <label>
                Venue
                <input type="text" name="venue" maxlength="150"
                       placeholder="Enter sitting venue" required>
            </label>

            <label>
                Sitting Mode
                <select name="mode" required>
                    <option value="PHYSICAL">Physical</option>
                    <option value="VIRTUAL">Virtual</option>
                    <option value="HYBRID">Hybrid</option>
                </select>
            </label>

        </div>

        <label>
            Convened By
            <select name="convened_by" required>
                <option value="">Select convening authority</option>
                <option value="SPEAKER">Speaker</option>
                <option value="CLERK">Clerk</option>
            </select>
        </label>

        <label>
            Purpose
            <textarea name="purpose" rows="3"
                      placeholder="Describe the purpose of the sitting"></textarea>
        </label>

        <label>
            Agenda
            <textarea name="agenda" rows="5"
                      placeholder="Enter the proposed sitting agenda"></textarea>
        </label>

        <button class="btn btn-primary" type="submit">
            Submit Sitting
        </button>

    </form>
</section>
<?php endif; ?>

<section class="panel table-panel">

    <div class="table-wrap">
        <table>

            <thead>
                <tr>
                    <th>Sitting</th>
                    <th>Type</th>
                    <th>Date &amp; Time</th>
                    <th>Venue</th>
                    <th>Mode</th>
                    <th>Convened By</th>
                    <th>Agenda</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($rows as $r): ?>
                <tr>

                    <td>
                        <strong><?= e($r['title']) ?></strong>
                        <small><?= e($r['purpose'] ?? '') ?></small>
                    </td>

                    <td>
                        <?= e(str_replace('_', ' ', $r['sitting_type'])) ?>
                    </td>

                    <td>
                        <?= e($r['sitting_date']) ?>
                        <small><?= e($r['start_time']) ?></small>
                    </td>

                    <td><?= e($r['venue'] ?? 'Not specified') ?></td>

                    <td><?= e($r['mode']) ?></td>

                    <td><?= e($r['convened_by']) ?></td>

                    <td>
                        <?= e(mb_strimwidth($r['agenda'] ?? '', 0, 90, '…')) ?>
                    </td>

                    <td><?= status_badge($r['status']) ?></td>

                    <td>
                        <?php if (
                            $u['role'] === 'speaker' &&
                            strtoupper($r['status']) === 'DRAFT'
                        ): ?>

                            <form method="POST" action="../backend/sitting_action.php">

                                <input type="hidden" name="csrf"
                                       value="<?= e(csrf_token()) ?>">

                                <input type="hidden" name="id"
                                       value="<?= (int)$r['id'] ?>">

                                <button name="action" value="approve"
                                        class="btn btn-small" type="submit">
                                    Approve
                                </button>

                            </form>

                        <?php else: ?>
                            <span class="muted">&mdash;</span>
                        <?php endif; ?>
                    </td>

                </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="9" class="empty-state">
                            No sittings scheduled.
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>

        </table>
    </div>

</section>

<?php include __DIR__ . '/../includes/page_bottom.php'; ?>
