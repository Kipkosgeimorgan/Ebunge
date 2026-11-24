<?php 
$title = 'Profile & access'; 
include __DIR__ . '/../includes/page_top.php'; 

$u = current_user(); 
?>

<div class="page-heading">
  <div>
    <p class="eyebrow">ACCOUNT CONTROL</p>
    <h1>Profile &amp; access</h1>
    <p class="muted">Your identity and permission boundary in e-Bunge.</p>
  </div>
</div>

<section class="panel profile-card">
  <div class="profile-avatar">
    <?= e(strtoupper(substr($u['username'], 0, 2))) ?>
  </div>
  <div>
    <h2><?= e($u['username']) ?></h2>
    <p><?= e($u['work_email']) ?></p>
    <span class="role-tag"><?= e(role_name($u['role'])) ?></span>
  </div>
</section>

<section class="panel">
  <div class="section-head">
    <div>
      
      <h2>What your role can do</h2>
    </div>
  </div>

  <div class="permission-grid">
    <div>
      <b>Read</b>
      <p>View records shared with your office and approved public information.</p>
    </div>
    <div>
      <b>Act</b>
      <p>
        <?php 
        if ($u['role'] === 'mca') {
            echo 'Submit motions and petitions, vote once on approved subjects, and view your records.';
        } elseif ($u['role'] === 'hansard') {
            echo 'Review submissions, schedule sittings, mark attendance, and prepare reports.';
        } else {
            echo 'Approve authorised motions, sittings, attendance, voting subjects, committees, petitions and reports.';
        }
        ?>
      </p>
    </div>
    <div>
      <b>Audit</b>
      <p>Important actions are recorded with a timestamp and user identity.</p>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/page_bottom.php'; ?>