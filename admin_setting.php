<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db_connect.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetCount = (int)$_POST['target_count'];
    $selectCount = (int)$_POST['select_count'];
    $description = trim($_POST['description']);
    
    if ($targetCount > 0 && $selectCount > 0 && $selectCount <= $targetCount) {
        $pdo->query("UPDATE screening_settings SET is_active = 0 WHERE is_active = 1");
        $stmt = $pdo->prepare("INSERT INTO screening_settings(setting_name, target_count, select_count, description) VALUES (:name,:tc,:sc,:desc)");
        $stmt->bindValue(':name', "RULE_".date("YmdHis"));
        $stmt->bindValue(':tc', $targetCount);
        $stmt->bindValue(':sc', $selectCount);
        $stmt->bindValue(':desc', $description);
        $stmt->execute();
        $message = "✅ บันทึกสำเร็จ: ทุก $targetCount คน ตรวจ $selectCount คน";
    } else {
        $message = "❌ ค่าต้องมากกว่า 0 และ จำนวนตรวจ ≤ จำนวนทั้งหมดในรอบ";
    }
}

$stmt = $pdo->query("SELECT target_count, select_count, description, updated_at FROM screening_settings WHERE is_active = 1 LIMIT 1");
$current = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>


<head>
<meta charset="UTF-8">
<!-- โหลดฟอนต์ Sarabun -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Sarabun', 'Sarabun New', sans-serif;
    font-size: 16px;
    background: #f5f5f5;
    padding: 20px;
}
.container {
    max-width: 600px;
    margin: 0 auto;
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
h2 {
    text-align: center;
    color: #333;
    margin-bottom: 30px;
    font-family: 'Sarabun', sans-serif;
}
.form-group {
    margin-bottom: 20px;
}
label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #444;
    font-family: 'Sarabun', sans-serif;
}
input, textarea, button {
    width: 100%;
    padding: 10px 12px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-family: 'Sarabun', sans-serif;
}
button {
    background: #2563eb;
    color: white;
    border: none;
    font-weight: 600;
    cursor: pointer;
    margin-top: 10px;
    transition: background 0.2s;
}
button:hover {
    background: #1d4ed8;
}
.success-msg {
    background: #dcfce7;
    color: #166534;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
    text-align: center;
}
.current-rule {
    background: #fef3c7;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 25px;
    border-left: 4px solid #f59e0b;
}
.current-rule strong {
    font-family: 'Sarabun', sans-serif;
}
</style>
</head>


<body>
<div class="container">
<h2>🔐 ตั้งค่าการสุ่มตรวจ</h2>
<!--
<p>👤 ผู้ใช้ : <?php echo htmlspecialchars($_SESSION['full_name']); ?>
   | <a href="admin_report.php">📊 ดูรายงาน</a>
   | <a href="logout.php">🚪 ออกจากระบบ</a>
</p> -->


<!-- เพิ่มลิงก์ Font Awesome ที่บนสุดก่อน </head> -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css">

<!-- ส่วนแถบเมนูด้านบน -->
<div style="background:#1e293b; padding:12px 24px; display:flex; justify-content:space-between; align-items:center; border-radius:8px; margin-bottom:20px;">
    <!-- ซ้าย -->
    <div style="display:flex; align-items:center; gap:12px; color:white; font-family:'Sarabun', sans-serif; font-size:17px;">
        <i class="fa-solid fa-user-shield" style="font-size:24px; color:#60a5fa;"></i>
        <span>ผู้ใช้ : <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
    </div>

    <!-- ขวา -->
    <div style="display:flex; gap:16px; align-items:center;">
        <a href="admin_change_password.php" style="display:flex; align-items:center; gap:6px; color:#e2e8f0; text-decoration:none; padding:8px 14px; border-radius:6px; transition:background 0.2s; font-family:'Sarabun', sans-serif; font-size:16px;"
           onmouseover="this.style.background='#334155'" onmouseout="this.style.background='transparent'">
            <i class="fa-solid fa-key" style="font-size:18px;"></i>
            <span>รหัสผ่าน</span>
        </a>

        <a href="admin_report.php" style="display:flex; align-items:center; gap:6px; color:#e2e8f0; text-decoration:none; padding:8px 14px; border-radius:6px; transition:background 0.2s; font-family:'Sarabun', sans-serif; font-size:16px;"
           onmouseover="this.style.background='#334155'" onmouseout="this.style.background='transparent'">
            <i class="fa-solid fa-chart-bar" style="font-size:18px;"></i>
            <span>รายงาน</span>
        </a>

        <a href="logout.php" style="display:flex; align-items:center; gap:6px; color:#fecaca; text-decoration:none; padding:8px 14px; border-radius:6px; transition:background 0.2s; font-family:'Sarabun', sans-serif; font-size:16px;"
           onmouseover="this.style.background='#991b1b'" onmouseout="this.style.background='transparent'">
            <i class="fa-solid fa-right-from-bracket" style="font-size:18px;"></i>
            <span>Exit</span>
        </a>
    </div>
</div>


<div style="background:#e8f5e9;padding:15px;border-radius:8px;margin-bottom:20px;">
<h3 style="margin-top:0;">📋 กฎปัจจุบัน</h3>
<p style="font-size:24px;font-weight:bold;">ทุก <?php echo $current['target_count']; ?> คน → ตรวจ <?php echo $current['select_count']; ?> คน</p>
<p>หมายเหตุ: <?php echo htmlspecialchars($current['description']); ?></p>
<p>อัปเดตเมื่อ: <?php echo htmlspecialchars($current['updated_at']); ?></p>
</div>

<p style="color:green;"><?php echo $message; ?></p>

<form method="POST">
<label>จำนวนคนต่อรอบ (เช่น 20):</label>
<input type="number" name="target_count" required min="1" style="width:100%;padding:10px;font-size:18px;"><br><br>
<label>จำนวนคนที่จะตรวจในรอบนั้น (เช่น 1):</label>
<input type="number" name="select_count" required min="1" style="width:100%;padding:10px;font-size:18px;"><br><br>
<label>หมายเหตุ:</label>
<textarea name="description" rows="2" style="width:100%;padding:10px;font-size:18px;"></textarea><br><br>
<input type="submit" value="บันทึกกฎการตรวจ" style="padding:12px 24px;font-size:20px;">
</form>

<p style="margin-top:20px;color:#666;font-size:16px;">
💡 ตัวอย่าง:<br>
- 20 คน ตรวจ 1 คน = 5%<br>
- 30 คน ตรวจ 2 คน ≈ 6.67%<br>
- 10 คน ตรวจ 1 คน = 10%
</p>
</div>
</body>
</html>
