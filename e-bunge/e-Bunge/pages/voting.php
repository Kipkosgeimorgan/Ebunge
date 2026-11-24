
<?php
$title = 'Voting Centre';
include __DIR__ . '/../includes/page_top.php';

$u = current_user();
$pdo = db();

$rows = $pdo->query("
    SELECT v.*, u.username
    FROM votes v
    LEFT JOIN users u ON u.id = v.created_by
    ORDER BY v.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-heading">
    <div>
        <p class="eyebrow">DECISION RECORDS</p>
        <h1>Voting Centre</h1>
        <p class="muted">Create voting subjects, conduct MCA voting and view results.</p>
    </div>

    <?php if ($u['role'] === 'hansard'): ?>
        <button class="btn btn-primary" data-open="vote-form">
            Create Voting Subject <span>＋</span>
        </button>
    <?php endif; ?>
</div>

<?php if ($u['role'] === 'hansard'): ?>

<section class="panel form-panel hidden" id="vote-form">
    <h2>Create Voting Subject</h2>

    <form method="post" action="../backend/voting_action.php">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="create">

        <label>
            Related Motion
            <select name="motion_id">
                <option value="">No linked motion</option>
                <?php
                $motions = $pdo->query("
                    SELECT id, motion_topic
                    FROM motions
                    WHERE LOWER(status) = 'approved'
                    ORDER BY id DESC
                ")->fetchAll(PDO::FETCH_ASSOC);

                foreach ($motions as $motion):
                ?>
                    <option value="<?= (int)$motion['id'] ?>">
                        <?= e($motion['motion_topic']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Voting Subject / Question
            <input type="text" name="title" maxlength="255" required>
        </label>

        <label>
            Voting Type
            <select name="vote_type" required>
                <option value="open">Open Voting</option>
                <option value="secret">Secret Voting</option>
            </select>
        </label>

        <label>
            Approval Threshold
            <select name="threshold" required>
                <option value="simple">Simple Majority</option>
                <option value="absolute">Absolute Majority</option>
                <option value="two_thirds">Two-Thirds Majority</option>
            </select>
        </label>

        <label>
            Voting Duration (Seconds)
            <input type="number" name="duration_seconds"
                   min="10" max="86400" value="60" required>
        </label>

        <button class="btn btn-primary" type="submit">
            Submit for Speaker Approval
        </button>
    </form>
</section>

<?php endif; ?>

<section class="card-grid">

<?php foreach ($rows as $r): ?>

    <?php
    $voteId = (int)$r['id'];

    $voted = false;

    if ($u['role'] === 'mca') {
        $check = $pdo->prepare("
            SELECT COUNT(*)
            FROM vote_records
            WHERE vote_id = ? AND mca_id = ?
        ");
        $check->execute([$voteId, $u['id']]);
        $voted = (bool)$check->fetchColumn();
    }

    $counts = [
        'propose' => 0,
        'oppose' => 0,
        'abstain' => 0
    ];

    $countStmt = $pdo->prepare("
        SELECT choice, COUNT(*) AS total
        FROM vote_records
        WHERE vote_id = ?
        GROUP BY choice
    ");
    $countStmt->execute([$voteId]);

    foreach ($countStmt->fetchAll(PDO::FETCH_ASSOC) as $result) {
        if (isset($counts[$result['choice']])) {
            $counts[$result['choice']] = (int)$result['total'];
        }
    }

    $proposeVotes = $counts['propose'];
    $opposeVotes = $counts['oppose'];
    $abstentions = $counts['abstain'];

    $totalVotes = $proposeVotes + $opposeVotes + $abstentions;

    $proposePercent = $totalVotes ? ($proposeVotes / $totalVotes) * 100 : 0;
    $opposePercent = $totalVotes ? ($opposeVotes / $totalVotes) * 100 : 0;
    $abstainPercent = $totalVotes ? ($abstentions / $totalVotes) * 100 : 0;
    ?>

    <article class="panel vote-card">

        <div class="card-top">
            <?= status_badge($r['status']) ?>
            <small><?= e($r['created_at']) ?></small>
        </div>

        <h2><?= e($r['title'] ?: $r['subject']) ?></h2>

        <p>
            Voting Type: <?= e(ucfirst($r['vote_type'] ?? 'open')) ?><br>
            Threshold:
            <?= e(ucwords(str_replace('_', ' ', $r['threshold'] ?? 'simple'))) ?><br>
            Duration: <?= (int)($r['duration_seconds'] ?? 60) ?> seconds
        </p>

        <?php if ($u['role'] === 'speaker' && $r['status'] === 'draft'): ?>

            <form method="post" action="../backend/voting_action.php">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= $voteId ?>">

                <button name="action" value="approve"
                        class="btn btn-small" type="submit">
                    Approve Voting Subject
                </button>
            </form>

        <?php elseif ($u['role'] === 'mca' && $r['status'] === 'open' && !$voted): ?>

            <form method="post" action="../backend/voting_action.php"
                  class="vote-buttons">

                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= $voteId ?>">
                <input type="hidden" name="action" value="vote">

                <button name="choice" value="propose"
                        class="btn btn-primary btn-small" type="submit">
                    Propose
                </button>

                <button name="choice" value="oppose"
                        class="btn btn-soft btn-small" type="submit">
                    Oppose
                </button>

                <button name="choice" value="abstain"
                        class="btn btn-small" type="submit">
                    Abstain
                </button>
            </form>

        <?php elseif ($u['role'] === 'mca' && $voted): ?>

            <p class="muted">Your vote has been recorded successfully.</p>

        <?php endif; ?>

        <div class="vote-results">

            <h3>Voting Results</h3>

            <div class="result-item">
                <span>Propose</span>
                <strong><?= $proposeVotes ?></strong>
            </div>

            <div class="result-bar">
                <div style="width: <?= $proposePercent ?>%; background:#16a34a;"></div>
            </div>

            <div class="result-item">
                <span>Oppose</span>
                <strong><?= $opposeVotes ?></strong>
            </div>

            <div class="result-bar">
                <div style="width: <?= $opposePercent ?>%; background:#dc2626;"></div>
            </div>

            <div class="result-item">
                <span>Abstain</span>
                <strong><?= $abstentions ?></strong>
            </div>

            <div class="result-bar">
                <div style="width: <?= $abstainPercent ?>%; background:#d97706;"></div>
            </div>

            <div class="result-total">
                Total Votes:
                <strong><?= $totalVotes ?></strong>
            </div>

        </div>

    </article>

<?php endforeach; ?>

<?php if (empty($rows)): ?>
    <p class="empty-state">No voting subjects available.</p>
<?php endif; ?>

</section>



<?php include __DIR__ . '/../includes/page_bottom.php'; ?>
