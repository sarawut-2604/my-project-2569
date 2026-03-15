<?php
// ไฟล์: job_history.php
require_once 'config.php';

// ตรวจสอบการ Login และสิทธิ์ช่างเทคนิค
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ช่างเทคนิค') {
    echo "<script>alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้ เฉพาะช่างเทคนิคเท่านั้น'); window.location.href='index.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// --- ส่วนที่เพิ่ม: รับค่าจาก Filter ---
$filter_year = isset($_GET['filter_year']) ? $_GET['filter_year'] : date('Y');
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : '';

// สร้างเงื่อนไข SQL ตามตัวกรอง
$where_clause = "WHERE r.technician_id = '$user_id'";
if ($filter_year) {
    $where_clause .= " AND YEAR(r.request_date) = '$filter_year'";
}
if ($filter_month) {
    $where_clause .= " AND MONTH(r.request_date) = '$filter_month'";
}

// ดึงข้อมูลประวัติงานซ่อม (ใช้เงื่อนไขจาก Filter)
$sql_history = "SELECT r.*, d.device_name, d.device_type, u.full_name AS requester_name 
                FROM Repair_Request r
                LEFT JOIN IT_Device d ON r.device_id = d.device_id
                LEFT JOIN User u ON r.user_id = u.user_id
                $where_clause
                ORDER BY r.request_date DESC";
$result_history = mysqli_query($conn, $sql_history);

// สถิติและข้อมูลตาราง
$stat_total = 0; $stat_success = 0; $stat_cannot_repair = 0; $stat_in_progress = 0;
$history_data = [];

if(mysqli_num_rows($result_history) > 0) {
    while($row = mysqli_fetch_assoc($result_history)) {
        $history_data[] = $row;
        $stat_total++;
        if($row['repair_status'] == 'สำเร็จ') $stat_success++;
        elseif($row['repair_status'] == 'ไม่สามารถซ่อมได้') $stat_cannot_repair++;
        else $stat_in_progress++; 
    }
}

echo getSystemHeader("ประวัติและรายงานการซ่อม - IT Repair");
?>

