<?php
session_start();
if (empty($_SESSION['user_id'])) {      // GUARD: must be first
    header('Location: login.php');
    exit;
}
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/functions.php';

$uid     = $_SESSION['user_id'];
$message = '';
$errors  = [];

// ---- Avatar upload ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors[] = 'Invalid request token.';
    } elseif (!isset($_FILES['avatar'])) {
        $errors[] = 'No file selected.';
    } else {
        [$newPath, $err] = process_avatar($_FILES['avatar'], __DIR__ . '/uploads');
        if ($err) {
            $errors[] = $err;
        } else {
            // get old path first so we can delete it
            $s = $pdo->prepare('SELECT avatar_path FROM users WHERE id = :id');
            $s->execute([':id' => $uid]);
            $old = $s->fetchColumn();

            $u = $pdo->prepare('UPDATE users SET avatar_path = :p WHERE id = :id');
            $u->execute([':p' => $newPath, ':id' => $uid]);

            if ($old && is_file(__DIR__ . '/' . $old)) {
                unlink(__DIR__ . '/' . $old);        // remove obsolete file
            }
            $message = 'Profile picture updated.';
        }
    }
}

// ---- Read data for display ----
$s = $pdo->prepare('SELECT full_name, avatar_path FROM users WHERE id = :id');
$s->execute([':id' => $uid]);
$me = $s->fetch();

$s = $pdo->prepare('SELECT id, entry_type, title, organization, start_date, end_date
                    FROM resume_entries WHERE user_id = :uid
                    ORDER BY start_date DESC');
$s->execute([':uid' => $uid]);
$rows = $s->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<h1>Dashboard</h1>
<?php if ($message): ?><p class="success"><?= e($message) ?></p><?php endif; ?>
<?php foreach ($errors as $err): ?><p class="error"><?= e($err) ?></p><?php endforeach; ?>

<h2>Profile Portrait</h2>
<?php if ($me['avatar_path']): ?>
  <img src="<?= e($me['avatar_path']) ?>" width="150" height="150" alt="Current avatar">
<?php endif; ?>
<form method="POST" enctype="multipart/form-data" action="admin.php">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <input type="file" name="avatar" accept="image/jpeg,image/png" required>
  <button type="submit" name="upload_avatar">Upload</button>
</form>

<h2>Resume Entries</h2>
<p><a href="entry_form.php">+ Add new entry</a></p>

<?php if (count($rows) === 0): ?>
  <p class="empty">No entries yet.</p>
<?php else: ?>
<table>
  <thead><tr><th>Type</th><th>Title</th><th>Organization</th><th>Dates</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['entry_type']) ?></td>
      <td><?= e($r['title']) ?></td>
      <td><?= e($r['organization']) ?></td>
      <td><?= e($r['start_date']) ?> → <?= $r['end_date'] ? e($r['end_date']) : 'Present' ?></td>
      <td>
        <a href="entry_form.php?id=<?= (int)$r['id'] ?>">Edit</a>
        <form method="POST" action="delete_entry.php" style="display:inline"
              onsubmit="return confirm('Delete this entry permanently?');">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <button type="submit">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>