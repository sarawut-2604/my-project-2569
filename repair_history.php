<?php
// ไฟล์: repair_history.php
require_once 'config.php';

// ตรวจสอบการ Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$user_role = $_SESSION['role'];

// หากไม่ใช่ผู้ใช้งานทั่วไป
if ($user_role !== 'ผู้ใช้งานทั่วไป') {
    echo "<script>alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้'); window.location.href='index.php';</script>";
    exit();
}

// --- ส่วนที่เพิ่ม: รับค่าจาก Filter ---
$filter_year = isset($_GET['filter_year']) ? $_GET['filter_year'] : date('Y');
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : '';

// สร้างเงื่อนไข SQL ตามตัวกรอง
$where_clause = "WHERE r.user_id = '$user_id'";
if ($filter_year) {
    $where_clause .= " AND YEAR(r.request_date) = '$filter_year'";
}
if ($filter_month) {
    $where_clause .= " AND MONTH(r.request_date) = '$filter_month'";
}

// 1. ดึงข้อมูลประวัติการแจ้งซ่อม พร้อม JOIN ตาราง Purchase_Order
$sql_history = "SELECT r.*, d.device_name, d.device_type, d.serial_number, u.full_name AS tech_name, 
                       po.order_number, po.po_status 
                FROM Repair_Request r
                LEFT JOIN IT_Device d ON r.device_id = d.device_id
                LEFT JOIN User u ON r.technician_id = u.user_id
                LEFT JOIN Purchase_Order po ON r.repair_request_id = po.repair_request_id
                $where_clause
                ORDER BY r.request_date DESC";

$result_history = mysqli_query($conn, $sql_history);

// สรุปสถิติสำหรับกราฟและ Dashboard (คงเดิม)
$stat_success = 0; $stat_cannot_repair = 0; $stat_in_progress = 0; $rows_for_modal = [];
if(mysqli_num_rows($result_history) > 0) {
    $temp_res = mysqli_query($conn, $sql_history); 
    while($s = mysqli_fetch_assoc($temp_res)) {
        if($s['repair_status'] == 'สำเร็จ') $stat_success++;
        elseif($s['repair_status'] == 'ไม่สามารถซ่อมได้') $stat_cannot_repair++;
        else $stat_in_progress++;
    }
}

// --- ฟังก์ชันสำหรับกรอง Log ของฝ่ายจัดซื้อออก (เพิ่มใหม่) ---
function cleanUserDescription($text) {
    return preg_replace('/\[(ระบบจัดซื้อ|คลังสินค้า|จัดซื้อ)\].*?(\n|$)/s', '', $text);
}

echo getSystemHeader("ตรวจสอบผลการแจ้งซ่อม - ระบบแจ้งซ่อม");
?>

