<?php
// ไฟล์: job_list.php
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

// 1. จัดการการ "รับงาน" (คงเดิม)
if (isset($_GET['accept_id'])) {
    $accept_id = mysqli_real_escape_string($conn, $_GET['accept_id']);
    $sql_accept = "UPDATE Repair_Request SET technician_id = '$user_id', repair_status = 'กำลังดำเนินการ', user_noti_read = 0 WHERE repair_request_id = '$accept_id' AND technician_id IS NULL";
    if(mysqli_query($conn, $sql_accept)){
        $_SESSION['sys_msg'] = 'รับงานแจ้งซ่อมเรียบร้อยแล้ว'; $_SESSION['sys_msg_type'] = 'success';
    }
    header("Location: job_list.php"); exit();
}

// 2. จัดการการ "อัปเดตสถานะ" (คงเดิม)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['repair_request_id'])) {
    $req_id = mysqli_real_escape_string($conn, $_POST['repair_request_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['repair_status']);
    $device_id = mysqli_real_escape_string($conn, $_POST['device_id_hidden']);
    $sql_update = "UPDATE Repair_Request SET repair_status = '$new_status', user_noti_read = 0 WHERE repair_request_id = '$req_id' AND technician_id = '$user_id'";
    if (mysqli_query($conn, $sql_update)) {
        if ($new_status == 'สำเร็จ') mysqli_query($conn, "UPDATE IT_Device SET device_status = 'ใช้งานปกติ' WHERE device_id = '$device_id'");
        elseif ($new_status == 'ไม่สามารถซ่อมได้') mysqli_query($conn, "UPDATE IT_Device SET device_status = 'ชำรุด' WHERE device_id = '$device_id'");
        else mysqli_query($conn, "UPDATE IT_Device SET device_status = 'ส่งซ่อม' WHERE device_id = '$device_id'");
        $_SESSION['sys_msg'] = 'อัปเดตสถานะงานซ่อมเรียบร้อยแล้ว'; $_SESSION['sys_msg_type'] = 'success';
    }
    header("Location: job_list.php"); exit();
}

$message = '';
if (isset($_SESSION['sys_msg'])) {
    $message = '<div class="alert alert-' . $_SESSION['sys_msg_type'] . ' alert-dismissible fade show shadow-sm rounded-3" role="alert">' . $_SESSION['sys_msg'] . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['sys_msg']); unset($_SESSION['sys_msg_type']);
}

// ดึงข้อมูล "งานใหม่ที่รอรับ" (คงเดิม)
$sql_pending = "SELECT r.*, d.device_name, d.device_type, d.serial_number, u.full_name AS requester_name FROM Repair_Request r LEFT JOIN IT_Device d ON r.device_id = d.device_id LEFT JOIN User u ON r.user_id = u.user_id WHERE r.technician_id IS NULL ORDER BY r.request_date ASC";
$result_pending = mysqli_query($conn, $sql_pending);

// 3. ปรับปรุงการดึงข้อมูล "งานที่ฉันรับผิดชอบ" (เพิ่ม Filter และ po_status)
$where_clause = "WHERE r.technician_id = '$user_id' AND r.repair_status NOT IN ('สำเร็จ', 'ไม่สามารถซ่อมได้')";
if ($filter_year) $where_clause .= " AND YEAR(r.request_date) = '$filter_year'";
if ($filter_month) $where_clause .= " AND MONTH(r.request_date) = '$filter_month'";

$sql_my_jobs = "SELECT r.*, d.device_name, d.device_type, d.serial_number, u.full_name AS requester_name, po.order_number, po.po_status 
                FROM Repair_Request r 
                LEFT JOIN IT_Device d ON r.device_id = d.device_id 
                LEFT JOIN User u ON r.user_id = u.user_id 
                LEFT JOIN Purchase_Order po ON r.repair_request_id = po.repair_request_id 
                $where_clause 
                ORDER BY r.request_date DESC";
$result_my_jobs = mysqli_query($conn, $sql_my_jobs);

// สถิติสำหรับกราฟแท่ง
$count_repairing = 0; $count_wait_parts = 0; $my_jobs_data = [];
while($row = mysqli_fetch_assoc($result_my_jobs)) {
    $my_jobs_data[] = $row;
    if($row['repair_status'] == 'กำลังดำเนินการ') $count_repairing++;
    else $count_wait_parts++;
}

echo getSystemHeader("จัดการงานซ่อม - IT Repair");
?>

