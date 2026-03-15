<?php
// ไฟล์: order_device.php
require_once 'config.php';

// ตรวจสอบการ Login และสิทธิ์ฝ่ายจัดซื้อ
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ฝ่ายจัดซื้อ') {
    echo "<script>alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้ เฉพาะฝ่ายจัดซื้อเท่านั้น'); window.location.href='index.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// จัดการการส่งฟอร์มสั่งซื้อ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $repair_request_id = mysqli_real_escape_string($conn, $_POST['repair_request_id']);
    $order_number = mysqli_real_escape_string($conn, $_POST['order_number']);
    $vendor_name = mysqli_real_escape_string($conn, $_POST['vendor_name']);
    
    // รับข้อมูล: เหตุผล และ รายการอุปกรณ์ที่สั่งจริง (เพื่อบันทึกลง item_name_custom)
    $purchase_reason = mysqli_real_escape_string($conn, $_POST['purchase_reason']); 
    $item_name_ordered = mysqli_real_escape_string($conn, $_POST['item_name_ordered']); 
    
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];
    $total_price = $quantity * $price;
    $order_date = date('Y-m-d H:i:s');
    
    $po_id = 'PO-' . date('Ymd') . '-' . rand(1000, 9999);
    $poi_id = 'POI-' . date('Ymd') . '-' . rand(1000, 9999);

    // 1. ดึง device_id ตั้งต้นจากใบแจ้งซ่อม
    $sql_get_device = "SELECT device_id FROM Repair_Request WHERE repair_request_id = '$repair_request_id'";
    $res_device = mysqli_query($conn, $sql_get_device);
    $device_id = 0;
    if($row_dev = mysqli_fetch_assoc($res_device)) {
        $device_id = $row_dev['device_id'];
    }

    mysqli_begin_transaction($conn);
    try {
        // 2. Insert ลงตาราง Purchase_Order
        $sql_po = "INSERT INTO Purchase_Order (purchase_order_id, repair_request_id, purchaser_id, order_date, order_number, total_price, vendor_name, purchase_reason) 
                   VALUES ('$po_id', '$repair_request_id', '$user_id', '$order_date', '$order_number', '$total_price', '$vendor_name', '$purchase_reason')";
        mysqli_query($conn, $sql_po);

        // 3. Insert ลงตาราง Purchase_Order_Item (เพิ่มการบันทึก item_name_custom เพื่อให้รายงานแสดงผลถูกต้อง)
        $sql_poi = "INSERT INTO Purchase_Order_Item (purchase_order_item_id, purchase_order_id, device_id, item_name_custom, quantity, price) 
                    VALUES ('$poi_id', '$po_id', '$device_id', '$item_name_ordered', '$quantity', '$price')";
        mysqli_query($conn, $sql_poi);

        // 4. อัปเดตรายละเอียด Log ในใบแจ้งซ่อมเพื่อให้ช่างทราบ
        $append_text = "\n\n[ระบบจัดซื้อ]: ได้ดำเนินการสั่งซื้อ $item_name_ordered (เลขที่ PO: $order_number) เรียบร้อยแล้ว";
        mysqli_query($conn, "UPDATE Repair_Request SET problem_description = CONCAT(problem_description, '$append_text') WHERE repair_request_id = '$repair_request_id'");

        mysqli_commit($conn);
        $_SESSION['sys_msg'] = 'บันทึกข้อมูลการสั่งซื้อเรียบร้อยแล้ว';
        $_SESSION['sys_msg_type'] = 'success';
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['sys_msg'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $_SESSION['sys_msg_type'] = 'danger';
    }
    header("Location: order_device.php");
    exit();
}

// จัดการข้อความแจ้งเตือน
$message = '';
if (isset($_SESSION['sys_msg'])) {
    $message = '<div class="alert alert-' . $_SESSION['sys_msg_type'] . ' alert-dismissible fade show shadow-sm rounded-3" role="alert">
                    ' . $_SESSION['sys_msg'] . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
    unset($_SESSION['sys_msg']); unset($_SESSION['sys_msg_type']);
}

// ดึงรายการที่รอจัดซื้อ
$sql_pending_orders = "
    SELECT r.repair_request_id, r.problem_description, r.repair_status, d.device_name, 
           u.full_name as requester_name, t.full_name as tech_name
    FROM Repair_Request r
    JOIN IT_Device d ON r.device_id = d.device_id
    LEFT JOIN User u ON r.user_id = u.user_id
    LEFT JOIN User t ON r.technician_id = t.user_id
    WHERE r.repair_status IN ('สั่งซื้ออุปกรณ์', 'ไม่สามารถซ่อมได้')
    AND r.repair_request_id NOT IN (SELECT repair_request_id FROM Purchase_Order WHERE repair_request_id IS NOT NULL)
";
$result_pending_orders = mysqli_query($conn, $sql_pending_orders);

