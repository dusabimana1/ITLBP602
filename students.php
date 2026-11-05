<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/Amina/database.php';
require_once __DIR__ . '/includes/functions.php';
verify_csrf();
$error = '';
$edit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save') {
            $id = (int)($_POST['student_id'] ?? 0);
            $admission = trim($_POST['admission_number'] ?? '');
            $names = trim($_POST['names'] ?? '');
            $gender = $_POST['gender'] ?? '';
            $dob = $_POST['date_of_birth'] ?? '';
            $classId = ($_POST['class_id'] ?? '') === '' ? null : (int)$_POST['class_id'];
            $phone = trim($_POST['phone_number'] ?? '');
            $address = trim($_POST['address'] ?? '');
            if ($admission === '' || $names === '' || !in_array($gender, ['Male','Female'], true) || $dob === '') {
                throw new RuntimeException('Complete all required student fields.');
            }
            if ($id > 0) {
                $stmt = $conn->prepare('UPDATE students SET admission_number=?, names=?, gender=?, date_of_birth=?, class_id=?, 
                phone_number=?, address=? WHERE student_id=?' );
                $stmt->bind_param('ssssissi', $admission, $names, $gender, $dob, $classId, $phone, $address, $id);
                $stmt->execute();
                flash('Student updated successfully.');
            } else {
                $stmt = $conn->prepare('INSERT INTO students (admission_number,names,gender,date_of_birth,class_id,phone_number,address) VALUES (?,?,?,?,?,?,?)');
                $stmt->bind_param('ssssiss', $admission, $names, $gender, $dob, $classId, $phone, $address);
                $stmt->execute();
                flash('Student registered successfully.');
            }
            header('Location: students.php'); exit;
        } elseif ($action === 'delete') {
            if (($_SESSION['role'] ?? '') !== 'admin') {
                throw new RuntimeException('Only administrators can delete student records.');
            }
            $id = (int)($_POST['student_id'] ?? 0);
            $stmt = $conn->prepare('DELETE FROM students WHERE student_id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            flash('Student and related attendance/grade records deleted.');
            header('Location: students.php'); exit;
        }
    } catch (mysqli_sql_exception $e) {
        $error = $e->getCode() === 1062 ? 'That admission number already exists.' : 'Database operation failed. Check the information and try again.';
    } catch (RuntimeException $e) { $error = $e->getMessage(); }
}
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM students WHERE student_id=?');
    $stmt->bind_param('i', $id); $stmt->execute(); $edit = $stmt->get_result()->fetch_assoc();
}



$classRows = $conn->query('SELECT class_id,class_name FROM classes ORDER BY class_name')->fetch_all(MYSQLI_ASSOC);
$students = $conn->query('SELECT s.*, c.class_name FROM students s LEFT JOIN classes c ON c.class_id=s.class_id ORDER BY s.student_id DESC')->fetch_all(MYSQLI_ASSOC);
$page_title = 'Students';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h2 mb-3">Student registration and records</h1>
<?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>
<div class="card mb-4"><div class="card-body"><h2 class="h5"><?= $edit ? 'Update student' : 'Register new student' ?></h2>
<form method="post" class="row g-3">
<input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="student_id" value="<?= h($edit['student_id'] ?? '') ?>">
<div class="col-md-4"><label class="form-label">Admission number *</label>
<input class="form-control" name="admission_number" required value="<?= h($edit['admission_number'] ?? '') ?>"></div>
<div class="col-md-4"><label class="form-label">Student full name *</label>
<input class="form-control" name="names" required value="<?= h($edit['names'] ?? '') ?>"></div>
<div class="col-md-4"><label class="form-label">Gender *</label><select class="form-select" name="gender" required>
    <option value="">Choose...</option><?php foreach (['Male','Female'] as $g): ?><option <?= ($edit['gender'] ?? '') === $g ? 'selected' : '' ?>><?= h($g) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Date of birth *</label><input type="date" class="form-control" name="date_of_birth" required value="<?= h($edit['date_of_birth'] ?? '') ?>"></div>
<div class="col-md-4"><label class="form-label">Class</label><select class="form-select" name="class_id">
    <option value="">Not assigned</option><?php foreach ($classRows as $c): ?><option value="<?= (int)$c['class_id'] ?>" 
        <?= (string)($edit['class_id'] ?? '') === (string)$c['class_id'] ? 'selected' : '' ?>><?= h($c['class_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Phone number</label><input class="form-control" name="phone_number" value="<?= h($edit['phone_number'] ?? '') ?>"></div>
<div class="col-md-8"><label class="form-label">Address</label><input class="form-control" name="address" value="<?= h($edit['address'] ?? '') ?>"></div>
<div class="col-12"><button class="btn btn-primary"><?= $edit ? 'Save changes' : 'Register student' ?></button> 
<?php if ($edit): ?><a class="btn btn-secondary" href="students.php">Cancel</a><?php endif; ?></div>
</form></div></div>
<div class="card"><div class="card-body"><div class="d-flex justify-content-between gap-2 flex-wrap"><h2 class="h5">Student list</h2><input id="tableSearch" class="form-control search-box" placeholder="Search records..."></div>
<div class="table-responsive mt-3"><table class="table table-striped align-middle searchable-table"><thead>
    <tr>
        <th>NO</th>
        <th>Admission No.</th>
        <th>Name</th>
        <th>Gender</th>
        <th>Date of birth</th>
        <th>Class</th>
        <th>Phone</th>
        <th>Actions</th>
    </tr>
</thead>
    <tbody>
<?php foreach ($students as $s): ?>
    <tr>
        <td ><?php echo $s['student_id'];?></td>
        <td><?= h($s['admission_number']) ?></td>
        <td><?= h($s['names']) ?></td>
        <td><?= h($s['gender']) ?></td>
        <td><?= h($s['date_of_birth']) ?></td>
        <td><?= h($s['class_name'] ?? 'Not assigned') ?></td>
        <td><?= h($s['phone_number'] ?? '') ?></td>
        <td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="students.php?edit=<?= (int)$s['student_id'] ?>">Update</a> 

        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <form method="post" class="d-inline delete-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="student_id" value="<?= (int)$s['student_id'] ?>">
                <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form><?php endif; ?></td></tr><?php endforeach; ?>
<?php if (!$students): ?>
      
    <tr><td colspan="7" class="text-center text-muted">No students registered yet.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
