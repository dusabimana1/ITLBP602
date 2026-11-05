
<?php
$con=mysqli_connect("localhost","root","","lol_sms");
$id=$_GET['a'];
$sql=mysqli_query($con,"Delete from classes Where class_id='$id'");
if ($sql) {
    header("Location: classes.php");
}

?>