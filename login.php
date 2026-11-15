<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db_connect.php';
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "⚠️ กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
    } else {
        $stmt = $pdo->prepare("SELECT user_id, password_hash, role, full_name FROM users WHERE username = :uname LIMIT 1");
        $stmt->bindValue(':uname', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$error = "⚠️ กรุณากรอกชื่อผู้ใช้และรหัสผ่าน";
		
        if ($user && password_verify($password, $user['password_hash'])) {
			// ✅ กำหนดค่า Session ก่อน
			$_SESSION['user_id'] = $user['user_id'];
			$_SESSION['full_name'] = $user['full_name'];
			$_SESSION['role'] = $user['role'];

			// ✅ ล้างบัฟเฟอร์ก่อนส่ง header
			if (ob_get_level()) ob_end_clean();

			// ✅ กำหนดปลายทางตาม role
			$redirect_url = '';
			if ($user['role'] === 'admin') {
				$redirect_url = 'admin_setting.php';
			} elseif ($user['role'] === 'guard') {
				$redirect_url = 'screen.php';
			} else {
        $redirect_url = 'index.php';
    }
    // ✅ สั่งเปลี่ยนหน้า + exit ทันที
    header("Location: " . $redirect_url);
    exit;
} else {
    $error = "⚠️ ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
}

    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>เข้าสู่ระบบ - Factory Exit Screening</title>
<!-- Fonts & Icons -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
body {
    font-family: 'Sarabun', sans-serif;
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

/* Login Card */
.login-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    width: 100%;
    max-width: 420px;
    padding: 45px 35px;
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(25px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Logo / Icon */
.logo-section {
    text-align: center;
    margin-bottom: 35px;
}
.logo-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
}
.logo-icon i {
    font-size: 38px;
    color: white;
}
.logo-section h1 {
    font-size: 24px;
    color: #1e293b;
    font-weight: 600;
    margin-bottom: 5px;
}
.logo-section p {
    font-size: 15px;
    color: #64748b;
}

/* Error Message */
.error-box {
    background: #fef2f2;
    border-left: 4px solid #ef4444;
    color: #991b1b;
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 25px;
    font-size: 15px;
}

/* Form */
.form-group {
    margin-bottom: 22px;
}
.form-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 15px;
    font-weight: 500;
    color: #374151;
}
.input-wrapper {
    position: relative;
}
.input-wrapper i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 18px;
}
.input-wrapper input {
    width: 100%;
    padding: 14px 15px 14px 48px;
    font-size: 16px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-family: 'Sarabun', sans-serif;
    transition: all 0.3s ease;
    background: #fafafa;
}
.input-wrapper input:focus {
    outline: none;
    border-color: #2563eb;
    background: white;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
}

/* Button */
.btn-login {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 17px;
    font-weight: 600;
    font-family: 'Sarabun', sans-serif;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 8px;
    box-shadow: 0 6px 18px rgba(37, 99, 235, 0.3);
}
.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(37, 99, 235, 0.35);
}
.btn-login:active {
    transform: translateY(0);
}

/* Footer */
.footer-text {
    text-align: center;
    margin-top: 30px;
    font-size: 14px;
    color: #94a3b8;
}
</style>
</head>
<body>

<div class="login-card">
    <div class="logo-section">
        <div class="logo-icon">
            <i class="fa-solid fa-factory"></i>
        </div>
        <h1>Factory Exit</h1>
        <p>ระบบตรวจคัดกรองพนักงาน</p>
    </div>

    <?php if ($error): ?>
    <div class="error-box"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="username">ชื่อผู้ใช้</label>
            <div class="input-wrapper">
                <i class="fa-solid fa-user"></i>
                <input type="text" id="username" name="username" placeholder="กรอกชื่อผู้ใช้" required autocomplete="username">
            </div>
        </div>

        <div class="form-group">
            <label for="password">รหัสผ่าน</label>
            <div class="input-wrapper">
                <i class="fa-solid fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="กรอกรหัสผ่าน" required autocomplete="current-password">
            </div>
        </div>

        <button type="submit" class="btn-login">
            <i class="fa-solid fa-right-to-bracket"></i> เข้าสู่ระบบ
        </button>
    </form>

    <div class="footer-text">
        © 2025 Factory Exit Screening System
    </div>
</div>

</body>
</html>