<div class="bg-white shadow-sm sticky-top w-100" style="z-index: 1020;">
    <div class="container d-flex justify-content-between align-items-center py-3">
        <a href="index.php" class="text-decoration-none" style="font-size: 1.5rem; font-weight: 700; color: #1a1a2e;">
            IT<span style="color: #9b51e0;">Repair</span>
        </a>
        <div class="d-flex align-items-center" style="gap: 10px;">
            <span class="text-muted d-none d-md-inline me-2">ช่างเทคนิค: <?php echo htmlspecialchars($full_name); ?></span>
            <a href="profile.php" class="btn text-white px-4 btn-sm" style="background-color: #9b51e0; border-radius: 50px;">ข้อมูลส่วนตัว</a>
            <a href="logout.php" class="btn btn-outline-danger px-4 btn-sm" style="border-radius: 50px;">ออกจากระบบ</a>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="row align-items-end mb-4 g-3">
        <div class="col-lg-6">
            <h3 class="font-bold text-wix-dark mb-0">ประวัติและรายงานการซ่อม</h3>
            <p class="text-muted text-sm mb-0">สรุปผลการปฏิบัติงานเฉพาะที่คุณรับผิดชอบ</p>
        </div>
        <div class="col-lg-6">
            <form method="GET" class="row g-2 justify-content-lg-end">
                <div class="col-auto">
                    <select name="filter_month" class="form-select form-select-sm rounded-pill">
                        <option value="">ทุกเดือน</option>
                        <?php 
                        $months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
                        foreach($months as $m_idx => $m_name) {
                            $m_val = $m_idx + 1;
                            $selected = ($filter_month == $m_val) ? 'selected' : '';
                            echo "<option value='$m_val' $selected>$m_name</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-auto">
                    <select name="filter_year" class="form-select form-select-sm rounded-pill">
                        <?php 
                        $current_y = date('Y');
                        for($y = $current_y; $y >= $current_y - 5; $y--) {
                            $selected = ($filter_year == $y) ? 'selected' : '';
                            echo "<option value='$y' $selected>".($y+543)."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3">ค้นหา</button>
                    <a href="job_history.php" class="btn btn-sm btn-light border rounded-pill">ล้างค่า</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 font-bold"><i class="bi bi-bar-chart-line me-2"></i>กราฟสรุปสถานะงานซ่อม</h6>
                </div>
                <div class="card-body py-4">
                    <div style="height: 250px;">
                        <canvas id="jobBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card border-0 shadow-sm rounded-4 bg-primary text-white h-100 text-center"><div class="card-body p-4"><h2 class="font-extrabold mb-0"><?php echo $stat_total; ?></h2><div class="text-sm opacity-75">งานทั้งหมด</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm rounded-4 bg-success text-white h-100 text-center"><div class="card-body p-4"><h2 class="font-extrabold mb-0"><?php echo $stat_success; ?></h2><div class="text-sm opacity-75">ซ่อมสำเร็จ</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm rounded-4 bg-danger text-white h-100 text-center"><div class="card-body p-4"><h2 class="font-extrabold mb-0"><?php echo $stat_cannot_repair; ?></h2><div class="text-sm opacity-75">ซ่อมไม่ได้</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm rounded-4 bg-warning text-dark h-100 text-center"><div class="card-body p-4"><h2 class="font-extrabold mb-0"><?php echo $stat_in_progress; ?></h2><div class="text-sm opacity-75">อยู่ระหว่างซ่อม</div></div></div></div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-5">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted text-sm text-uppercase">
                        <tr><th class="py-3 px-4">เลขที่</th><th class="py-3">วันที่</th><th class="py-3">ผู้แจ้ง</th><th class="py-3">อุปกรณ์</th><th class="py-3">ปัญหา</th><th class="py-3 text-center">สถานะ</th><th class="py-3 text-center">รายละเอียด</th></tr>
                    </thead>
                    <tbody>
                        <?php if(count($history_data) > 0): ?>
                            <?php foreach($history_data as $row): ?>
                            <tr>
                                <td class="px-4 font-medium text-dark">REP-<?php echo str_pad($row['repair_request_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td class="text-sm"><?php echo ThaiDate($row['request_date']); ?></td>
                                <td><div class="font-semibold text-dark"><?php echo htmlspecialchars($row['requester_name']); ?></div></td>
                                <td><span class="text-primary font-medium"><?php echo htmlspecialchars($row['device_name']); ?></span></td>
                                <td class="text-sm text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($row['problem_description']); ?></td>
                                <td class="text-center">
                                    <?php 
                                        $status = $row['repair_status']; $badge = 'bg-secondary';
                                        if($status == 'กำลังดำเนินการ') $badge = 'bg-warning text-dark';
                                        elseif($status == 'สั่งซื้ออุปกรณ์') $badge = 'bg-info text-dark';
                                        elseif($status == 'สำเร็จ') $badge = 'bg-success';
                                        elseif($status == 'ไม่สามารถซ่อมได้') $badge = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badge; ?> rounded-pill fw-normal px-3 py-2"><?php echo htmlspecialchars($status); ?></span>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#jobModal<?php echo $row['repair_request_id']; ?>">ดูรายละเอียด</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">ไม่พบข้อมูลงานในช่วงเวลาที่เลือก</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php foreach($history_data as $modal_row): ?>
<div class="modal fade" id="jobModal<?php echo $modal_row['repair_request_id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 bg-light px-4 py-3"><h5 class="modal-title font-bold">รายละเอียด REP-<?php echo str_pad($modal_row['repair_request_id'], 4, '0', STR_PAD_LEFT); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div class="row mb-3"><div class="col-md-6"><label class="text-muted text-xs font-bold mb-1">อุปกรณ์</label><div class="text-primary"><?php echo htmlspecialchars($modal_row['device_name']); ?></div></div><div class="col-md-6"><label class="text-muted text-xs font-bold mb-1">ผู้แจ้ง</label><div><?php echo htmlspecialchars($modal_row['requester_name']); ?></div></div></div>
                <label class="text-muted text-xs font-bold mb-1">บันทึกการซ่อม</label>
                <div class="p-3 bg-light rounded-3" style="white-space: pre-wrap;"><?php echo htmlspecialchars($modal_row['problem_description']); ?></div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('jobBarChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['สำเร็จ', 'ไม่สามารถซ่อมได้', 'อยู่ระหว่างซ่อม/รออะไหล่'],
            datasets: [{
                label: 'จำนวนงาน (รายการ)',
                data: [<?php echo $stat_success; ?>, <?php echo $stat_cannot_repair; ?>, <?php echo $stat_in_progress; ?>],
                backgroundColor: ['#198754', '#dc3545', '#ffc107'],
                borderRadius: 10,
                barThickness: 50
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            }
        }
    });
</script>

<style>
    .font-bold { font-weight: 600; }
    .font-extrabold { font-weight: 800; }
</style>

<?php echo getSystemFooter(); ?>