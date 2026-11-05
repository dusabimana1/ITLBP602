<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/Amina/database.php';
require_once __DIR__ . '/includes/functions.php';
$page_title = 'Dashboard';
$students = (int)$conn->query('SELECT COUNT(*) AS n FROM students')->fetch_assoc()['n'];
$classes = (int)$conn->query('SELECT COUNT(*) AS n FROM classes')->fetch_assoc()['n'];
$announcements = (int)$conn->query('SELECT COUNT(*) AS n FROM announcements')->fetch_assoc()['n'];
require __DIR__ . '/includes/header.php';
?>

<h1 class="h2 mb-4">Dashboard</h1>
<div class="row g-3">
  <div class="col-md-4"><div class="card stat-card bg-primary text-white">
    <div class="card-body"><a href=""></a><div>Total students</div>
    <div class="display-5"><a href=""></a><?= $students ?></div></div></div></div>
  <div class="col-md-4"><div class="card stat-card bg-success text-white">
    <div class="card-body"><div>Total classes</div><div class="display-5"><?= $classes ?></div></div></div></div>
  <div class="col-md-4"><div class="card stat-card bg-warning"><div class="card-body">
    <div>Announcements</div><div class="display-5"><?= $announcements ?></div></div></div></div>
</div>
<div class="card mt-4"><div class="card-body"><h2 class="h5">Quick actions</h2><div class="d-flex flex-wrap gap-2"><a class="btn btn-primary" href="students.php">Register student</a><a class="btn btn-outline-primary" href="attendance.php">Record attendance</a><a class="btn btn-outline-primary" href="grades.php">Enter grades</a><a class="btn btn-outline-primary" href="announcements.php">Publish announcement</a></div></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
