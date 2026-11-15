<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db_connect.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guard') {
    header("Location: login.php");
    exit;
}

// โหลดกฎ
$stmt = $pdo->query("SELECT target_count, select_count FROM screening_settings WHERE is_active = 1 LIMIT 1");
$setting = $stmt->fetch(PDO::FETCH_ASSOC);
$targetCount = (int)$setting['target_count'];
$selectCount = (int)$setting['select_count'];

// โหลดจุดตรวจ
$stmtStation = $pdo->query("SELECT station_id FROM screening_stations WHERE is_active=1");
$stations = $stmtStation->fetchAll(PDO::FETCH_ASSOC);
$selectedStationId = isset($_POST['station_id']) ? (int)$_POST['station_id'] : $stations[0]['station_id'];

// เริ่มรอบใหม่
if (!isset($_SESSION['cycle_number'])) {
    $_SESSION['cycle_number'] = 1;
    $_SESSION['cycle_counter'] = 0;
    $_SESSION['picked_numbers'] = [];
}

$displayResult = "กดปุ่มใดๆ เพื่อเริ่ม";
$displayColor = "green";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trigger_screen'])) {
    $_SESSION['cycle_counter']++;
    $posInCycle = $_SESSION['cycle_counter'];
    $cycleNumber = $_SESSION['cycle_number'];

    if ($posInCycle === 1) {
        $_SESSION['picked_numbers'] = pickRandomPositions($targetCount, $selectCount);
    }

    $isSelected = in_array($posInCycle, $_SESSION['picked_numbers']);

    // บันทึก Log
    $note = "รอบที่ $cycleNumber | คนที่ $posInCycle | สุ่ม: " . implode(',', $_SESSION['picked_numbers']);
    $stmtLog = $pdo->prepare("INSERT INTO screening_log(user_id, station_id, result_color, notes) VALUES (:user,:station,:color,:note)");
    $stmtLog->bindValue(':user', $_SESSION['user_id']);
    $stmtLog->bindValue(':station', $selectedStationId);
    $stmtLog->bindValue(':color', $isSelected ? "RED" : "GREEN");
    $stmtLog->bindValue(':note', $note);
    $stmtLog->execute();
    $logId = (int)$pdo->lastInsertId();

    // กำหนดข้อความแสดงผล
    if ($isSelected) {
        $displayResult = "#$logId =>*เข้าห้องตรวจ*";
        $displayColor = "red";
    } else {
        $displayResult = "#$logId => สแกนปกติ";
        $displayColor = "green";
    }

    // รีเซ็ตรอบใหม่
    if ($posInCycle >= $targetCount) {
        $_SESSION['cycle_number']++;
        $_SESSION['cycle_counter'] = 0;
        $_SESSION['picked_numbers'] = [];
    }
}

function pickRandomPositions($total, $need) {
    $all = range(1, $total);
    shuffle($all);
    return array_slice($all, 0, $need);
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
html, body { height:100%; }

/* ปุ่มออกจากระบบ */
.logout-btn {
    position: fixed;
    top:20px;
    right:20px;
    z-index:100;
    padding:12px 24px;
    background:rgba(0,0,0,0.2);
    color:white;
    border:none;
    border-radius:8px;
    font-family:'Sarabun', sans-serif;
    font-size:18px;
    font-weight:bold;
    cursor:pointer;
    transition:background 0.2s;
}
.logout-btn:hover {
    background:rgba(0,0,0,0.4);
}

#displayBox {
    width:100%;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    font-size:96px;
    font-weight:bold;
    color:white;
    font-family:'Sarabun', 'Sarabun New', sans-serif;
    transition:background-color 0.3s ease;
}
.green { background:#00c853; }
.red { background:#d32f2f; }

/* แอนิเมชันรอ + สีตัวอักษรสีส้ม */
@keyframes pulse {
    0% { opacity:1; }
    50% { opacity:0.6; }
    100% { opacity:1; }
}
.waiting {
    animation:pulse 0.8s infinite;
    color:#ff9800; /* สีส้ม */
}
</style>
</head>
<body>

<!-- ปุ่มออกจากระบบ -->
<form action="logout.php" method="get">
    <button type="submit" class="logout-btn">🚪 ออกจากระบบ</button>
</form>

<div id="displayBox" class="green"><?php echo $displayResult; ?></div>

<form id="screenForm" method="POST">
    <input type="hidden" name="station_id" value="<?php echo $selectedStationId; ?>">
    <input type="hidden" name="trigger_screen" value="1">
</form>

<script>
let triggered = false;
const form = document.getElementById('screenForm');
const displayBox = document.getElementById('displayBox');

document.addEventListener('keyup', () => {
    if (triggered) return;
    triggered = true;
    
    // แสดงสถานะกำลังประมวลผล — ตัวอักษรสีส้ม พื้นเขียว
    displayBox.textContent = "...กำลังประมวลผล...";
    displayBox.classList.add('waiting');
    displayBox.style.color = "#ff9800"; // สีส้ม
    
    // ส่งฟอร์ม
    setTimeout(() => {
        form.submit();
    }, 300);
});
</script>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['trigger_screen'])): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const box = document.getElementById('displayBox');
    box.classList.remove('waiting');
    box.style.color = "white"; // กลับเป็นสีขาว
    box.className = "<?php echo $displayColor; ?>";
    box.textContent = "<?php echo $displayResult; ?>";
    setTimeout(() => triggered = false, 500);
});
</script>
<?php endif; ?>

</body>
</html>
