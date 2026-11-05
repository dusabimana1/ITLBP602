
<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/Amina/database.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';
$message = '';
$edit_grade = null;

try {

    // DELETE GRADE
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        verify_csrf();

        $action = $_POST['action'] ?? '';

        if ($action === 'delete') {

            $grade_id = (int)($_POST['grade_id'] ?? 0);

            if ($grade_id <= 0) {
                throw new Exception("Invalid grade ID.");
            }

            $stmt = $conn->prepare(
                "DELETE FROM grades WHERE grade_id = ?"
            );

            $stmt->bind_param("i", $grade_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $_SESSION['grade_message'] = "Grade deleted successfully.";
            } else {
                $_SESSION['grade_message'] = "Grade not found.";
            }

            header("Location: grades.php");
            exit;
        }

        // INSERT OR UPDATE
        if ($action === 'save' || $action === 'update') {

            $student_id = (int)($_POST['student_id'] ?? 0);
            $subject_id = (int)($_POST['subject_id'] ?? 0);
            $assessment_type = trim($_POST['assessment_type'] ?? '');
            $marks = $_POST['marks'] ?? '';
            $academic_year = trim($_POST['academic_year'] ?? '');

            if (
                $student_id <= 0 ||
                $subject_id <= 0 ||
                $assessment_type === '' ||
                !is_numeric($marks) ||
                $marks < 0 ||
                $marks > 100 ||
                $academic_year === ''
            ) {
                throw new Exception(
                    "Please fill in all fields correctly. Marks must be between 0 and 100."
                );
            }

            $marks = (float)$marks;

            // CHECK STUDENT
            $check = $conn->prepare(
                "SELECT student_id FROM students WHERE student_id = ?"
            );
            $check->bind_param("i", $student_id);
            $check->execute();

            if (!$check->get_result()->fetch_assoc()) {
                throw new Exception("Selected student does not exist.");
            }

            // CHECK SUBJECT
            $check = $conn->prepare(
                "SELECT subject_id FROM subjects WHERE subject_id = ?"
            );
            $check->bind_param("i", $subject_id);
            $check->execute();

            if (!$check->get_result()->fetch_assoc()) {
                throw new Exception("Selected subject does not exist.");
            }

            // UPDATE
            if ($action === 'update') {

                $grade_id = (int)($_POST['grade_id'] ?? 0);

                if ($grade_id <= 0) {
                    throw new Exception("Invalid grade ID.");
                }

                $stmt = $conn->prepare(
                    "UPDATE grades
                     SET student_id = ?,
                         subject_id = ?,
                         assessment_type = ?,
                         marks = ?,
                         academic_year = ?
                     WHERE grade_id = ?"
                );

                $stmt->bind_param(
                    "iisdsi",
                    $student_id,
                    $subject_id,
                    $assessment_type,
                    $marks,
                    $academic_year,
                    $grade_id
                );

                $stmt->execute();

                $_SESSION['grade_message'] = "Grade updated successfully.";

                header("Location: grades.php");
                exit;
            }

            // SAVE
            if ($action === 'save') {

                $stmt = $conn->prepare(
                    "INSERT INTO grades
                    (student_id, subject_id, assessment_type, marks, academic_year)
                    VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "iisds",
                    $student_id,
                    $subject_id,
                    $assessment_type,
                    $marks,
                    $academic_year
                );

                $stmt->execute();

                $_SESSION['grade_message'] = "Grade saved successfully.";

                header("Location: grades.php");
                exit;
            }
        }
    }

    // GET GRADE FOR EDITING
    if (isset($_GET['edit'])) {

        $id = (int)$_GET['edit'];

        $stmt = $conn->prepare(
            "SELECT * FROM grades WHERE grade_id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $edit_grade = $stmt->get_result()->fetch_assoc();

        if (!$edit_grade) {
            throw new Exception("Grade not found.");
        }
    }

    // GET STUDENTS
    $students = $conn->query(
        "SELECT student_id, admission_number, names
         FROM students ORDER BY names"
    )->fetch_all(MYSQLI_ASSOC);

    // GET SUBJECTS
    $subjects = $conn->query(
        "SELECT subject_id, subject_name
         FROM subjects ORDER BY subject_name"
    )->fetch_all(MYSQLI_ASSOC);

    // GET GRADES
    $grades = $conn->query(
        "SELECT g.grade_id,
                g.student_id,
                g.subject_id,
                g.assessment_type,
                g.marks,
                g.academic_year,
                s.admission_number,
                s.names,
                sub.subject_name
         FROM grades g
         INNER JOIN students s
            ON s.student_id = g.student_id
         INNER JOIN subjects sub
            ON sub.subject_id = g.subject_id
         ORDER BY g.grade_id DESC"
    )->fetch_all(MYSQLI_ASSOC);

} catch (Throwable $e) {

    $error = $e->getMessage();

    if (!isset($students)) {
        $students = [];
    }

    if (!isset($subjects)) {
        $subjects = [];
    }

    if (!isset($grades)) {
        $grades = [];
    }
}

// DISPLAY MESSAGE
if (isset($_SESSION['grade_message'])) {
    $message = $_SESSION['grade_message'];
    unset($_SESSION['grade_message']);
}

