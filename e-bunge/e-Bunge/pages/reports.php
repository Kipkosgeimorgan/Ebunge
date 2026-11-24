<?php 
$title = 'Reports'; 
include __DIR__ . '/../includes/page_top.php'; 

$u = current_user(); 
$rows = db()->query('SELECT r.*, u.username FROM reports r LEFT JOIN users u ON u.id = r.created_by ORDER BY r.id DESC')->fetchAll(); 
?>

<div class="page-heading">
  <div>
    <p class="eyebrow">OFFICIAL OUTPUTS</p>
    <h1>Reports</h1>
    <p class="muted">Forms and summaries approved for authorised download.</p>
  </div>
  <?php if ($u['role'] === 'hansard'): ?>
    <button class="btn btn-primary" data-open="report-form">Create report <span>＋</span></button>
  <?php endif; ?>
</div>

<?php if ($u['role'] === 'hansard'): ?>
  <section class="panel form-panel hidden" id="report-form">
    <h2>Create a report</h2>
    <form method="post" action="../backend/report_action.php">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="create">

      <label>
        Report title
        <input name="title" required>
      </label>

      <label>
        Category
        <input name="category" required placeholder="Sitting summary, motion register...">
      </label>

      <label>
        Report content
        <textarea name="content" rows="6" required></textarea>
      </label>

      <button class="btn btn-primary" type="submit">Submit for approval</button>
    </form>
  </section>
<?php endif; ?>

<section class="card-grid">
  <?php foreach ($rows as $r): ?>
    <article class="panel report-card">
      <div class="card-top">
        <span><?= status_badge($r['status']) ?></span>
        <small><?= e($r['category']) ?></small>
      </div>

      <h2><?= e($r['title']) ?></h2>
      <p><?= e(mb_strimwidth($r['content'], 0, 180, '…')) ?></p>

      <?php if ($u['role'] === 'speaker' && $r['status'] === 'pending'): ?>
        <form method="post" action="../backend/report_action.php">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="id" value="<?= $r['id'] ?>">
          <button name="action" value="approve" class="btn btn-small" type="submit">Approve report</button>
        </form>
      <?php elseif ($r['status'] === 'approved'): ?>
        <a class="btn btn-small btn-soft" href="../backend/report_action.php?id=<?= $r['id'] ?>&amp;download=1">Download</a>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>

  <?php if (!$rows): ?>
    <div class="panel empty-state">No reports available.</div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/../includes/page_bottom.php'; ?>