<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPass = $_POST['old_password'];
    $newPass = $_POST['new_password'];
    $confirmPass = $_POST['confirm_password'];

    if (empty($oldPass) || empty($newPass) || empty($confirmPass)) {
        $msg = "<div style='background:#fef2f2; color:#991b1b; padding:12px; border-radius:6px; margin-bottom:20px;'>⚠️ กรุณากรอกข้อมูลให้ครบถ้วน</div>";
    } elseif ($newPass !== $confirmPass) {
        $msg = "<div style='background:#fef2f2; color:#991b1b; padding:12px; border-radius:6px; margin-bottom:20px;'>⚠️ รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน</div>";
    } elseif (strlen($newPass) < 6) {
        $msg = "<div style='background:#fef2f2; color:#991b1b; padding:12px; border-radius:6px; margin-bottom:20px;'>⚠️ รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร</div>";
    } else {
        // ✅ ใช้ชื่อคอลัมน์ที่ถูกต้อง: password_hash
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = :uid");
        $stmt->bindValue(':uid', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($oldPass, $user['password_hash'])) {
            $msg = "<div style='background:#fef2f2; color:#991b1b; padding:12px; border-radius:6px; margin-bottom:20px;'>⚠️ รหัสผ่านเดิมไม่ถูกต้อง</div>";
        } else {
            // ✅ อัปเดตด้วยชื่อคอลัมน์ที่ถูกต้อง
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password_hash = :pass WHERE user_id = :uid");
            $update->bindValue(':pass', $hash);
            $update->bindValue(':uid', $_SESSION['user_id']);
            $update->execute();
            
            $msg = "<div style='background:#f0fdf4; color:#166534; padding:12px; border-radius:6px; margin-bottom:20px;'>✅ เปลี่ยนรหัสผ่านสำเร็จ</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Sarabun', sans-serif;
    background: #f8fafc;
    padding: 40px 20px;
}
.container {
    max-width: 480px;
    margin: 0 auto;
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
h2 {
    text-align: center;
    color: #1e293b;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}
h2 i { color: #2563eb; }
.form-group { margin-bottom: 20px; }
label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #374151;
}
input {
    width: 100%;
    padding: 12px 14px;
    font-size: 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-family: 'Sarabun', sans-serif;
    transition: border 0.2s;
}
input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
button {
    width: 100%;
    padding: 12px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    font-family: 'Sarabun', sans-serif;
    cursor: pointer;
    transition: background 0.2s;
}
button:hover { background: #1d4ed8; }
.back-link {
    display: block;
    text-align: center;
    margin-top: 20px;
    color: #6b7280;
    text-decoration: none;
}
.back-link:hover { color: #2563eb; }
</style>
</head>
<body>

<div class="container">
    <h2><i class="fa-solid fa-key"></i> เปลี่ยนรหัสผ่าน</h2>
    
    <?php echo $msg; ?>

    <form method="POST">
        <div class="form-group">
            <label for="old_password">รหัสผ่านเดิม</label>
            <input type="password" id="old_password" name="old_password" required>
        </div>
        <div class="form-group">
            <label for="new_password">รหัสผ่านใหม่ (อย่างน้อย 6 ตัวอักษร)</label>
            <input type="password" id="new_password" name="new_password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">ยืนยันรหัสผ่านใหม่</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit">บันทึกรหัสผ่านใหม่</button>
    </form>

    <a href="admin_setting.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> กลับหน้าตั้งค่า</a>
</div>

</body>
</html>
