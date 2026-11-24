
<?php
$title = 'Committees';
include __DIR__ . '/../includes/page_top.php';

$u = current_user();
$pdo = db();

// Retrieve committees and chairperson details.
$rows = $pdo->query("
    SELECT
        c.*,
        chair.username AS chairperson_name,

        (
            SELECT COUNT(*)
            FROM committee_members cm
            WHERE cm.committee_id = c.id
              AND cm.status = 'active'
        ) AS member_count,

        (
            SELECT COUNT(*)
            FROM committee_meetings mt
            WHERE mt.committee_id = c.id
              AND mt.status = 'scheduled'
        ) AS meeting_count

    FROM committees c

    LEFT JOIN users chair
        ON chair.id = c.chairperson_id

    ORDER BY c.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Retrieve active MCA accounts.
$mcAs = $pdo->query("
    SELECT id, username
    FROM users
    WHERE role = 'mca'
      AND active = 1
    ORDER BY username
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-heading">
    <div>
        <p class="eyebrow">COMMITTEE WORKSPACE</p>
        <h1>Committees</h1>
        <p class="muted">
            Establish committees, manage membership and coordinate meetings.
        </p>
    </div>

    <?php if ($u['role'] === 'hansard'): ?>
        <button class="btn btn-primary" data-open="committee-form">
            Create committee <span>＋</span>
        </button>
    <?php endif; ?>
</div>

<?php if ($u['role'] === 'hansard'): ?>

<section class="panel form-panel hidden" id="committee-form">

    <h2>Create new committee</h2>

    <form method="post" action="../backend/committee_action.php">

        <input type="hidden" name="csrf"
               value="<?= e(csrf_token()) ?>">

        <input type="hidden" name="action" value="create">

        <label>
            Committee name
            <input type="text"
                   name="committee_name"
                   placeholder="Enter committee name"
                   required>
        </label>

        <label>
            Committee type
            <select name="committee_type" required>
                <option value="sectoral">Sectoral</option>
                <option value="select">Select</option>
                <option value="watchdog">Watchdog</option>
            </select>
        </label>

        <label>
            Committee description
            <textarea name="description"
                      rows="4"
                      placeholder="Describe the committee's responsibilities"></textarea>
        </label>

        <label>
            Initial chairperson
            <select name="chairperson_id" required>
                <option value="">Select chairperson</option>

                <?php foreach ($mcAs as $mca): ?>
                    <option value="<?= (int)$mca['id'] ?>">
                        <?= e($mca['username']) ?>
                    </option>
                <?php endforeach; ?>

            </select>
        </label>

        <button class="btn btn-primary" type="submit">
            Submit committee for approval
        </button>

    </form>

</section>

<?php endif; ?>

<section class="card-grid">

    <?php foreach ($rows as $r): ?>

        <article class="panel">

            <div class="card-top">
                <?= status_badge($r['approval_status']) ?>
                <small><?= e($r['created_at']) ?></small>
            </div>

            <h2><?= e($r['committee_name']) ?></h2>

            <p class="muted">
                <?= e($r['description'] ?? 'No description provided.') ?>
            </p>

            <dl class="detail-list">

                <dt>Committee type</dt>
                <dd><?= e(ucfirst($r['committee_type'])) ?></dd>

                <dt>Chairperson</dt>
                <dd>
                    <?= e($r['chairperson_name'] ?? 'Not assigned') ?>
                </dd>

                <dt>Members</dt>
                <dd><?= (int)$r['member_count'] ?></dd>

                <dt>Scheduled meetings</dt>
                <dd><?= (int)$r['meeting_count'] ?></dd>

                <dt>Operational status</dt>
                <dd><?= e(ucfirst($r['status'])) ?></dd>

            </dl>

            <?php if (
                $u['role'] === 'speaker' &&
                $r['approval_status'] === 'pending'
            ): ?>

                <div class="action-group">

                    <form method="post"
                          action="../backend/committee_action.php">

                        <input type="hidden" name="csrf"
                               value="<?= e(csrf_token()) ?>">

                        <input type="hidden" name="action"
                               value="approve">

                        <input type="hidden" name="id"
                               value="<?= (int)$r['id'] ?>">

                        <button class="btn btn-primary" type="submit">
                            Approve
                        </button>

                    </form>

                    <form method="post"
                          action="../backend/committee_action.php">

                        <input type="hidden" name="csrf"
                               value="<?= e(csrf_token()) ?>">

                        <input type="hidden" name="action"
                               value="reject">

                        <input type="hidden" name="id"
                               value="<?= (int)$r['id'] ?>">

                        <button class="btn btn-danger" type="submit">
                            Reject
                        </button>

                    </form>

                </div>

            <?php endif; ?>

            <?php if ($r['approval_status'] === 'approved'): ?>

                <a href="committee_details.php?id=<?= (int)$r['id'] ?>"
                   class="btn btn-primary">
                    Manage committee
                </a>

            <?php endif; ?>

        </article>

    <?php endforeach; ?>

    <?php if (!$rows): ?>

        <div class="panel empty-state">
            No committees have been created yet.
        </div>

    <?php endif; ?>

</section>

<?php include __DIR__ . '/../includes/page_bottom.php'; ?>
