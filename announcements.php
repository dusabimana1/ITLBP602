
<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/Amina/database.php';
require_once __DIR__ . '/includes/functions.php';

verify_csrf();

$error = '';
$edit = null;

/* CHECK ADMIN */
$isAdmin = (($_SESSION['role'] ?? '') === 'admin');

/* PUBLISH, UPDATE AND DELETE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$isAdmin) {
        $error = 'Only administrators can manage announcements.';
    } else {

        $action = $_POST['action'] ?? '';

        /* PUBLISH OR UPDATE */
        if ($action === 'save') {

            $id = (int)($_POST['announcement_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $message = trim($_POST['message'] ?? '');

            if ($title === '' || $message === '') {
                $error = 'Enter both a title and a message.';
            } else {

                if ($id > 0) {

                    // UPDATE ANNOUNCEMENT
                    $stmt = $conn->prepare(
                        'UPDATE announcements
                         SET title = ?, message = ?
                         WHERE announcement_id = ?'
                    );

                    $stmt->bind_param('ssi', $title, $message, $id);
                    $stmt->execute();
                    $stmt->close();

                    flash('Announcement updated successfully.');

                } else {

                    // INSERT ANNOUNCEMENT
                    $stmt = $conn->prepare(
                        'INSERT INTO announcements (title, message)
                         VALUES (?, ?)'
                    );

                    $stmt->bind_param('ss', $title, $message);
                    $stmt->execute();
                    $stmt->close();

                    flash('Announcement published successfully.');
                }

                header('Location: announcements.php');
                exit;
            }
        }

        /* DELETE ANNOUNCEMENT */
        if ($action === 'delete') {

            $id = (int)($_POST['announcement_id'] ?? 0);

            $stmt = $conn->prepare(
                'DELETE FROM announcements
                 WHERE announcement_id = ?'
            );

            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            flash('Announcement deleted successfully.');

            header('Location: announcements.php');
            exit;
        }
    }
}

/* GET ANNOUNCEMENT FOR EDITING */
if (isset($_GET['edit']) && $isAdmin) {

    $id = (int)$_GET['edit'];

    $stmt = $conn->prepare(
        'SELECT announcement_id, title, message
         FROM announcements
         WHERE announcement_id = ?'
    );

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $edit = $stmt->get_result()->fetch_assoc();

    $stmt->close();
}

/* GET ALL ANNOUNCEMENTS */
$rows = $conn->query(
    'SELECT announcement_id, title, message
     FROM announcements
     ORDER BY announcement_id DESC'
)->fetch_all(MYSQLI_ASSOC);

$page_title = 'Announcements';

require __DIR__ . '/includes/header.php';
?>

<h1 class="h2 mb-3">School Announcements</h1>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <?= h($error) ?>
    </div>
<?php endif; ?>


<!-- PUBLISH / EDIT FORM -->

<?php if ($isAdmin): ?>

<div class="card mb-4">

    <div class="card-body">

        <h2 class="h5">
            <?= $edit ? 'Edit Announcement' : 'Publish Announcement' ?>
        </h2>

        <form method="post">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= h(csrf_token()) ?>">

            <input type="hidden"
                   name="action"
                   value="save">

            <input type="hidden"
                   name="announcement_id"
                   value="<?= h($edit['announcement_id'] ?? 0) ?>">

            <label class="form-label">Title</label>

            <input
                type="text"
                class="form-control mb-3"
                name="title"
                maxlength="150"
                value="<?= h($edit['title'] ?? '') ?>"
                placeholder="Enter announcement title"
                required
            >

            <label class="form-label">Message</label>

            <textarea
                class="form-control mb-3"
                name="message"
                rows="5"
                placeholder="Write your announcement here..."
                required
            ><?= h($edit['message'] ?? '') ?></textarea>

            <button type="submit" class="btn btn-primary">
                <?= $edit ? 'Update Announcement' : 'Publish Announcement' ?>
            </button>

            <?php if ($edit): ?>
                <a href="announcements.php"
                   class="btn btn-secondary">
                    Cancel
                </a>
            <?php endif; ?>

        </form>

    </div>

</div>

<?php endif; ?>


<!-- DISPLAY ANNOUNCEMENTS -->

<div class="row g-3">

<?php foreach ($rows as $r): ?>

    <div class="col-12">

        <article class="card">

            <div class="card-body">

                <h2 class="h5">
                    <?= h($r['title']) ?>
                </h2>

                <p class="announcement-message">
                    <?= nl2br(h($r['message'])) ?>
                </p>

                <small class="text-muted">
                    Announcement ID: <?= h($r['announcement_id']) ?>
                </small>

                <?php if ($isAdmin): ?>

                    <div class="mt-3">

                        <!-- EDIT BUTTON -->

                        <a
                            href="announcements.php?edit=<?= h($r['announcement_id']) ?>"
                            class="btn btn-warning btn-sm">
                            Edit
                        </a>

                        <!-- DELETE BUTTON -->

                        <form method="post"
                              class="d-inline"
                              onsubmit="return confirm('Are you sure you want to delete this announcement?');">

                            <input type="hidden"
                                   name="csrf_token"
                                   value="<?= h(csrf_token()) ?>">

                            <input type="hidden"
                                   name="action"
                                   value="delete">

                            <input type="hidden"
                                   name="announcement_id"
                                   value="<?= h($r['announcement_id']) ?>">

                            <button type="submit"
                                    class="btn btn-danger btn-sm">
                                Delete
                            </button>

                        </form>

                    </div>

                <?php endif; ?>

            </div>

        </article>

    </div>

<?php endforeach; ?>


<?php if (!$rows): ?>

    <p class="text-muted">
        No announcements have been published.
    </p>

<?php endif; ?>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
