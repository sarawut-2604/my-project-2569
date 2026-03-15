<?php
// ไฟล์: request_parts.php
require_once 'config.php';

// ตรวจสอบการ Login และสิทธิ์ช่างเทคนิค
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ช่างเทคนิค') {
    echo "<script>alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้'); window.location.href='index.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// จัดการการส่งฟอร์มขอซื้ออะไหล่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $req_id = mysqli_real_escape_string($conn, $_POST['repair_request_id']);
    $parts_detail = mysqli_real_escape_string($conn, $_POST['parts_detail']);
    
    // อัปเดตสถานะเป็น "สั่งซื้ออุปกรณ์" และนำรายการอะไหล่ไปต่อท้ายในรายละเอียดปัญหา
    $append_text = "\n\n[คำร้องขอจัดซื้ออะไหล่จากช่าง]: " . $parts_detail;
    
    $sql_update = "UPDATE Repair_Request 
                   SET repair_status = 'สั่งซื้ออุปกรณ์',
                       problem_description = CONCAT(problem_description, '$append_text'),
                       pur_noti_read = 0
                   WHERE repair_request_id = '$req_id' AND technician_id = '$user_id'";
                   
    if (mysqli_query($conn, $sql_update)) {
        $_SESSION['sys_msg'] = 'ส่งคำร้องขอจัดซื้ออะไหล่เรียบร้อยแล้ว สถานะงานถูกเปลี่ยนเป็น "สั่งซื้ออุปกรณ์"';
        $_SESSION['sys_msg_type'] = 'success';
    } else {
        $_SESSION['sys_msg'] = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn);
        $_SESSION['sys_msg_type'] = 'danger';
    }
    
    header("Location: request_parts.php");
    exit();
}

// จัดการข้อความแจ้งเตือน
$message = '';
if (isset($_SESSION['sys_msg'])) {
    $message = '<div class="alert alert-' . $_SESSION['sys_msg_type'] . ' alert-dismissible fade show shadow-sm rounded-3" role="alert">
                    ' . $_SESSION['sys_msg'] . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
    unset($_SESSION['sys_msg']);
    unset($_SESSION['sys_msg_type']);
}

// ดึงรายการงานที่ "กำลังดำเนินการ" หรือ "สั่งซื้ออุปกรณ์" ของช่างคนนี้มาเป็นตัวเลือก
$sql_active_jobs = "SELECT r.repair_request_id, r.problem_description, d.device_name, d.device_type 
                    FROM Repair_Request r
                    JOIN IT_Device d ON r.device_id = d.device_id
                    WHERE r.technician_id = '$user_id' AND r.repair_status IN ('กำลังดำเนินการ', 'สั่งซื้ออุปกรณ์')";
$result_active_jobs = mysqli_query($conn, $sql_active_jobs);

echo getSystemHeader("ขอจัดซื้ออะไหล่ - ระบบแจ้งซ่อม");
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

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h2 class="font-extrabold text-3xl text-wix-dark mb-1">ขอจัดซื้ออะไหล่เพิ่มเติม</h2>
                    <p class="text-gray-500 mb-0">ส่งคำร้องให้ฝ่ายจัดซื้อ กรณีที่ต้องเปลี่ยนอุปกรณ์ภายใน เช่น RAM, HDD</p>
                </div>
                <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">กลับหน้าหลัก</a>
            </div>

            <?php echo $message; ?>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden border border-success border-opacity-25">
                <div class="card-body p-4 p-md-5">
                    <form action="request_parts.php" method="POST">
                        
                        <div class="mb-4">
                            <label for="repair_request_id" class="form-label font-medium text-wix-dark">เลือกงานแจ้งซ่อมที่ต้องการใช้อะไหล่ <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg focus:border-wix-purple focus:ring-wix-purple" id="repair_request_id" name="repair_request_id" required>
                                <option value="" disabled selected>-- เลือกงานที่กำลังดำเนินการอยู่ --</option>
                                <?php if(mysqli_num_rows($result_active_jobs) > 0): ?>
                                    <?php while($job = mysqli_fetch_assoc($result_active_jobs)): ?>
                                        <option value="<?php echo $job['repair_request_id']; ?>">
                                            REP-<?php echo str_pad($job['repair_request_id'], 4, '0', STR_PAD_LEFT); ?> : 
                                             <?php echo htmlspecialchars($job['device_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="" disabled>ไม่มีงานที่กำลังดำเนินการ (ต้องรับงานก่อนขอซื้ออะไหล่)</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="parts_detail" class="form-label font-medium text-wix-dark">ระบุอะไหล่ที่ต้องการจัดซื้อ <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-lg focus:border-wix-purple focus:ring-wix-purple" id="parts_detail" name="parts_detail" rows="4" placeholder="ระบุชนิด ยี่ห้อ สเปค หรือจำนวนที่ต้องการ เช่น RAM DDR4 8GB จำนวน 1 ตัว สำหรับเปลี่ยนให้เครื่องนี้" required></textarea>
                            <div class="form-text mt-2 text-muted">
                                <i class="bi bi-info-circle me-1"></i> เมื่อกดยืนยัน สถานะงานซ่อมนี้จะเปลี่ยนเป็น <span class="badge bg-info text-dark">สั่งซื้ออุปกรณ์</span> โดยอัตโนมัติ เพื่อรอฝ่ายจัดซื้อดำเนินการ
                            </div>
                        </div>

                        <div class="d-grid pt-2">
                            <button type="submit" class="btn btn-success btn-lg w-100 shadow-sm rounded-pill" <?php echo (mysqli_num_rows($result_active_jobs) == 0) ? 'disabled' : ''; ?>>
                                <i class="bi bi-cart-plus me-2"></i> ส่งคำร้องขอจัดซื้ออะไหล่
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php echo getSystemFooter(); ?>