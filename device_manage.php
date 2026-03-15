<?php
// ไฟล์: device_manage.php
require_once 'config.php';

// ตรวจสอบสิทธิ์การเข้าถึง (เฉพาะฝ่ายจัดซื้อเท่านั้น)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ฝ่ายจัดซื้อ') {
    echo "<script>alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้'); window.location.href='index.php';</script>";
    exit();
}

$full_name = $_SESSION['full_name'];
$user_role = $_SESSION['role'];

// 1. จัดการการ ลบ ข้อมูล (Delete)
if (isset($_GET['delete_id'])) {
    $del_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $sql_delete = "DELETE FROM IT_Device WHERE device_id = '$del_id'";
    if (mysqli_query($conn, $sql_delete)) {
        $_SESSION['sys_msg'] = 'ลบข้อมูลอุปกรณ์เรียบร้อยแล้ว';
        $_SESSION['sys_msg_type'] = 'success';
    } else {
        $_SESSION['sys_msg'] = 'ไม่สามารถลบได้ เนื่องจากอุปกรณ์นี้อาจถูกผูกกับการแจ้งซ่อมแล้ว';
        $_SESSION['sys_msg_type'] = 'danger';
    }
    // Redirect ป้องกันการ Refresh แล้วส่งค่าซ้ำ
    header("Location: device_manage.php");
    exit();
}

// 2. จัดการการ เพิ่ม (Insert) และ แก้ไข (Update)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $device_id = mysqli_real_escape_string($conn, $_POST['device_id']);
    $device_name = mysqli_real_escape_string($conn, $_POST['device_name']);
    $device_type = mysqli_real_escape_string($conn, $_POST['device_type']);
    $brand = mysqli_real_escape_string($conn, $_POST['brand']);
    $model = mysqli_real_escape_string($conn, $_POST['model']);
    $serial_number = mysqli_real_escape_string($conn, $_POST['serial_number']);
    $purchase_date = mysqli_real_escape_string($conn, $_POST['purchase_date']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $device_status = mysqli_real_escape_string($conn, $_POST['device_status']);

    if (empty($device_id)) {
        // เพิ่มข้อมูลใหม่ (Insert)
        $sql_insert = "INSERT INTO IT_Device (device_name, device_type, brand, model, serial_number, purchase_date, location, device_status) 
                       VALUES ('$device_name', '$device_type', '$brand', '$model', '$serial_number', '$purchase_date', '$location', '$device_status')";
        if (mysqli_query($conn, $sql_insert)) {
            $_SESSION['sys_msg'] = 'เพิ่มข้อมูลอุปกรณ์ใหม่สำเร็จ';
            $_SESSION['sys_msg_type'] = 'success';
        } else {
            $_SESSION['sys_msg'] = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn);
            $_SESSION['sys_msg_type'] = 'danger';
        }
    } else {
        // แก้ไขข้อมูล (Update)
        $sql_update = "UPDATE IT_Device SET 
                        device_name='$device_name', device_type='$device_type', brand='$brand', 
                        model='$model', serial_number='$serial_number', purchase_date='$purchase_date', 
                        location='$location', device_status='$device_status' 
                       WHERE device_id='$device_id'";
        if (mysqli_query($conn, $sql_update)) {
            $_SESSION['sys_msg'] = 'อัปเดตข้อมูลอุปกรณ์สำเร็จ';
            $_SESSION['sys_msg_type'] = 'success';
        } else {
            $_SESSION['sys_msg'] = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn);
            $_SESSION['sys_msg_type'] = 'danger';
        }
    }
    // Redirect ป้องกันการ Refresh แล้วส่งค่าซ้ำ
    header("Location: device_manage.php");
    exit();
}

