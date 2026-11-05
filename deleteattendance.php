
<?php
$con=mysqli_connect("localhost","root","","lol_sms");
$id=$_GET['date'];
$sql=mysqli_query($con,"Delete from attendance Where admission_number='$id'");
if ($sql) {
    header("Location: attendance.php");
}

?>