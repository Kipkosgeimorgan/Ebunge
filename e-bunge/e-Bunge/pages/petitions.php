<?php 
$title = 'Petitions'; 
include __DIR__ . '/../includes/page_top.php'; 

$u = current_user(); 
$rows = db()->query('SELECT p.*, u.username FROM petitions p LEFT JOIN users u ON u.id = p.created_by ORDER BY p.id DESC')->fetchAll(); 
?>

<div class="page-heading">
  <div>
    <p class="eyebrow">PUBLIC VOICE</p>
    <h1>Petitions</h1>
    <p class="muted">Review petitions submitted through the public portal or by an MCA.</p>
  </div>
  <?php if ($u['role'] === 'mca'): ?>
    <button class="btn btn-primary" data-open="petition-form">Submit petition <span>＋</span></button>
  <?php endif; ?>
</div>

<?php if ($u['role'] === 'mca'): ?>
  <section class="panel form-panel hidden" id="petition-form">
    <h2>Submit a petition</h2>
    <form method="post" action="../backend/petition_action.php">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="create_mca">

      <label>
        Subject
        <input name="subject" required>
      </label>

      <label>
        Petitioner name
        <input name="petitioner_name" required>
      </label>

      <label>
        Petitioner email
        <input type="email" name="petitioner_email" required>
      </label>

      <label>
        Details
        <textarea name="details" rows="4" required></textarea>
      </label>

      <button class="btn btn-primary" type="submit">Submit petition</button>
    </form>
  </section>
<?php endif; ?>

<section class="panel table-panel">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Petition</th>
          <th>Petitioner</th>
          <th>Source</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <strong><?= e($r['subject']) ?></strong>
              <small><?= e(mb_strimwidth($r['details'], 0, 100, '…')) ?></small>
            </td>
            <td>
              <?= e($r['petitioner_name']) ?>
              <small><?= e($r['petitioner_email']) ?></small>
            </td>
            <td><?= e(ucfirst($r['source'])) ?></td>
            <td><?= status_badge($r['status']) ?></td>
            <td>
              <?php if ($u['role'] === 'hansard' && $r['status'] === 'submitted'): ?>
                <form method="post" action="../backend/petition_action.php" class="inline-form">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button name="action" value="review" class="btn btn-small" type="submit">Review</button>
                  <button name="action" value="reject" class="btn btn-small btn-soft" type="submit">Reject</button>
                </form>
              <?php elseif ($u['role'] === 'speaker' && $r['status'] === 'reviewed'): ?>
                <form method="post" action="../backend/petition_action.php">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button name="action" value="approve" class="btn btn-small" type="submit">Approve publication</button>
                </form>
              <?php else: ?>
                &mdash;
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (!$rows): ?>
          <tr>
            <td colspan="5" class="empty-state">No petitions found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php include __DIR__ . '/../includes/page_bottom.php'; ?>