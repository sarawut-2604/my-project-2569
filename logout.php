<?php

session_start();

// ล้างค่า Session ทั้งหมด
$_SESSION = array();

// ทำลาย Session
session_destroy();

// ส่งกลับไปหน้า Login
header("Location: login.php");
exit();
?>