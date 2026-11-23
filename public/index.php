<?php
declare(strict_types=1);
session_start(); // only so the nav can show Dashboard/Logout
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/functions.php';

// Profile (prepared even though no user input, for consistency)
$stmt = $pdo->prepare('SELECT id, full_name, headline, summary, avatar_path
                       FROM users ORDER BY id ASC LIMIT 1');
$stmt->execute();
$profile = $stmt->fetch();

$sections = [
    'experience' => 'Experience',
    'education'  => 'Education',
    'project'    => 'Projects',
];

$entries = [];
if ($profile) {
    $q = $pdo->prepare('SELECT title, organization, start_date, end_date, description
                        FROM resume_entries
                        WHERE user_id = :uid AND entry_type = :type
                        ORDER BY start_date DESC');
    foreach ($sections as $type => $label) {
        $q->execute([':uid' => $profile['id'], ':type' => $type]);
        $entries[$type] = $q->fetchAll();
    }
}

$pageTitle = $profile ? $profile['full_name'] . ' | CV' : 'Resume';
require __DIR__ . '/../includes/header.php';
?>

<?php if (!$profile): ?>
  <p class="empty">No profile has been created yet.</p>
<?php else: ?>
  <?php $avatar = $profile['avatar_path'] ?: 'assets/img/placeholder.png'; ?>
  <header class="cv-header">
    <img src="<?= e($avatar) ?>" width="150" height="150" alt="Profile photo">
    <h1><?= e($profile['full_name']) ?></h1>
    <h2><?= e($profile['headline']) ?></h2>
    <p><?= nl2br(e($profile['summary'])) ?></p>
  </header>

  <?php foreach ($sections as $type => $label): ?>
    <section>
      <h3><?= e($label) ?></h3>
      <?php if (count($entries[$type]) === 0): ?>
        <p class="empty">No <?= e(strtolower($label)) ?> entries yet.</p>
      <?php else: ?>
        <?php foreach ($entries[$type] as $row): ?>
          <article>
            <h4><?= e($row['title']) ?> &mdash; <?= e($row['organization']) ?></h4>
            <p class="dates">
              <?= e(date('M Y', strtotime($row['start_date']))) ?> –
              <?= $row['end_date'] ? e(date('M Y', strtotime($row['end_date']))) : 'Present' ?>
            </p>
            <p><?= nl2br(e($row['description'])) ?></p>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>