// 3. จัดการแสดงข้อความแจ้งเตือน (ดึงจาก Session แล้วลบทิ้ง)
$message = '';
if (isset($_SESSION['sys_msg'])) {
    $message = '<div class="alert alert-' . $_SESSION['sys_msg_type'] . ' alert-dismissible fade show" role="alert">
                    ' . $_SESSION['sys_msg'] . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
    // ล้างค่าทิ้งเพื่อไม่ให้แสดงซ้ำเมื่อ Refresh อีกรอบ
    unset($_SESSION['sys_msg']);
    unset($_SESSION['sys_msg_type']);
}

// ดึงข้อมูลอุปกรณ์ทั้งหมดมาแสดง
$sql_fetch = "SELECT * FROM IT_Device ORDER BY device_id DESC";
$result_device = mysqli_query($conn, $sql_fetch);

echo getSystemHeader("จัดการข้อมูลอุปกรณ์ IT - ระบบแจ้งซ่อม");
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

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="font-bold text-wix-dark mb-0">จัดการคลังอุปกรณ์ IT</h3>
            <p class="text-muted text-sm mb-0">เพิ่ม แก้ไข ลบ ข้อมูลอุปกรณ์สำหรับหน่วยงาน</p>
        </div>
        <button type="button" class="btn-purple" data-bs-toggle="modal" data-bs-target="#deviceModal" onclick="clearForm()">
            + เพิ่มอุปกรณ์ใหม่
        </button>
    </div>

    <?php echo $message; ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted text-sm">
                        <tr>
                            <th class="py-3 px-4">รหัส</th>
                            <th class="py-3">ชื่ออุปกรณ์ / รุ่น</th>
                            <th class="py-3">ประเภท</th>
                            <th class="py-3">S/N</th>
                            <th class="py-3">วันที่ซื้อ</th>
                            <th class="py-3">สถานะ</th>
                            <th class="py-3 text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result_device) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result_device)): ?>
                            <tr>
                                <td class="px-4 text-muted">#<?php echo $row['device_id']; ?></td>
                                <td>
                                    <div class="font-semibold text-wix-dark"><?php echo htmlspecialchars($row['device_name']); ?></div>
                                    <div class="text-xs text-muted"><?php echo htmlspecialchars($row['brand'] . ' ' . $row['model']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($row['device_type']); ?></td>
                                <td class="text-muted text-sm"><?php echo htmlspecialchars($row['serial_number']); ?></td>
                                <td class="text-sm"><?php echo ThaiDate($row['purchase_date']); ?></td>
                                <td>
                                    <?php 
                                        $badge_class = 'bg-secondary';
                                        if($row['device_status'] == 'ใช้งานปกติ') $badge_class = 'bg-success';
                                        if($row['device_status'] == 'ส่งซ่อม') $badge_class = 'bg-warning text-dark';
                                        if($row['device_status'] == 'ชำรุด') $badge_class = 'bg-danger';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?> rounded-pill fw-normal px-2 py-1">
                                        <?php echo htmlspecialchars($row['device_status']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1" 
                                        onclick="editDevice(
                                            '<?php echo $row['device_id']; ?>',
                                            '<?php echo addslashes($row['device_name']); ?>',
                                            '<?php echo addslashes($row['device_type']); ?>',
                                            '<?php echo addslashes($row['brand']); ?>',
                                            '<?php echo addslashes($row['model']); ?>',
                                            '<?php echo addslashes($row['serial_number']); ?>',
                                            '<?php echo date('Y-m-d', strtotime($row['purchase_date'])); ?>',
                                            '<?php echo addslashes($row['location']); ?>',
                                            '<?php echo addslashes($row['device_status']); ?>'
                                        )">แก้ไข</button>
                                    
                                    <a href="device_manage.php?delete_id=<?php echo $row['device_id']; ?>" 
                                       class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                       onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบอุปกรณ์นี้?');">ลบ</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">ยังไม่มีข้อมูลอุปกรณ์ในระบบ</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deviceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-light border-0 px-4 py-3 rounded-top-4">
                <h5 class="modal-title font-bold text-wix-dark" id="modalTitle">เพิ่มอุปกรณ์ใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="device_manage.php" method="POST">
                <div class="modal-body px-4 py-4">
                    <input type="hidden" id="device_id" name="device_id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-sm font-semibold text-muted">ชื่ออุปกรณ์ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="device_name" name="device_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-sm font-semibold text-muted">ประเภทอุปกรณ์ <span class="text-danger">*</span></label>
                            <select class="form-select" id="device_type" name="device_type" required>
                                <option value="" disabled selected>เลือกประเภท</option>
                                <option value="คอมพิวเตอร์ (PC)">คอมพิวเตอร์ (PC)</option>
                                <option value="โน้ตบุ๊ก (Notebook)">โน้ตบุ๊ก (Notebook)</option>
                                <option value="เครื่องพิมพ์ (Printer)">เครื่องพิมพ์ (Printer)</option>
                                <option value="จอภาพ (Monitor)">จอภาพ (Monitor)</option>
                                <option value="อุปกรณ์เครือข่าย (Network)">อุปกรณ์เครือข่าย (Network)</option>
                                <option value="อื่นๆ">อื่นๆ</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label text-sm font-semibold text-muted">ยี่ห้อ (Brand)</label>
                            <input type="text" class="form-control" id="brand" name="brand">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-sm font-semibold text-muted">รุ่น (Model)</label>
                            <input type="text" class="form-control" id="model" name="model">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-sm font-semibold text-muted">Serial Number</label>
                            <input type="text" class="form-control" id="serial_number" name="serial_number">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-sm font-semibold text-muted">วันที่สั่งซื้อ</label>
                            <input type="date" class="form-control" id="purchase_date" name="purchase_date">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-sm font-semibold text-muted">สถานที่/แผนกที่จัดเก็บ</label>
                            <input type="text" class="form-control" id="location" name="location">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-sm font-semibold text-muted">สถานะอุปกรณ์ <span class="text-danger">*</span></label>
                            <select class="form-select" id="device_status" name="device_status" required>
                                <option value="ใช้งานปกติ" selected>ใช้งานปกติ</option>
                                <option value="ส่งซ่อม">ส่งซ่อม</option>
                                <option value="ชำรุด">ชำรุด (ใช้งานไม่ได้)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn-purple px-4">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // ฟังก์ชันเคลียร์ฟอร์มตอนกด "เพิ่มอุปกรณ์ใหม่"
    function clearForm() {
        document.getElementById('modalTitle').innerText = 'เพิ่มอุปกรณ์ใหม่';
        document.getElementById('device_id').value = '';
        document.getElementById('device_name').value = '';
        document.getElementById('device_type').value = '';
        document.getElementById('brand').value = '';
        document.getElementById('model').value = '';
        document.getElementById('serial_number').value = '';
        document.getElementById('purchase_date').value = '';
        document.getElementById('location').value = '';
        document.getElementById('device_status').value = 'ใช้งานปกติ';
    }

    // ฟังก์ชันดึงข้อมูลมาใส่ฟอร์มตอนกด "แก้ไข"
    function editDevice(id, name, type, brand, model, serial, date, loc, status) {
        document.getElementById('modalTitle').innerText = 'แก้ไขข้อมูลอุปกรณ์ (ID: ' + id + ')';
        document.getElementById('device_id').value = id;
        document.getElementById('device_name').value = name;
        document.getElementById('device_type').value = type;
        document.getElementById('brand').value = brand;
        document.getElementById('model').value = model;
        document.getElementById('serial_number').value = serial;
        
        // จัดการกรณีวันที่ว่าง
        if(date === '1970-01-01' || date === '') {
            document.getElementById('purchase_date').value = '';
        } else {
            document.getElementById('purchase_date').value = date;
        }
        
        document.getElementById('location').value = loc;
        document.getElementById('device_status').value = status;
        
        // เปิด Modal
        var myModal = new bootstrap.Modal(document.getElementById('deviceModal'));
        myModal.show();
    }
</script>

<?php echo getSystemFooter(); ?>