$page_title = 'Grades';
require __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">

    <h1 class="h2 mb-4">Grade Management</h1>

    <?php if ($message): ?>
        <div class="alert alert-success">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <!-- ADD OR UPDATE FORM -->
    <div class="card mb-4">

        <div class="card-header">
            <h5>
                <?= $edit_grade ? 'Update Grade' : 'Add New Grade' ?>
            </h5>
        </div>

        <div class="card-body">

            <form method="POST" action="grades.php" class="row g-3">

                <input type="hidden" name="csrf_token"
                       value="<?= h(csrf_token()) ?>">

                <?php if ($edit_grade): ?>

                    <input type="hidden" name="action" value="update">

                    <input type="hidden" name="grade_id"
                           value="<?= (int)$edit_grade['grade_id'] ?>">

                <?php else: ?>

                    <input type="hidden" name="action" value="save">

                <?php endif; ?>

                <div class="col-md-4">
                    <label class="form-label">Student</label>

                    <select name="student_id" class="form-select" required>

                        <option value="">Select Student</option>

                        <?php foreach ($students as $student): ?>

                            <option value="<?= (int)$student['student_id'] ?>"
                                <?= ($edit_grade &&
                                $edit_grade['student_id'] == $student['student_id'])
                                ? 'selected' : '' ?>>

                                <?= h($student['admission_number'] . ' - ' . $student['names']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Subject</label>

                    <select name="subject_id" class="form-select" required>

                        <option value="">Select Subject</option>

                        <?php foreach ($subjects as $subject): ?>

                            <option value="<?= (int)$subject['subject_id'] ?>"
                                <?= ($edit_grade &&
                                $edit_grade['subject_id'] == $subject['subject_id'])
                                ? 'selected' : '' ?>>

                                <?= h($subject['subject_name']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Assessment Type</label>

                    <select name="assessment_type" class="form-select" required>

                        <option value="">Select Type</option>

                        <option value="Test"
                            <?= (($edit_grade['assessment_type'] ?? '') === 'Test') ? 'selected' : '' ?>>
                            Test
                        </option>

                        <option value="Exam"
                            <?= (($edit_grade['assessment_type'] ?? '') === 'Exam') ? 'selected' : '' ?>>
                            Exam
                        </option>

                        <option value="Assignment"
                            <?= (($edit_grade['assessment_type'] ?? '') === 'Assignment') ? 'selected' : '' ?>>
                            Assignment
                        </option>

                    </select>
                </div>

                <div class="col-md-1">
                    <label class="form-label">Marks</label>

                    <input type="number"
                           name="marks"
                           class="form-control"
                           min="0"
                           max="100"
                           step="0.01"
                           value="<?= h($edit_grade['marks'] ?? '') ?>"
                           required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Academic Year</label>

                    <input type="text"
                           name="academic_year"
                           class="form-control"
                           placeholder="2026"
                           value="<?= h($edit_grade['academic_year'] ?? '') ?>"
                           required>
                </div>

                <div class="col-12">

                    <?php if ($edit_grade): ?>

                        <button type="submit" class="btn btn-success">
                            Update Grade
                        </button>

                        <a href="grades.php" class="btn btn-secondary">
                            Cancel
                        </a>

                    <?php else: ?>

                        <button type="submit" class="btn btn-primary"
                            <?= (empty($students) || empty($subjects)) ? 'disabled' : '' ?>>
                            Save Grade
                        </button>

                    <?php endif; ?>

                </div>

            </form>

        </div>
    </div>

    <!-- DISPLAY GRADES -->
    <div class="card">

        <div class="card-header">
            <h5>All Student Grades</h5>
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-striped table-hover">

                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Admission No.</th>
                            <th>Student</th>
                            <th>Subject</th>
                            <th>Assessment</th>
                            <th>Marks</th>
                            <th>Academic Year</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (!empty($grades)): ?>

                        <?php $number = 1; ?>

                        <?php foreach ($grades as $grade): ?>

                            <tr>

                                <td><?= $number++ ?></td>

                                <td><?= h($grade['admission_number']) ?></td>

                                <td><?= h($grade['names']) ?></td>

                                <td><?= h($grade['subject_name']) ?></td>

                                <td><?= h($grade['assessment_type']) ?></td>

                                <td><?= h($grade['marks']) ?>/100</td>

                                <td><?= h($grade['academic_year']) ?></td>

                                <td>

                                    <a href="grades.php?edit=<?= (int)$grade['grade_id'] ?>"
                                       class="btn btn-success btn-sm">
                                        Edit
                                    </a>

                                    <form method="POST"
                                          action="grades.php"
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this grade?');">

                                        <input type="hidden" name="csrf_token"
                                               value="<?= h(csrf_token()) ?>">

                                        <input type="hidden" name="action"
                                               value="delete">

                                        <input type="hidden" name="grade_id"
                                               value="<?= (int)$grade['grade_id'] ?>">

                                        <button type="submit"
                                                class="btn btn-danger btn-sm">
                                            Delete
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="text-center">
                                No grades recorded yet.
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
