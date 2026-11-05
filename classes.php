
<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/Amina/database.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';
$edit = null;

// HANDLE CREATE, UPDATE AND DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $action = $_POST['action'] ?? '';

    // CREATE CLASS
    if ($action === 'create') {

        $name = trim($_POST['class_name'] ?? '');

        if ($name === '') {
            $error = 'Please enter a class name.';
        } else {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO classes (class_name) VALUES (?)"
                );
                $stmt->bind_param('s', $name);
                $stmt->execute();

                flash('Class created successfully.');
                header('Location: classes.php');
                exit;

            } catch (mysqli_sql_exception $e) {
                $error = 'Class name already exists or could not be created.';
            }
        }
    }

    // UPDATE CLASS
    elseif ($action === 'update') {

        $id = (int)($_POST['class_id'] ?? 0);
        $name = trim($_POST['class_name'] ?? '');

        if ($id <= 0 || $name === '') {
            $error = 'Please enter a valid class name.';
        } else {
            try {
                $stmt = $conn->prepare(
                    "UPDATE classes SET class_name = ? WHERE class_id = ?"
                );
                $stmt->bind_param('si', $name, $id);
                $stmt->execute();

                flash('Class updated successfully.');
                header('Location: classes.php');
                exit;

            } catch (mysqli_sql_exception $e) {
                $error = 'Unable to update class. The name may already exist.';
            }
        }
    }

    // DELETE CLASS
    elseif ($action === 'delete') {

        $id = (int)($_POST['class_id'] ?? 0);

        if ($id <= 0) {
            $error = 'Invalid class ID.';
        } else {

            // Check whether students are assigned to this class
            $check = $conn->prepare(
                "SELECT COUNT(*) AS total FROM students WHERE class_id = ?"
            );
            $check->bind_param('i', $id);
            $check->execute();

            $result = $check->get_result()->fetch_assoc();

            if ((int)$result['total'] > 0) {

                $error = 'You cannot delete this class because students are assigned to it.';

            } else {

                try {
                    $stmt = $conn->prepare(
                        "DELETE FROM classes WHERE class_id = ?"
                    );
                    $stmt->bind_param('i', $id);
                    $stmt->execute();

                    flash('Class deleted successfully.');
                    header('Location: classes.php');
                    exit;

                } catch (mysqli_sql_exception $e) {
                    $error = 'Unable to delete this class.';
                }
            }
        }
    }
}

// LOAD CLASS FOR EDITING
if (isset($_GET['edit'])) {

    $id = (int)$_GET['edit'];

    $stmt = $conn->prepare(
        "SELECT class_id, class_name FROM classes WHERE class_id = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();

    $edit = $stmt->get_result()->fetch_assoc();

    if (!$edit) {
        $error = 'Class not found.';
    }
}

// DISPLAY ALL CLASSES AND NUMBER OF STUDENTS
$rows = $conn->query("
    SELECT
        c.class_id,
        c.class_name,
        COUNT(s.student_id) AS student_count
    FROM classes c
    LEFT JOIN students s ON s.class_id = c.class_id
    GROUP BY c.class_id, c.class_name
    ORDER BY c.class_id ASC
")->fetch_all(MYSQLI_ASSOC);

$page_title = 'Class Management';

require __DIR__ . '/includes/header.php';
?>

<h1 class="h2 mb-3">Class Management</h1>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <?= h($error) ?>
    </div>
<?php endif; ?>

<!-- CLASS FORM -->
<div class="card mb-4">
    <div class="card-body">

        <h5 class="card-title mb-3">
            <?= $edit ? 'Update Class' : 'Register New Class' ?>
        </h5>

        <form method="POST" action="classes.php" class="row g-3">

            <input type="hidden" name="csrf_token"
                   value="<?= h(csrf_token()) ?>">

            <input type="hidden" name="action"
                   value="<?= $edit ? 'update' : 'create' ?>">

            <?php if ($edit): ?>
                <input type="hidden" name="class_id"
                       value="<?= (int)$edit['class_id'] ?>">
            <?php endif; ?>

            <div class="col-md-8">

                <label class="form-label">Class Name</label>

                <input
                    type="text"
                    name="class_name"
                    class="form-control"
                    placeholder="Enter class name"
                    value="<?= h($edit['class_name'] ?? '') ?>"
                    required
                >

            </div>

            <div class="col-md-4 align-self-end">

                <button type="submit" class="btn btn-primary">
                    <?= $edit ? 'Update Class' : 'Register Class' ?>
                </button>

                <?php if ($edit): ?>
                    <a href="classes.php" class="btn btn-secondary">
                        Cancel
                    </a>
                <?php endif; ?>

            </div>

        </form>

    </div>
</div>

<!-- CLASS TABLE -->
<div class="card">
    <div class="card-body">

        <h5 class="card-title mb-3">All Classes</h5>

        <div class="table-responsive">

            <table class="table table-bordered table-striped">

                <thead class="table-dark">
                    <tr>
                        <th>Class ID</th>
                        <th>Class Name</th>
                        <th>Students Assigned</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($rows): ?>

                    <?php foreach ($rows as $r): ?>

                        <tr>

                            <td><?= (int)$r['class_id'] ?></td>

                            <td><?= h($r['class_name']) ?></td>

                            <td>
                                <span class="badge bg-info">
                                    <?= (int)$r['student_count'] ?>
                                </span>
                            </td>

                            <td class="text-nowrap">

                                <!-- UPDATE BUTTON -->
                                <a
                                    href="classes.php?edit=<?= (int)$r['class_id'] ?>"
                                    class="btn btn-sm btn-primary">
                                    Update
                                </a>

                                <!-- DELETE FORM -->
                                <form method="POST"
                                      action="classes.php"
                                      class="d-inline"
                                      onsubmit="return confirm('Are you sure you want to delete this class?');">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= h(csrf_token()) ?>">

                                    <input type="hidden" name="action"
                                           value="delete">

                                    <input type="hidden" name="class_id"
                                           value="<?= (int)$r['class_id'] ?>">

                                    <button type="submit"
                                            class="btn btn-sm btn-danger">
                                        Delete
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4" class="text-center">
                            No classes registered yet.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

        <p class="text-muted small mb-0">
            Assign students to classes from the Students page.
        </p>

    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
