
<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/Amina/database.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';
$message = '';

// Validate date
function validAttendanceDate($date) {
    if (!is_string($date) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }

    $parts = explode('-', $date);

    return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
}

$date = $_GET['date'] ?? date('Y-m-d');

if (!validAttendanceDate($date)) {
    $date = date('Y-m-d');
}

// Display message
if (isset($_SESSION['attendance_message'])) {
    $message = $_SESSION['attendance_message'];
    unset($_SESSION['attendance_message']);
}

// HANDLE POST ACTIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        verify_csrf();

        $action = $_POST['action'] ?? '';
        $date = $_POST['attendance_date'] ?? date('Y-m-d');

        if (!validAttendanceDate($date)) {
            throw new Exception('Invalid attendance date.');
        }

        // DELETE ATTENDANCE
        if ($action === 'delete') {

            $attendance_id = (int)($_POST['attendance_id'] ?? 0);

            if ($attendance_id <= 0) {
                throw new Exception('Invalid attendance ID.');
            }

            $stmt = $conn->prepare(
                "DELETE FROM attendance
                 WHERE attendance_id = ?
                 AND attendance_date = ?"
            );

            $stmt->bind_param(
                "is",
                $attendance_id,
                $date
            );

            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $_SESSION['attendance_message'] =
                    'Attendance deleted successfully.';
            } else {
                $_SESSION['attendance_message'] =
                    'Attendance not found.';
            }

            header('Location: attendance.php?date=' . urlencode($date));
            exit;
        }

        // SAVE OR UPDATE ATTENDANCE
        if ($action === 'save') {

            $student_id = (int)($_POST['student_id'] ?? 0);
            $status = $_POST['attendance_status'] ?? '';

            $allowed_status = [
                'Present',
                'Absent',
                'Late',
                'Excused'
            ];

            if (
                $student_id <= 0 ||
                !in_array($status, $allowed_status, true)
            ) {
                throw new Exception('Please select a valid student and attendance status.');
            }

            // Check student exists
            $check = $conn->prepare(
                "SELECT student_id FROM students WHERE student_id = ?"
            );

            $check->bind_param("i", $student_id);
            $check->execute();

            if (!$check->get_result()->fetch_assoc()) {
                throw new Exception('Student does not exist.');
            }

            // Check if attendance already exists for this student and date
            $check = $conn->prepare(
                "SELECT attendance_id
                 FROM attendance
                 WHERE student_id = ?
                 AND attendance_date = ?
                 LIMIT 1"
            );

            $check->bind_param("is", $student_id, $date);
            $check->execute();

            $existing = $check->get_result()->fetch_assoc();

            if ($existing) {

                // UPDATE EXISTING ATTENDANCE
                $attendance_id = (int)$existing['attendance_id'];

                $stmt = $conn->prepare(
                    "UPDATE attendance
                     SET attendance_status = ?
                     WHERE attendance_id = ?
                     AND student_id = ?
                     AND attendance_date = ?"
                );

                $stmt->bind_param(
                    "siis",
                    $status,
                    $attendance_id,
                    $student_id,
                    $date
                );

                $stmt->execute();

                $_SESSION['attendance_message'] =
                    'Attendance updated successfully.';

            } else {

                // INSERT NEW ATTENDANCE
                $stmt = $conn->prepare(
                    "INSERT INTO attendance
                    (student_id, attendance_date, attendance_status)
                    VALUES (?, ?, ?)"
                );

                $stmt->bind_param(
                    "iss",
                    $student_id,
                    $date,
                    $status
                );

                $stmt->execute();

                $_SESSION['attendance_message'] =
                    'Attendance saved successfully.';
            }

            header('Location: attendance.php?date=' . urlencode($date));
            exit;
        }

    } catch (Throwable $e) {

        $error = $e->getMessage();
    }
}

// GET STUDENTS AND ATTENDANCE
try {

    $stmt = $conn->prepare(
        "SELECT
            s.student_id,
            s.admission_number,
            s.names,
            a.attendance_id,
            a.attendance_status
         FROM students s
         LEFT JOIN attendance a
            ON a.student_id = s.student_id
            AND a.attendance_date = ?
         ORDER BY s.names ASC"
    );

    $stmt->bind_param("s", $date);
    $stmt->execute();

    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Throwable $e) {

    $rows = [];
    $error = $e->getMessage();
}