<div class="bg-white shadow-sm sticky-top w-100" style="z-index: 1020;">
    <div class="container d-flex justify-content-between align-items-center py-3">
        <a href="index.php" class="text-decoration-none font-bold text-2xl" style="color: #1a1a2e;">IT<span style="color: #9b51e0;">Repair</span></a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted d-none d-md-inline me-2">ช่างเทคนิค: <?php echo htmlspecialchars($full_name); ?></span>
            <a href="profile.php" class="btn text-white px-4 btn-sm" style="background-color: #9b51e0; border-radius: 50px;">ข้อมูลส่วนตัว</a>
            <a href="logout.php" class="btn btn-outline-danger px-4 btn-sm" style="border-radius: 50px;">ออกจากระบบ</a>
        </div>
    </div>
</div>

<!-- <div class="container py-4">
    <div class="row align-items-end mb-4 g-3">
        <div class="col-lg-6">
            <h3 class="font-bold text-wix-dark mb-0">จัดการงานแจ้งซ่อม</h3>
            <p class="text-muted text-sm mb-0">รับงานใหม่ และปรับสถานะงานที่คุณรับผิดชอบ</p>
        </div>
        <div class="col-lg-6">
            <form method="GET" class="row g-2 justify-content-lg-end" autocomplete="off">
                <div class="col-auto">
                    <select name="filter_month" class="form-select form-select-sm rounded-pill px-3">
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
                    <select name="filter_year" class="form-select form-select-sm rounded-pill px-3" style="width: 100px;">
                        <?php 
                        $curr_y = date('Y');
                        for($y = $curr_y; $y >= $curr_y - 4; $y--) {
                            $selected = ($filter_year == $y) ? 'selected' : '';
                            echo "<option value='$y' $selected>".($y+543)."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3">ค้นหา</button>
                    <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">กลับหน้าหลัก</a>
                </div>
            </form>
        </div>
    </div> -->

    <?php echo $message; ?>
<!-- 
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom"><h6 class="mb-0 font-bold"><i class="bi bi-bar-chart-line me-2"></i>สรุปงานที่ค้างอยู่แยกตามสถานะ</h6></div>
                <div class="card-body py-4"><div style="height: 200px;"><canvas id="jobBarChart"></canvas></div></div>
            </div>
        </div>
    </div> -->
     <br/>
    <h5 class="font-bold text-wix-purple mb-3"><i class="bi bi-tools me-2"></i>งานที่ฉันรับผิดชอบ (กำลังดำเนินการ)</h5>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted text-sm">
                        <tr>
                            <th class="py-3 px-4">เลขที่อ้างอิง</th>
                            <th class="py-3">ผู้แจ้ง / วันที่</th>
                            <th class="py-3">อุปกรณ์ / สถานที่</th>
                            <th class="py-3">ปัญหา / สถานะอะไหล่</th>
                            <th class="py-3 text-center">สถานะปัจจุบัน</th>
                            <th class="py-3 text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($my_jobs_data) > 0): ?>
                            <?php foreach($my_jobs_data as $job): ?>
                            <tr>
                                <td class="px-4 font-medium text-wix-dark">REP-<?php echo str_pad($job['repair_request_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><div class="font-semibold"><?php echo htmlspecialchars($job['requester_name']); ?></div><div class="text-xs text-muted"><?php echo ThaiDate($job['request_date']); ?></div></td>
                                <td><div class="font-semibold text-primary"><?php echo htmlspecialchars($job['device_name']); ?></div><div class="text-xs text-muted"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($job['repair_location']); ?></div></td>
                                <td>
                                    <div class="text-sm text-truncate mb-1" style="max-width: 200px;" title="<?php echo htmlspecialchars($job['problem_description']); ?>"><?php echo htmlspecialchars($job['problem_description']); ?></div>
                                    <?php if($job['order_number']): ?>
                                        <?php if($job['po_status'] == 'ได้รับของแล้ว'): ?>
                                            <span class="badge bg-success rounded-pill px-2 py-1" style="font-size: 0.65rem;"><i class="bi bi-box-seam me-1"></i> อะไหล่มาถึงแล้ว (พร้อมซ่อม)</span>
                                        <?php else: ?>
                                            <div class="text-success fw-bold" style="font-size: 0.75rem;"><i class="bi bi-check-circle-fill me-1"></i> สั่งซื้อแล้ว (PO: <?php echo $job['order_number']; ?>)</div>
                                        <?php endif; ?>
                                    <?php elseif($job['repair_status'] == 'สั่งซื้ออุปกรณ์'): ?>
                                        <div class="text-muted fst-italic" style="font-size: 0.75rem;"><i class="bi bi-hourglass-split me-1"></i> รอฝ่ายจัดซื้อออก PO</div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php $badge = ($job['repair_status'] == 'สั่งซื้ออุปกรณ์') ? 'bg-info text-dark' : 'bg-warning text-dark'; ?>
                                    <span class="badge <?php echo $badge; ?> rounded-pill px-3 py-2"><?php echo htmlspecialchars($job['repair_status']); ?></span>
                                </td>
                                <td class="text-center"><button class="btn btn-sm btn-purple rounded-pill px-3" onclick="openUpdateModal('<?php echo $job['repair_request_id']; ?>', '<?php echo $job['device_id']; ?>', '<?php echo $job['repair_status']; ?>')">อัปเดตสถานะ</button></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">ไม่พบงานในช่วงเวลาที่เลือก</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <h5 class="font-bold text-wix-dark mb-3"><i class="bi bi-inbox me-2"></i>งานใหม่ (รอช่างรับงาน)</h5>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden border border-warning">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted text-sm">
                        <tr><th class="py-3 px-4">เลขที่</th><th class="py-3">ผู้แจ้ง / วันที่</th><th class="py-3">อุปกรณ์ / สถานที่</th><th class="py-3">ปัญหา / อาการ</th><th class="py-3 text-center">จัดการ</th></tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result_pending) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result_pending)): ?>
                            <tr>
                                <td class="px-4 font-medium text-wix-dark">REP-<?php echo str_pad($row['repair_request_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><div class="font-semibold"><?php echo htmlspecialchars($row['requester_name']); ?></div><div class="text-xs text-muted"><?php echo ThaiDate($row['request_date']); ?></div></td>
                                <td><div class="font-semibold text-primary"><?php echo htmlspecialchars($row['device_name']); ?></div><div class="text-xs text-muted"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($row['repair_location']); ?></div></td>
                                <td><div class="text-sm text-truncate" style="max-width: 250px;"><?php echo htmlspecialchars($row['problem_description']); ?></div></td>
                                <td class="text-center"><a href="job_list.php?accept_id=<?php echo $row['repair_request_id']; ?>" class="btn btn-sm btn-outline-success rounded-pill px-4 fw-bold" onclick="return confirm('ยืนยันการรับงานแจ้งซ่อมนี้?');">กดรับงาน</a></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">ไม่มีรายการแจ้งซ่อมใหม่ในขณะนี้</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow rounded-4"><div class="modal-header bg-light border-0 px-4 py-3 rounded-top-4"><h5 class="modal-title font-bold text-wix-dark">อัปเดตสถานะการซ่อม</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form action="job_list.php" method="POST"><div class="modal-body px-4 py-4"><input type="hidden" id="modal_repair_request_id" name="repair_request_id"><input type="hidden" id="modal_device_id_hidden" name="device_id_hidden"><div class="mb-3"><label class="form-label font-medium text-wix-dark">เลือกสถานะใหม่ <span class="text-danger">*</span></label><select class="form-select form-select-lg" id="modal_repair_status" name="repair_status" required><option value="กำลังดำเนินการ">กำลังดำเนินการ (ซ่อมอยู่)</option><option value="สั่งซื้ออุปกรณ์">สั่งซื้ออุปกรณ์ (รออะไหล่)</option><option value="สำเร็จ">สำเร็จ (ซ่อมเสร็จแล้ว)</option><option value="ไม่สามารถซ่อมได้">ไม่สามารถซ่อมได้</option></select></div></div><div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button><button type="submit" class="btn-purple px-4">บันทึกสถานะ</button></div></form></div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('jobBarChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['กำลังซ่อมอยู่', 'รออะไหล่/สั่งซื้ออุปกรณ์'],
            datasets: [{
                label: 'จำนวนงานที่ค้างอยู่ (รายการ)',
                data: [<?php echo $count_repairing; ?>, <?php echo $count_wait_parts; ?>],
                backgroundColor: ['#ffc107', '#0dcaf0'],
                borderRadius: 10,
                barThickness: 50
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } }, x: { grid: { display: false } } }
        }
    });

    function openUpdateModal(req_id, dev_id, current_status) {
        document.getElementById('modal_repair_request_id').value = req_id;
        document.getElementById('modal_device_id_hidden').value = dev_id;
        document.getElementById('modal_repair_status').value = current_status;
        var myModal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
        myModal.show();
    }
</script>
<?php echo getSystemFooter(); ?>