<div class="bg-white shadow-sm sticky-top w-100" style="z-index: 1020;">
    <div class="container d-flex justify-content-between align-items-center py-3">
        <a href="index.php" class="text-decoration-none font-bold text-2xl" style="color: #1a1a2e;">
            IT<span style="color: #9b51e0;">Repair</span>
        </a>
        <div class="d-flex align-items-center" style="gap: 10px;">
            <span class="text-muted d-none d-md-inline me-2">สวัสดี, <?php echo htmlspecialchars($full_name); ?></span>
            <a href="profile.php" class="btn text-white px-4 btn-sm" style="background-color: #9b51e0; border-radius: 50px;">ข้อมูลส่วนตัว</a>
            <a href="logout.php" class="btn btn-outline-danger px-4 btn-sm" style="border-radius: 50px;">ออกจากระบบ</a>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row align-items-end mb-4 g-3">
        <div class="col-lg-6">
            <h2 class="font-extrabold text-3xl text-wix-dark mb-1">ตรวจสอบสถานะการแจ้งซ่อม</h2>
            <p class="text-gray-500 mb-0">ประวัติและรายงานการแจ้งซ่อมอุปกรณ์ IT ของคุณ</p>
        </div>
        <div class="col-lg-6">
            <form method="GET" class="row g-2 justify-content-lg-end align-items-center" autocomplete="off">
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
                    <select name="filter_year" class="form-select form-select-sm rounded-pill" style="width: 100px;">
                        <?php 
                        $current_y = date('Y');
                        for($y = $current_y; $y >= $current_y - 3; $y--) {
                            $selected = ($filter_year == $y) ? 'selected' : '';
                            echo "<option value='$y' $selected>".($y+543)."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3">ค้นหา</button>
                    <a href="repair_request.php" class="btn btn-sm btn-purple rounded-pill px-3" style="background-color: #9b51e0; color:white;">+ แจ้งซ่อมใหม่</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h6 class="font-bold mb-3"><i class="bi bi-bar-chart-line me-2"></i>สรุปสถานะการแจ้งซ่อมของท่าน</h6>
                    <div style="height: 200px;"><canvas id="repairBarChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted text-sm text-uppercase">
                        <tr>
                            <th class="py-3 px-4">เลขที่อ้างอิง</th>
                            <th class="py-3">วันที่แจ้งซ่อม</th>
                            <th class="py-3">อุปกรณ์ / ปัญหา</th>
                            <th class="py-3">ช่างผู้รับผิดชอบ</th>
                            <th class="py-3 text-center">สถานะ</th>
                            <th class="py-3 text-center">การจัดซื้อ (PO)</th>
                            <th class="py-3 text-center">รูปภาพ</th>
                            <th class="py-3 text-center">รายละเอียด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result_history) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result_history)): 
                                // --- Logic แยกแยะการแสดงผล (Make Sense) ---
                                if($row['repair_status'] == 'ไม่สามารถซ่อมได้') {
                                    // เคสเบิกใหม่: ให้ผู้ใช้เห็นข้อมูลจัดซื้อทั้งหมด
                                    $display_desc = $row['problem_description']; 
                                } else {
                                    // เคสซ่อมปกติ: กรองเรื่องจัดซื้อออก
                                    $display_desc = cleanUserDescription($row['problem_description']);
                                }
                                $row['problem_description'] = $display_desc; 
                                $rows_for_modal[] = $row;
                            ?>
                            <tr>
                                <td class="px-4 font-medium text-wix-purple">REP-<?php echo str_pad($row['repair_request_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td class="text-sm"><?php echo ThaiDate($row['request_date']); ?><br><span class="text-muted text-xs"><?php echo date('H:i', strtotime($row['request_date'])); ?> น.</span></td>
                                <td>
                                    <div class="font-semibold text-wix-dark"><?php echo htmlspecialchars($row['device_name']); ?></div>
                                    <div class="text-xs text-muted mt-1 text-truncate" style="max-width: 250px;">อาการ: <?php echo htmlspecialchars($display_desc); ?></div>
                                </td>
                                <td class="text-sm"><?php echo ($row['tech_name']) ? '<span class="text-success font-medium">'.htmlspecialchars($row['tech_name']).'</span>' : '<span class="text-muted fst-italic">รอรับงาน</span>'; ?></td>
                                <td class="text-center">
                                    <?php $s = $row['repair_status']; $bc = 'bg-secondary';
                                        if($s == 'กำลังดำเนินการ') $bc = 'bg-warning text-dark';
                                        if($s == 'สั่งซื้ออุปกรณ์') $bc = 'bg-info text-dark';
                                        if($s == 'สำเร็จ') $bc = 'bg-success';
                                        if($s == 'ไม่สามารถซ่อมได้') $bc = 'bg-danger'; ?>
                                    <span class="badge <?php echo $bc; ?> rounded-pill fw-normal px-3 py-2"><?php echo htmlspecialchars($s); ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if($row['order_number']): ?>
                                        <?php if($row['repair_status'] == 'ไม่สามารถซ่อมได้'): ?>
                                            <div class="<?php echo ($row['po_status'] == 'ได้รับของแล้ว') ? 'text-success' : 'text-primary'; ?> font-bold text-xs mb-1">
                                                <i class="bi <?php echo ($row['po_status'] == 'ได้รับของแล้ว') ? 'bi-box-seam' : 'bi-cart-check'; ?>"></i> 
                                                <?php echo ($row['po_status'] == 'ได้รับของแล้ว') ? 'ของมาถึงแล้ว' : 'ออก PO แล้ว'; ?>
                                            </div>
                                            <span class="badge <?php echo ($row['po_status'] == 'ได้รับของแล้ว') ? 'bg-success-subtle text-success border-success' : 'bg-primary-subtle text-primary border-primary'; ?> border"><?php echo $row['order_number']; ?></span>
                                        <?php else: ?> - <?php endif; ?>
                                    <?php else: ?> - <?php endif; ?>
                                </td>
                                <td class="text-center"><?php if(!empty($row['repair_image'])): ?><a href="uploads/<?php echo htmlspecialchars($row['repair_image']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill">ดูรูปภาพ</a><?php else: ?>-<?php endif; ?></td>
                                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#descModal<?php echo $row['repair_request_id']; ?>">ดูรายละเอียด</button></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center py-5 text-muted">ไม่พบข้อมูลในช่วงเวลาที่เลือก</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php foreach($rows_for_modal as $m_row): ?>
<div class="modal fade" id="descModal<?php echo $m_row['repair_request_id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 bg-light px-4 py-3"><h5 class="modal-title font-bold">รายละเอียด REP-<?php echo str_pad($m_row['repair_request_id'], 4, '0', STR_PAD_LEFT); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <div class="mb-3"><label class="text-muted text-xs text-uppercase fw-bold mb-1">อุปกรณ์</label><div class="font-semibold text-primary"><?php echo htmlspecialchars($m_row['device_name']); ?></div></div>
                <div class="mb-4">
                    <label class="text-muted text-xs text-uppercase fw-bold mb-1">บันทึกการซ่อม/เบิก</label>
                    <div class="p-3 bg-light rounded-3" style="white-space: pre-wrap; line-height: 1.6; color: #1a1a2e; min-height: 150px;">
                        <?php echo htmlspecialchars($m_row['problem_description']); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button></div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('repairBarChart').getContext('2d');
    new Chart(ctx, { type: 'bar', data: { labels: ['สำเร็จ', 'ซ่อมไม่ได้', 'รอดำเนินการ/รออะไหล่'], datasets: [{ label: 'จำนวนงาน', data: [<?php echo $stat_success; ?>, <?php echo $stat_cannot_repair; ?>, <?php echo $stat_in_progress; ?>], backgroundColor: ['#198754', '#dc3545', '#ffc107'], borderRadius: 10, barThickness: 40 }] }, options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } }, x: { grid: { display: false } } } } });
</script>

<style>
    .font-bold { font-weight: 600; } .font-extrabold { font-weight: 800; }
    .bg-success-subtle { background-color: #e6fcf5; } .bg-primary-subtle { background-color: #e7f1ff; }
</style>
<?php echo getSystemFooter(); ?>