<?php 
$pageTitle = $pageTitle ?? 'Resume';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav>
  <a href="index.php">Public CV</a>
  <?php if (!empty($_SESSION['user_id'])): ?>
    <a href="admin.php">Dashboard</a>
    <a href="logout.php">Logout</a>
  <?php else: ?>
    <a href="login.php">Admin Login</a>
  <?php endif; ?>
</nav>
<main>