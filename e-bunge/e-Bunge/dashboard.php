
<?php
require_once __DIR__ . '/config.php';

// Ensure the user is logged in
require_login();

// Page configuration
$title = 'Dashboard';
$root_prefix = '';
$asset_prefix = '';

// Get current user
$user = current_user();
$user_id = $user['id'];
$username = $user['username'];
$user_role = role_name($user['role']);

// Dashboard statistics


$open_items = count_query(
    "SELECT COUNT(*) FROM motions
     WHERE mover_id = ?
     AND UPPER(status) NOT IN ('APPROVED', 'DENIED')",
    [(string) $user_id]
);

$pending_items = count_query(
    "SELECT COUNT(*) FROM motions
     WHERE LOWER(status) IN ('submitted', 'reviewed', 'pending')"
);

$approved_items = count_query(
    "SELECT COUNT(*) FROM motions
     WHERE LOWER(status) = 'approved'"
);

$pending_petitions = count_query(
    "SELECT COUNT(*) FROM petitions
     WHERE LOWER(status) = 'submitted'"
);


// Retrieve recent system activities
$events = db()->query(
    "SELECT audit_logs.*, users.username
     FROM audit_logs
     LEFT JOIN users ON users.id = audit_logs.user_id
     ORDER BY audit_logs.id DESC
     LIMIT 5"
)->fetchAll();

// Load page header
include __DIR__ . '/includes/header.php';
?>

<div class="app-shell">
    <!-- Sidebar -->
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <!-- Main dashboard content -->
    <main class="main-content">

        <!-- Page heading -->
        <div class="page-heading">

            <div>
                <p class="eyebrow">
                    <?= e($user_role); ?>
                </p>

                <h1>
                    Welcome, <?= e($username); ?>.
                </h1>

                <p class="muted">
                    DASHBOARD
                </p>
            </div>

            <a href="pages/profile.php" class="btn btn-primary">
                Account settings <span>→</span>
            </a>

        </div>

        <!-- Dashboard statistics -->
        <section class="metric-grid">

            <div class="metric-card">
                <span>My open items</span>
                <strong><?= (int) $open_items; ?></strong>
                
            </div>

            <div class="metric-card">
                <span>Awaiting my action</span>
                <strong><?= (int) $pending_items; ?></strong>
                
            </div>

            <div class="metric-card">
                <span>Approved records</span>
                <strong><?= (int) $approved_items; ?></strong>
               
            </div>

            <div class="metric-card accent">
                <span>Public petitions</span>
                <strong><?= (int) $pending_petitions; ?></strong>
               
            </div>

        </section>

        <!-- Dashboard panels -->
        <div class="dashboard-grid">

            <!-- Activity tracker -->
            <section class="panel activity-panel">

                <div class="section-head">

                    <div>
                        <p class="eyebrow">ACTIVITY TRACKER</p>
                        <h2>Track your workflow</h2>
                    </div>

                    <span class="live-pill">● Live</span>

                </div>

                <div class="tracker">

                    <div class="tracker-line"></div>

                    <?php if (empty($events)): ?>

                        <div class="empty-state">
                            Your recent actions will appear here.
                        </div>

                    <?php else: ?>

                        <?php foreach ($events as $event): ?>

                            <div class="tracker-item">

                                <span class="tracker-dot"></span>

                                <div>
                                    <strong>
                                        <?= e($event['action']); ?>
                                    </strong>

                                    <small>
                                        <?= e($event['username'] ?? 'Public user'); ?>
                                        ·
                                        <?= e($event['created_at']); ?>
                                    </small>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </section>

            <!-- Quick access -->
            <section class="panel quick-panel">

                <div class="section-head">

                    <div>
                        <p class="eyebrow">QUICK ACCESS</p>
                        <h2>Go to a workspace</h2>
                    </div>

                </div>

                <div class="quick-links">

                    <a href="pages/motions.php">
                        <span>↗</span>
                        <div>
                            <b>Motions register</b>
                            <small>Submit and follow business</small>
                        </div>
                    </a>

                    <a href="pages/sittings.php">
                        <span>◷</span>
                        <div>
                            <b>Sittings schedule</b>
                            <small>View upcoming house work</small>
                        </div>
                    </a>

                    <a href="pages/petitions.php">
                        <span>⌁</span>
                        <div>
                            <b>Petitions</b>
                            <small>Review public submissions</small>
                        </div>
                    </a>

                    <a href="public_portal.php">
                        <span>◎</span>
                        <div>
                            <b>Public portal</b>
                            <small>See approved information</small>
                        </div>
                    </a>

                </div>

            </section>

        </div>

    </main>

</div>

<?php
// Load page footer
include __DIR__ . '/includes/footer.php';
?>
