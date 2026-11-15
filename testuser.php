<?php
//file : testuser.php
echo "Hash สำหรับ admin123: " . password_hash("admin123", PASSWORD_DEFAULT) . "<br>";
echo "Hash สำหรับ guard123: " . password_hash("guard123", PASSWORD_DEFAULT);
?>