echo getSystemHeader("สั่งซื้ออุปกรณ์ - ระบบแจ้งซ่อม");
?>

<div class="bg-white shadow-sm sticky-top w-100" style="z-index: 1020;">
    <div class="container d-flex justify-content-between align-items-center py-3">
        <a href="index.php" class="text-decoration-none" style="font-size: 1.5rem; font-weight: 700; color: #1a1a2e;">
            IT<span style="color: #9b51e0;">Repair</span>
        </a>
        <div class="d-flex align-items-center" style="gap: 10px;">
            <span class="text-muted d-none d-md-inline me-2">เจ้าหน้าที่จัดซื้อ: <?php echo htmlspecialchars($full_name); ?></span>
            <a href="profile.php" class="btn text-white px-4 btn-sm" style="background-color: #9b51e0; border-radius: 50px;">ข้อมูลส่วนตัว</a>
            <a href="logout.php" class="btn btn-outline-danger px-4 btn-sm" style="border-radius: 50px;">ออกจากระบบ</a>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h2 class="font-extrabold text-3xl text-wix-dark">สั่งซื้ออุปกรณ์ / อะไหล่</h2>
                <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">กลับหน้าหลัก</a>
            </div>

            <?php echo $message; ?>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-body p-4 p-md-5 border-top border-4 border-primary">
                    <form action="order_device.php" method="POST">
                        <h5 class="font-bold text-wix-purple mb-3 border-bottom pb-2">อ้างอิงใบแจ้งซ่อม</h5>
                        <div class="mb-3">
                            <label class="form-label font-bold text-wix-dark">เลือกงานที่ต้องการสั่งซื้อ <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" name="repair_request_id" onchange="showDescription(this)" required>
                                <option value="" disabled selected data-desc="">-- เลือกรายการที่รอจัดซื้อ --</option>
                                <?php while($req = mysqli_fetch_assoc($result_pending_orders)): 
                                    $req_by = ($req['repair_status'] == 'สั่งซื้ออุปกรณ์') ? 'ช่าง: '.$req['tech_name'] : 'ผู้ใช้: '.$req['requester_name'];
                                ?>
                                    <option value="<?php echo $req['repair_request_id']; ?>" data-desc="<?php echo htmlspecialchars($req['problem_description']); ?>">
                                        [<?php echo $req['repair_status']; ?>] REP-<?php echo $req['repair_request_id']; ?> : <?php echo htmlspecialchars($req['device_name']); ?> (<?php echo $req_by; ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div id="desc_box" class="alert alert-info border-0 bg-info bg-opacity-10 d-none mb-4 shadow-sm">
                            <strong><i class="bi bi-chat-left-text me-2"></i>รายละเอียดความต้องการ (จากช่าง/ผู้ใช้):</strong>
                            <div id="desc_text" class="mt-2 text-sm" style="white-space: pre-wrap;"></div>
                        </div>

                        <h5 class="font-bold text-wix-purple mb-3 border-bottom pb-2 mt-4">ข้อมูลการจัดซื้อ (PO)</h5>

                        <div class="mb-3">
                            <label class="form-label font-bold text-primary">อุปกรณ์ที่สั่งซื้อจริง (Item Name) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg border-primary" name="item_name_ordered" placeholder="เช่น RAM DDR4 16GB (ยี่ห้อ Kingston)" required>
                            <small class="text-muted">ชื่ออุปกรณ์นี้จะไปแสดงในช่อง 'รายการสินค้า' ในหน้ารายงาน</small>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6"><label class="form-label font-bold">เลขที่ใบสั่งซื้อ</label><input type="text" class="form-control" name="order_number" required></div>
                            <div class="col-md-6"><label class="form-label font-bold">ร้านค้า</label><input type="text" class="form-control" name="vendor_name" required></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-bold">เหตุผลความจำเป็นในการซื้อ <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="purchase_reason" rows="2" placeholder="เช่น อะไหล่เดิมเสียซ่อมไม่ได้" required></textarea>
                        </div>

                        <div class="row mb-4">
                            <div class="col-6"><label class="form-label font-bold">จำนวน</label><input type="number" class="form-control" name="quantity" value="1" min="1" required></div>
                            <div class="col-6"><label class="form-label font-bold">ราคาต่อหน่วย</label><input type="number" step="0.01" class="form-control" name="price" required></div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill shadow-sm">
                            <i class="bi bi-check2-circle me-2"></i> บันทึกใบสั่งซื้อ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function showDescription(selectElement) {
        var selectedOption = selectElement.options[selectElement.selectedIndex];
        var descText = selectedOption.getAttribute('data-desc');
        var descBox = document.getElementById('desc_box');
        var descTextContainer = document.getElementById('desc_text');
        
        if (descText && descText.trim() !== "") {
            descTextContainer.innerText = descText;
            descBox.classList.remove('d-none');
        } else {
            descBox.classList.add('d-none');
        }
    }
</script>

<?php echo getSystemFooter(); ?>