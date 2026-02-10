<?php
session_start();
$conn = new mysqli("localhost","root","","resolution_db");
if($conn->connect_error) die("DB Error");
?>
