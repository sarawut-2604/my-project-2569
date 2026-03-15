<?php
// ไฟล์: request_new_device.php
require_once 'config.php';

// ตรวจสอบการ Login และสิทธิ์ผู้ใช้งานทั่วไป
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ผู้ใช้งานทั่วไป') {
    echo "<script>alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้ เฉพาะผู้ใช้งานทั่วไปเท่านั้น'); window.location.href='index.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// จัดการการส่งฟอร์มขอจัดซื้อเครื่องใหม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $req_id = mysqli_real_escape_string($conn, $_POST['repair_request_id']);
    $request_reason = mysqli_real_escape_string($conn, $_POST['request_reason']);
    
    // นำเหตุผลที่ขอซื้อใหม่ไปต่อท้ายใน problem_description เพื่อเป็นบันทึกถึงฝ่ายจัดซื้อ
    $append_text = "\n\n[คำร้องขอจัดซื้อเครื่องใหม่ทดแทนจากผู้ใช้งาน]: " . $request_reason;
    
    $sql_update = "UPDATE Repair_Request 
                   SET problem_description = CONCAT(problem_description, '$append_text'),
                       pur_noti_read = 0
                   WHERE repair_request_id = '$req_id' AND user_id = '$user_id'";
                   
    if (mysqli_query($conn, $sql_update)) {
        $_SESSION['sys_msg'] = 'ส่งคำร้องขอจัดซื้ออุปกรณ์ใหม่ทดแทนเรียบร้อยแล้ว กรุณารอฝ่ายจัดซื้อดำเนินการ';
        $_SESSION['sys_msg_type'] = 'success';
    } else {
        $_SESSION['sys_msg'] = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn);
        $_SESSION['sys_msg_type'] = 'danger';
    }
    
    header("Location: request_new_device.php");
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

// ดึงรายการงานซ่อมของผู้ใช้คนนี้ ที่สถานะเป็น "ไม่สามารถซ่อมได้" 
// (และใช้ LIKE กรองว่ายังไม่เคยส่งคำร้องเข้าไปต่อท้าย เพื่อป้องกันการกดส่งซ้ำซ้อน)
$sql_unrepairable = "SELECT r.repair_request_id, r.problem_description, d.device_name, d.device_type 
                    FROM Repair_Request r
                    JOIN IT_Device d ON r.device_id = d.device_id
                    WHERE r.user_id = '$user_id' 
                    AND r.repair_status = 'ไม่สามารถซ่อมได้'
                    AND r.problem_description NOT LIKE '%[คำร้องขอจัดซื้อเครื่องใหม่ทดแทนจากผู้ใช้งาน]%'";
                    
$result_unrepairable = mysqli_query($conn, $sql_unrepairable);

echo getSystemHeader("ขอจัดซื้ออุปกรณ์ใหม่ - ระบบแจ้งซ่อม");
?>

<div class="bg-white shadow-sm sticky-top w-100" style="z-index: 1020;">
    <div class="container d-flex justify-content-between align-items-center py-3">
        <a href="index.php" class="text-decoration-none" style="font-size: 1.5rem; font-weight: 700; color: #1a1a2e;">
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
    <div class="row justify-content-center">
        <div class="col-md-8">
            
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h2 class="font-extrabold text-3xl text-wix-dark mb-1">ขอจัดซื้ออุปกรณ์ใหม่ทดแทน</h2>
                    <p class="text-gray-500 mb-0">ส่งคำร้องไปยังฝ่ายจัดซื้อ (เฉพาะอุปกรณ์ที่ช่างแจ้งว่า "ไม่สามารถซ่อมได้")</p>
                </div>
                <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">กลับหน้าหลัก</a>
            </div>

            <?php echo $message; ?>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden border border-danger border-opacity-25">
                <div class="card-body p-4 p-md-5">
                    
                    <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-dark mb-4 rounded-3 d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill fs-4 text-warning me-3"></i>
                        <div>
                            <strong>เงื่อนไขการใช้งาน:</strong> คุณสามารถส่งคำร้องนี้ได้ก็ต่อเมื่อ อุปกรณ์ของคุณถูกช่างเทคนิคประเมินสถานะว่า <span class="badge bg-danger">ไม่สามารถซ่อมได้</span> เท่านั้น
                        </div>
                    </div>

                    <form action="request_new_device.php" method="POST">
                        
                        <div class="mb-4">
                            <label for="repair_request_id" class="form-label font-medium text-wix-dark">เลือกอุปกรณ์ที่ต้องการขอทดแทน <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg focus:border-wix-purple focus:ring-wix-purple" id="repair_request_id" name="repair_request_id" required>
                                <option value="" disabled selected>-- เลือกอุปกรณ์ที่ซ่อมไม่ได้ --</option>
                                <?php if(mysqli_num_rows($result_unrepairable) > 0): ?>
                                    <?php while($item = mysqli_fetch_assoc($result_unrepairable)): ?>
                                        <option value="<?php echo $item['repair_request_id']; ?>">
                                            REP-<?php echo str_pad($item['repair_request_id'], 4, '0', STR_PAD_LEFT); ?> : 
                                            [<?php echo htmlspecialchars($item['device_type']); ?>] <?php echo htmlspecialchars($item['device_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="" disabled>ไม่มีอุปกรณ์ที่เข้าเงื่อนไข (ยังไม่มีเครื่องที่ถูกประเมินว่าซ่อมไม่ได้)</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="request_reason" class="form-label font-medium text-wix-dark">เหตุผลและสเปคเครื่องใหม่ที่ต้องการ <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-lg focus:border-wix-purple focus:ring-wix-purple" id="request_reason" name="request_reason" rows="4" placeholder="ระบุเหตุผลในการขอซื้อทดแทน เช่น ขอสเปคเดิม, หรือขออัปเกรดสเปคเพื่อรองรับงานกราฟิก พร้อมระบุโปรแกรมที่ต้องใช้ประจำ" required></textarea>
                            <div class="form-text mt-2 text-muted">
                                คำร้องของคุณจะถูกส่งไปรวมกับประวัติการแจ้งซ่อม เพื่อให้ฝ่ายจัดซื้อพิจารณาออกใบสั่งซื้อต่อไป
                            </div>
                        </div>

                        <div class="d-grid pt-2">
                            <button type="submit" class="btn btn-danger btn-lg w-100 shadow-sm rounded-pill" <?php echo (mysqli_num_rows($result_unrepairable) == 0) ? 'disabled' : ''; ?>>
                                <i class="bi bi-box-seam me-2"></i> ส่งคำร้องขอจัดซื้อเครื่องใหม่
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php echo getSystemFooter(); ?>