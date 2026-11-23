<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/functions.php';

$uid    = $_SESSION['user_id'];
$types  = ['experience', 'education', 'project'];
$errors = [];

// Is this edit (has id) or create?
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
   ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$isEdit = $id !== false && $id !== null && $id > 0;

// Default (sticky) values
$data = ['entry_type' => 'experience', 'title' => '', 'organization' => '',
         'start_date' => '', 'end_date' => '', 'description' => ''];

if ($isEdit && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $s = $pdo->prepare('SELECT entry_type, title, organization, start_date, end_date, description
                        FROM resume_entries WHERE id = :id AND user_id = :uid');
    $s->execute([':id' => $id, ':uid' => $uid]);
    $row = $s->fetch();
    if (!$row) {
        http_response_code(404);
        exit('Entry not found.');
    }
    $data = $row;
    $data['end_date'] = $row['end_date'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        exit('Invalid request token.');
    }
    $data['entry_type']   = $_POST['entry_type'] ?? '';
    $data['title']        = trim($_POST['title'] ?? '');
    $data['organization'] = trim($_POST['organization'] ?? '');
    $data['start_date']   = trim($_POST['start_date'] ?? '');
    $data['end_date']     = trim($_POST['end_date'] ?? '');
    $data['description']  = trim($_POST['description'] ?? '');

    // ---- validation ----
    if (!in_array($data['entry_type'], $types, true)) $errors['entry_type'] = 'Invalid type.';
    if ($data['title'] === '' || mb_strlen($data['title']) > 120)
        $errors['title'] = 'Title is required (max 120 chars).';
    if ($data['organization'] === '' || mb_strlen($data['organization']) > 120)
        $errors['organization'] = 'Organization is required (max 120 chars).';
    if (!valid_date($data['start_date'])) $errors['start_date'] = 'Valid start date required.';
    if ($data['end_date'] !== '') {
        if (!valid_date($data['end_date'])) $errors['end_date'] = 'Invalid end date.';
        elseif (!isset($errors['start_date']) && $data['end_date'] < $data['start_date'])
            $errors['end_date'] = 'End date cannot be before start date.';
    }
    if ($data['description'] === '') $errors['description'] = 'Description is required.';

    if (!$errors) {
        $end = $data['end_date'] === '' ? null : $data['end_date']; // '' -> NULL

        if ($isEdit) {
            $sql = 'UPDATE resume_entries
                    SET entry_type=:t, title=:ti, organization=:o,
                        start_date=:sd, end_date=:ed, description=:d
                    WHERE id=:id AND user_id=:uid';
            $params = [':t'=>$data['entry_type'], ':ti'=>$data['title'], ':o'=>$data['organization'],
                       ':sd'=>$data['start_date'], ':ed'=>$end, ':d'=>$data['description'],
                       ':id'=>$id, ':uid'=>$uid];
        } else {
            $sql = 'INSERT INTO resume_entries
                      (user_id, entry_type, title, organization, start_date, end_date, description)
                    VALUES (:uid, :t, :ti, :o, :sd, :ed, :d)';
            $params = [':uid'=>$uid, ':t'=>$data['entry_type'], ':ti'=>$data['title'],
                       ':o'=>$data['organization'], ':sd'=>$data['start_date'],
                       ':ed'=>$end, ':d'=>$data['description']];
        }
        $pdo->prepare($sql)->execute($params);
        header('Location: admin.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'Edit Entry' : 'New Entry';
require __DIR__ . '/../includes/header.php';
?>
<h1><?= $isEdit ? 'Edit' : 'Add' ?> Entry</h1>

<?php if ($errors): ?>
  <ul class="error"><?php foreach ($errors as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
<?php endif; ?>

<form method="POST" action="entry_form.php<?= $isEdit ? '?id=' . (int)$id : '' ?>">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>

  <label>Type
    <select name="entry_type">
      <?php foreach ($types as $t): ?>
        <option value="<?= e($t) ?>" <?= $data['entry_type'] === $t ? 'selected' : '' ?>><?= e(ucfirst($t)) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Title <input name="title" maxlength="120" value="<?= e($data['title']) ?>" required></label>
  <label>Organization <input name="organization" maxlength="120" value="<?= e($data['organization']) ?>" required></label>
  <label>Start date <input type="date" name="start_date" value="<?= e($data['start_date']) ?>" required></label>
  <label>End date (blank = Present) <input type="date" name="end_date" value="<?= e($data['end_date']) ?>"></label>
  <label>Description <textarea name="description" rows="5" required><?= e($data['description']) ?></textarea></label>

  <button type="submit"><?= $isEdit ? 'Update' : 'Create' ?></button>
  <a href="admin.php">Cancel</a>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>