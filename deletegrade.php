
<?php
$con=mysqli_connect("localhost","root","","lol_sms");
$id=$_GET['a'];
$sql=mysqli_query($con,"Delete from grades Where grade_id='$id'");
if ($sql) {
    header("Location: grade.php");
}

?>