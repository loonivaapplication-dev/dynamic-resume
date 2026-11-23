<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['user_id'])) {   // already logged in
    header('Location: admin.php');
    exit;
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id, full_name, password FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);          // prevents session fixation
            $_SESSION['user_id']   = (int)$user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            header('Location: admin.php');
            exit;
        }
        $errors[] = 'Invalid email or password.'; // same message for both cases
    }
}

$pageTitle = 'Admin Login';
require __DIR__ . '/../includes/header.php';
?>
<h1>Administrator Login</h1>
<?php foreach ($errors as $err): ?>
  <p class="error"><?= e($err) ?></p>
<?php endforeach; ?>

<form method="POST" action="login.php">
  <label>Email <input type="email" name="email" value="<?= e($email) ?>" required></label>
  <label>Password <input type="password" name="password" required></label>
  <button type="submit">Log in</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>