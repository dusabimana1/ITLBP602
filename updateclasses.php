
<?php
include "config/database.php";



$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM classes WHERE class_id = ?");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$class = $result->fetch_assoc();


if (isset($_POST['update'])) {

    $class_name = $_POST['class_name'];

    $sql = "UPDATE classes
            SET class_name = ?
            WHERE class_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ssi",
        $class_name,
        $academic_year,
        $id
    );

    if ($stmt->execute()) {
        header("Location: classes.php");
        exit;
    } else {
        echo "Error updating class: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>

    <title>Update Class</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

    <div class="card shadow p-4">

        <h3 class="mb-4">Update Class Information</h3>

        <form method="POST">

          
            <label class="form-label">Class Name</label>

            <input type="text"
                   name="class_name"
                   class="form-control mb-3"
                   value="<?= h($edit['class_name'] ?? '') 
            ?>" required>
        </div> 

            <button type="submit"
                    name="update"
                    class="btn btn-primary">
                Update Class
            </button>

            <a href="classes.php"
               class="btn btn-secondary">
                Back
            </a>

        </form>

    </div>

</div>

</body>
</html>
