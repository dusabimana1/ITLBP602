<?php require_once __DIR__ . '/functions.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($page_title ?? 'LOL School SMS') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="dashboard.php">LOL School SMS</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="mainNav">
      <div class="navbar-nav me-auto">
        <a class="nav-link" href="dashboard.php">Dashboard</a>
        <a class="nav-link" href="students.php">Students</a>
        <a class="nav-link" href="classes.php">Classes</a>
        <a class="nav-link" href="attendance.php">Attendance</a>
        <a class="nav-link" href="grades.php">Grades</a>
        <a class="nav-link" href="announcements.php">Announcements</a>
      </div>
      <span class="navbar-text text-white me-3"><?= h($_SESSION['username'] ?? '') ?></span>
      <a class="btn btn-light btn-sm" href="logout.php">Log out</a>
    </div>
  </div>
</nav>
<main class="container py-4">
<?php show_flash(); ?>
