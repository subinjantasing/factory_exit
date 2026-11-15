<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ช่วงเวลา
$startDate = isset($_POST['start_date']) ? $_POST['start_date'] : date("Y-m-d 00:00:00");
$endDate = isset($_POST['end_date']) ? $_POST['end_date'] : date("Y-m-d 23:59:59");

// Pagination — ตั้งค่า
$perPage = 20; // จำนวนแถวต่อหน้า
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// Export CSV
if (isset($_POST['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="screening_report_'.date("Ymd").'.csv"');
    $output = fopen("php://output", "w");
    fputcsv($output, ["เวลา","จุดตรวจ","รปภ. ผู้กด","ผลลัพธ์","หมายเหตุ"]);

    $stmtLog = $pdo->prepare("
        SELECT l.log_datetime, s.station_code, u.full_name, l.result_color, l.notes
        FROM screening_log l
        JOIN screening_stations s ON l.station_id = s.station_id
        JOIN users u ON l.user_id = u.user_id
        WHERE l.log_datetime BETWEEN :start AND :end
        ORDER BY l.log_datetime DESC
    ");
    $stmtLog->bindValue(':start', $startDate);
    $stmtLog->bindValue(':end', $endDate);
    $stmtLog->execute();

    while ($row = $stmtLog->fetch(PDO::FETCH_ASSOC)) {
        $resultText = $row['result_color'] === "RED" ? "ตรวจละเอียด" : "ผ่าน";
        fputcsv($output, [
            $row['log_datetime'],
            $row['station_code'],
            $row['full_name'],
            $resultText,
            $row['notes'] ?? '-'
        ]);
    }
    fclose($output);
    exit;
}

// นับจำนวนทั้งหมด
$stmtCount = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM screening_log l
    WHERE l.log_datetime BETWEEN :start AND :end
");
$stmtCount->bindValue(':start', $startDate);
$stmtCount->bindValue(':end', $endDate);
$stmtCount->execute();
$totalRow = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRow / $perPage);

// ดึงข้อมูลตามหน้า
$stmtLog = $pdo->prepare("
    SELECT l.log_id, l.log_datetime, l.result_color, l.notes,
           s.station_code, s.station_name, u.full_name AS guard_name
    FROM screening_log l
    JOIN screening_stations s ON l.station_id = s.station_id
    JOIN users u ON l.user_id = u.user_id
    WHERE l.log_datetime BETWEEN :start AND :end
    ORDER BY l.log_datetime DESC
    LIMIT :limit OFFSET :offset
");
$stmtLog->bindValue(':start', $startDate);
$stmtLog->bindValue(':end', $endDate);
$stmtLog->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmtLog->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtLog->execute();
$logList = $stmtLog->fetchAll(PDO::FETCH_ASSOC);

// สรุปผล
$stmtSum = $pdo->prepare("
    SELECT result_color, COUNT(*) AS total_count
    FROM screening_log
    WHERE log_datetime BETWEEN :start AND :end
    GROUP BY result_color
");
$stmtSum->bindValue(':start', $startDate);
$stmtSum->bindValue(':end', $endDate);
$stmtSum->execute();
$summary = $stmtSum->fetchAll(PDO::FETCH_ASSOC);

$redTotal = $greenTotal = 0;
foreach ($summary as $row) {
    if ($row['result_color'] === 'RED') $redTotal = $row['total_count'];
    if ($row['result_color'] === 'GREEN') $greenTotal = $row['total_count'];
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<!-- Font Awesome + Sarabun Font -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: 'Sarabun', 'Sarabun New', sans-serif;
    background: #f8fafc;
    padding: 20px;
}

/* ===== เมนูด้านบน ===== */
.top-nav {
    background:#1e293b;
    padding:14px 24px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-radius:10px;
    margin-bottom:24px;
}
.nav-left {
    display:flex;
    align-items:center;
    gap:10px;
    color:white;
    font-size:17px;
}
.nav-left i {
    font-size:26px;
    color:#60a5fa;
}
.nav-right {
    display:flex;
    gap:8px;
    align-items:center;
}
.nav-right a {
    display:flex;
    align-items:center;
    gap:6px;
    color:#e2e8f0;
    text-decoration:none;
    padding:10px 16px;
    border-radius:6px;
    font-size:15px;
    transition:background 0.2s;
}
.nav-right a:hover {
    background:#334155;
}
.nav-right a.active {
    background:#2563eb;
    color:white;
}
.nav-right a.logout-link {
    color:#fecaca;
}
.nav-right a.logout-link:hover {
    background:#991b1b;
}
.nav-right i {
    font-size:17px;
}

.container {width:920px;margin:0 auto;}
h2 {font-size:24px; color:#1e293b; margin-bottom:20px;}
table {width:100%;border-collapse:collapse;margin:15px 0; background:white; border-radius:8px; overflow:hidden;}
th,td {border:1px solid #e2e8f0;padding:10px;text-align:center; font-size:15px;}
th {background:#1e293b;color:white; font-weight:600;}
.redRow {background:#fef2f2;}
.greenRow {background:#f0fdf4;}
input,button {padding:8px 12px;font-size:15px;margin:3px; border-radius:6px; border:1px solid #cbd5e1;}
button {background:#2563eb;color:white;border:none; cursor:pointer;}
button:hover {background:#1d4ed8;}
.exportBtn {background:#16a34a;}
.exportBtn:hover {background:#15803d;}

/* Pagination */
.pagination {text-align:center;margin:24px 0;}
.pagination a, .pagination strong {
    display:inline-block;
    padding:8px 14px;
    margin:0 4px;
    border-radius:6px;
    text-decoration:none;
    border:1px solid #d1d5db;
    color:#374151;
}
.pagination a:hover {background:#f3f4f6;}
.pagination strong {background:#2563eb;color:white;border-color:#2563eb;}
.pageInfo {text-align:center;color:#6b7280;margin-bottom:12px; font-size:15px;}
.summary-box {background:white; padding:16px; border-radius:8px; margin-bottom:20px; border:1px solid #e2e8f0;}
</style>
</head>
<body>

<!-- 🧭 เมนูด้านบน -->
<div class="top-nav">
    <div class="nav-left">
        <i class="fa-solid fa-chart-line"></i>
        <span>ผู้ใช้ : <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
    </div>
    <div class="nav-right">
        <a href="admin_setting.php">
            <i class="fa-solid fa-gear"></i>
            <span>ตั้งค่า</span>
        </a>
        <a href="admin_report.php" class="active">
            <i class="fa-solid fa-chart-bar"></i>
            <span>รายงาน</span>
        </a>
        <a href="admin_change_password.php">
            <i class="fa-solid fa-key"></i>
            <span>เปลี่ยนรหัส</span>
        </a>
        <a href="logout.php" class="logout-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>ออกจากระบบ</span>
        </a>
    </div>
</div>

<div class="container">
<h2><i class="fa-solid fa-file-report" style="color:#2563eb;"></i> รายงานผลการตรวจ</h2>

<form method="POST" style="background:white; padding:16px; border-radius:8px; margin-bottom:20px; border:1px solid #e2e8f0;">
<label>📅 ช่วงเวลา :</label>
<input type="datetime-local" name="start_date" value="<?php echo htmlspecialchars(str_replace(" ","T",$startDate)); ?>" required>
<label>ถึง :</label>
<input type="datetime-local" name="end_date" value="<?php echo htmlspecialchars(str_replace(" ","T",$endDate)); ?>" required>
<button type="submit"><i class="fa-solid fa-magnifying-glass"></i> ค้นหา</button>
<button type="submit" name="export_csv" class="exportBtn"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
</form>

<div class="summary-box">
<h3 style="margin-bottom:10px; font-size:18px;">📈 สรุปผล</h3>
<p style="font-size:16px;">
🔴 ต้องเข้าห้องตรวจละเอียด : <strong style="color:#dc2626; font-size:18px;"><?php echo $redTotal; ?></strong> คน &nbsp;|&nbsp;
🟢 สแกนปกติ : <strong style="color:#16a34a; font-size:18px;"><?php echo $greenTotal; ?></strong> คน &nbsp;|&nbsp;
✅ รวมทั้งหมด : <strong style="font-size:18px;"><?php echo number_format($totalRow); ?></strong> คน
</p>
</div>

<h3 style="margin-bottom:10px;">📋 รายการบันทึก</h3>

<!-- ข้อมูลหน้า -->
<div class="pageInfo">
หน้า <strong><?php echo $page; ?></strong> / <?php echo $totalPages; ?> &nbsp;|&nbsp;
แสดง <strong><?php echo count($logList); ?></strong> แถว จากทั้งหมด <strong><?php echo number_format($totalRow); ?></strong> แถว
</div>

<table>
<tr>
<th>#ID</th>
<th>เวลา</th>
<th>จุดตรวจ</th>
<th>รปภ.</th>
<th>ผลลัพธ์</th>
<th>หมายเหตุ</th>
</tr>
<?php if (empty($logList)): ?>
<tr><td colspan="6" style="padding:40px; color:#6b7280;">❌ ไม่พบข้อมูลในช่วงเวลาที่เลือก</td></tr>
<?php else: ?>
<?php foreach ($logList as $log): ?>
<tr class="<?php echo $log['result_color']==='RED' ? 'redRow' : 'greenRow'; ?>">
<td><strong>#<?php echo $log['log_id']; ?></strong></td>
<td><?php echo htmlspecialchars($log['log_datetime']); ?></td>
<td><?php echo htmlspecialchars($log['station_code']); ?></td>
<td><?php echo htmlspecialchars($log['guard_name']); ?></td>
<td><?php echo $log['result_color']==='RED' ? "🔴 ตรวจละเอียด" : "🟢 ผ่าน"; ?></td>
<td style="font-size:14px;"><?php echo htmlspecialchars($log['notes'] ?? '-'); ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</table>

<!-- ปุ่มเปลี่ยนหน้า -->
<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
    <a href="?page=1"><i class="fa-solid fa-angles-left"></i> แรก</a>
    <a href="?page=<?php echo $page - 1; ?>"><i class="fa-solid fa-angle-left"></i> ก่อน</a>
    <?php endif; ?>

    <?php
    $startPage = max(1, $page - 2);
    $endPage = min($totalPages, $page + 2);
    for ($i = $startPage; $i <= $endPage; $i++):
    ?>
        <?php if ($i == $page): ?>
        <strong><?php echo $i; ?></strong>
        <?php else: ?>
        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
    <a href="?page=<?php echo $page + 1; ?>">ถัดไป <i class="fa-solid fa-angle-right"></i></a>
    <a href="?page=<?php echo $totalPages; ?>">ท้าย <i class="fa-solid fa-angles-right"></i></a>
    <?php endif; ?>
</div>
<?php endif; ?>

</div>
</body>
</html>