$page_title = 'Attendance';

require __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">

    <h1 class="h2 mb-3">Attendance Management</h1>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= h($message) ?>
            <button type="button" class="btn-close"
                    data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <!-- SELECT DATE -->
    <div class="card mb-4">

        <div class="card-body">

            <form method="GET" action="attendance.php"
                  class="row g-3 align-items-end">

                <div class="col-md-4">

                    <label class="form-label">
                        Select Attendance Date
                    </label>

                    <input
                        type="date"
                        name="date"
                        class="form-control"
                        value="<?= h($date) ?>"
                        required
                    >

                </div>

                <div class="col-md-3">

                    <button type="submit"
                            class="btn btn-outline-primary">
                        Load Attendance
                    </button>

                </div>

            </form>

        </div>
    </div>

    <!-- ATTENDANCE TABLE -->
    <div class="card">

        <div class="card-body">

            <h5 class="card-title mb-3">
                Student Attendance - <?= h($date) ?>
            </h5>

            <div class="table-responsive">

                <table class="table table-bordered table-striped table-hover">

                    <thead class="table-dark">

                        <tr>
                            <th>No</th>
                            <th>Attendance ID</th>
                            <th>Admission No.</th>
                            <th>Student Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (!empty($rows)): ?>

                        <?php $number = 1; ?>

                        <?php foreach ($rows as $z): ?>

                            <?php
                            $form_id = 'attendance_form_' . (int)$z['student_id'];
                            ?>

                            <tr>

                                <td><?= $number++ ?></td>

                                <td>
                                    <?= $z['attendance_id']
                                        ? (int)$z['attendance_id']
                                        : 'Not recorded' ?>
                                </td>

                                <td>
                                    <?= h($z['admission_number']) ?>
                                </td>

                                <td>
                                    <?= h($z['names']) ?>
                                </td>

                                <td>

                                    <!-- STATUS SELECT -->
                                    <select
                                        name="attendance_status"
                                        class="form-select"
                                        form="<?= h($form_id) ?>"
                                        required
                                    >

                                        <option value="">
                                            Select Status
                                        </option>

                                        <?php
                                        $statuses = [
                                            'Present',
                                            'Absent',
                                            'Late',
                                            'Excused'
                                        ];
                                        ?>

                                        <?php foreach ($statuses as $st): ?>

                                            <option
                                                value="<?= h($st) ?>"
                                                <?= ($z['attendance_status'] ?? '') === $st
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                <?= h($st) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                </td>

                                <td class="text-nowrap">

                                    <!-- SAVE / UPDATE FORM -->
                                    <form
                                        id="<?= h($form_id) ?>"
                                        method="POST"
                                        action="attendance.php"
                                        class="d-inline"
                                    >

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= h(csrf_token()) ?>">

                                        <input type="hidden"
                                               name="action"
                                               value="save">

                                        <input type="hidden"
                                               name="student_id"
                                               value="<?= (int)$z['student_id'] ?>">

                                        <input type="hidden"
                                               name="attendance_date"
                                               value="<?= h($date) ?>">

                                    </form>

                                    <button
                                        type="submit"
                                        form="<?= h($form_id) ?>"
                                        class="btn btn-primary btn-sm"
                                    >
                                        <?= $z['attendance_id']
                                            ? 'Update'
                                            : 'Save' ?>
                                    </button>

                                    <!-- DELETE FORM -->
                                    <?php if ($z['attendance_id']): ?>

                                        <form
                                            method="POST"
                                            action="attendance.php"
                                            class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this attendance?');"
                                        >

                                            <input type="hidden"
                                                   name="csrf_token"
                                                   value="<?= h(csrf_token()) ?>">

                                            <input type="hidden"
                                                   name="action"
                                                   value="delete">

                                            <input type="hidden"
                                                   name="attendance_id"
                                                   value="<?= (int)$z['attendance_id'] ?>">

                                            <input type="hidden"
                                                   name="attendance_date"
                                                   value="<?= h($date) ?>">

                                            <button type="submit"
                                                    class="btn btn-danger btn-sm">
                                                Delete
                                            </button>

                                        </form>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="text-center">
                                No students found. Please register students first.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>
    </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
