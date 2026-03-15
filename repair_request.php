<?php
// ไฟล์: repair_request.php
require_once 'config.php';

// ตรวจสอบการ Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$user_role = $_SESSION['role'];

// จัดการการส่งฟอร์มแจ้งซ่อม (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $device_id = mysqli_real_escape_string($conn, $_POST['device_id']);
    $problem_description = mysqli_real_escape_string($conn, $_POST['problem_description']);
    $repair_location = mysqli_real_escape_string($conn, $_POST['repair_location']);
    $request_date = date('Y-m-d H:i:s');
    $repair_status = 'กำลังดำเนินการ'; // สถานะเริ่มต้น

    // จัดการการอัปโหลดรูปภาพ (ถ้ามี)
    $repair_image = '';
    if (isset($_FILES['repair_image']) && $_FILES['repair_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        
        // ตรวจสอบนามสกุลไฟล์
        if (in_array($_FILES['repair_image']['type'], $allowed_types)) {
            $ext = pathinfo($_FILES['repair_image']['name'], PATHINFO_EXTENSION);
            // ตั้งชื่อไฟล์ใหม่เพื่อป้องกันชื่อซ้ำ
            $new_filename = 'repair_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            
            // สร้างโฟลเดอร์ uploads หากยังไม่มี
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            $upload_path = 'uploads/' . $new_filename;
            
            // ย้ายไฟล์จาก temp ไปยังโฟลเดอร์ uploads
            if (move_uploaded_file($_FILES['repair_image']['tmp_name'], $upload_path)) {
                $repair_image = $new_filename;
            }
        } else {
            $_SESSION['sys_msg'] = 'ไฟล์รูปภาพต้องเป็น JPG หรือ PNG เท่านั้น';
            $_SESSION['sys_msg_type'] = 'danger';
            header("Location: repair_request.php");
            exit();
        }
    }

    // บันทึกข้อมูลลงฐานข้อมูล
    $sql_insert = "INSERT INTO Repair_Request (user_id, device_id, request_date, problem_description, repair_location, repair_image, repair_status) 
                   VALUES ('$user_id', '$device_id', '$request_date', '$problem_description', '$repair_location', '$repair_image', '$repair_status')";
    
    if (mysqli_query($conn, $sql_insert)) {
        // อัปเดตสถานะอุปกรณ์เป็น 'ส่งซ่อม' ด้วย
        $sql_update_device = "UPDATE IT_Device SET device_status = 'ส่งซ่อม' WHERE device_id = '$device_id'";
        mysqli_query($conn, $sql_update_device);

        $_SESSION['sys_msg'] = 'ส่งคำร้องแจ้งซ่อมเรียบร้อยแล้ว ช่างเทคนิคจะดำเนินการตรวจสอบโดยเร็วที่สุด';
        $_SESSION['sys_msg_type'] = 'success';
    } else {
        $_SESSION['sys_msg'] = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn);
        $_SESSION['sys_msg_type'] = 'danger';
    }
    
    // Redirect ป้องกัน Form Resubmission
    header("Location: repair_request.php");
    exit();
}

// จัดการข้อความแจ้งเตือนจาก Session
$message = '';
if (isset($_SESSION['sys_msg'])) {
    $message = '<div class="alert alert-' . $_SESSION['sys_msg_type'] . ' alert-dismissible fade show shadow-sm rounded-3" role="alert">
                    ' . $_SESSION['sys_msg'] . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
    unset($_SESSION['sys_msg']);
    unset($_SESSION['sys_msg_type']);
}

// ดึงรายชื่ออุปกรณ์ที่สามารถแจ้งซ่อมได้ (สถานะต้องไม่ใช่ 'ชำรุด' เพราะเสียถาวรแล้ว)
$sql_devices = "SELECT * FROM IT_Device WHERE device_status != 'ชำรุด' ORDER BY device_name ASC";
$result_devices = mysqli_query($conn, $sql_devices);

echo getSystemHeader("แจ้งซ่อมอุปกรณ์ IT - ระบบแจ้งซ่อม");
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
        <div class="col-md-10 col-lg-8">
            
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h2 class="font-extrabold text-3xl text-wix-dark mb-1">ฟอร์มแจ้งซ่อมอุปกรณ์ IT</h2>
                    <p class="text-gray-500 mb-0">กรอกรายละเอียดปัญหาของคุณเพื่อให้ช่างเทคนิคตรวจสอบ</p>
                </div>
                <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">กลับหน้าหลัก</a>
            </div>

            <?php echo $message; ?>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-body p-4 p-md-5">
                    <form action="repair_request.php" method="POST" enctype="multipart/form-data">
                        
                        <div class="mb-4">
                            <label for="device_id" class="form-label font-medium text-wix-dark">อุปกรณ์ที่ต้องการแจ้งซ่อม <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg focus:border-wix-purple focus:ring-wix-purple" id="device_id" name="device_id" required>
                                <option value="" disabled selected>-- เลือกอุปกรณ์ของคุณ --</option>
                                <?php if(mysqli_num_rows($result_devices) > 0): ?>
                                    <?php while($dev = mysqli_fetch_assoc($result_devices)): ?>
                                        <option value="<?php echo $dev['device_id']; ?>">
                                             <?php echo htmlspecialchars($dev['device_name']); ?> 
                                            (S/N: <?php echo htmlspecialchars($dev['serial_number'] ? $dev['serial_number'] : '-'); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="" disabled>ไม่มีอุปกรณ์ในระบบ (กรุณาติดต่อฝ่ายจัดซื้อ)</option>
                                <?php endif; ?>
                            </select>
                            <div class="form-text mt-2 text-muted">
                                หากไม่พบอุปกรณ์ของคุณในรายการ กรุณาติดต่อฝ่ายจัดซื้อเพื่อเพิ่มข้อมูลเข้าสู่ระบบ
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="repair_location" class="form-label font-medium text-wix-dark">สถานที่/แผนก (ตำแหน่งที่อุปกรณ์ตั้งอยู่ปัจจุบัน) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg focus:border-wix-purple focus:ring-wix-purple" id="repair_location" name="repair_location" placeholder="เช่น แผนกบัญชี ชั้น 2 โต๊ะหมายเลข 5" required>
                        </div>

                        <div class="mb-4">
                            <label for="problem_description" class="form-label font-medium text-wix-dark">รายละเอียดปัญหา/อาการเสีย <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-lg focus:border-wix-purple focus:ring-wix-purple" id="problem_description" name="problem_description" rows="4" placeholder="อธิบายอาการเสียให้ชัดเจน เช่น เปิดไม่ติด, หน้าจอกระพริบ, ปริ้นไม่ออก" required></textarea>
                        </div>

                        <div class="mb-4 bg-gray-50 p-4 rounded-3 border border-gray-100">
                            <label for="repair_image" class="form-label font-medium text-wix-dark">แนบรูปภาพประกอบ (ถ้ามี)</label>
                            <input class="form-control" type="file" id="repair_image" name="repair_image" accept=".jpg, .jpeg, .png">
                            <div class="form-text mt-2 text-muted">รองรับไฟล์ JPG หรือ PNG เท่านั้น (ขนาดไฟล์ไม่เกิน 5MB) ช่วยให้ช่างประเมินอาการได้แม่นยำขึ้น</div>
                        </div>

                        <div class="d-grid pt-2">
                            <button type="submit" class="btn-purple btn-lg w-100 shadow-sm">
                                ส่งคำร้องแจ้งซ่อม
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php echo getSystemFooter(); ?>