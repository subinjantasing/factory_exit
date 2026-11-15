<?php
session_start();

// เคลียร์ค่า session ทั้งหมด
$_SESSION = [];

// ทำลาย session
session_destroy();

// กลับไปหน้าเข้าสู่ระบบ
header("Location: login.php");
exit;
?>
