<?php
// ไฟล์: direct_order.php
require_once 'config.php';

// 1. ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ฝ่ายจัดซื้อ') {
    echo "<script>alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้'); window.location.href='index.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// 2. จัดการการส่งฟอร์ม (POST Action)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // - เลข PO ควรระบุรูปแบบ
    $order_number = mysqli_real_escape_string($conn, $_POST['order_number']);
    if (!preg_match('/^PO-\d{4}-\d{3,4}$/', $order_number)) {
        $_SESSION['sys_msg'] = 'รูปแบบเลข PO ไม่ถูกต้อง เช่น PO-2026-0001'; 
        $_SESSION['sys_msg_type'] = 'danger';
        header("Location: direct_order.php"); 
        exit();
    }
    
    $vendor_name = mysqli_real_escape_string($conn, $_POST['vendor_name']);
    $purchase_reason = mysqli_real_escape_string($conn, $_POST['purchase_reason']);
    $order_date = date('Y-m-d H:i:s');
    
    $po_id = 'PO-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // รับข้อมูลอุปกรณ์ครบถ้วน
    $item_names = isset($_POST['item_name']) ? $_POST['item_name'] : array();
    $device_types = isset($_POST['device_type']) ? $_POST['device_type'] : array();
    $brands = isset($_POST['brand']) ? $_POST['brand'] : array();
    $models = isset($_POST['model']) ? $_POST['model'] : array();
    $serial_nos = isset($_POST['serial_number']) ? $_POST['serial_number'] : array();
    $locations = isset($_POST['location']) ? $_POST['location'] : array();
    $quantities = isset($_POST['quantity']) ? $_POST['quantity'] : array();
    $prices = isset($_POST['price']) ? $_POST['price'] : array();
    
    $total_price = 0;
    for ($i = 0; $i < count($item_names); $i++) {
        $total_price += ($quantities[$i] * $prices[$i]);
    }

    // ตรวจสอบเลขที่ PO ซ้ำ
    $check_po = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM Purchase_Order WHERE order_number = '$order_number'");
    $po_count = mysqli_fetch_assoc($check_po)['cnt'];
    if ($po_count > 0) {
        $_SESSION['sys_msg'] = 'เลขที่ PO ' . $order_number . ' มีในระบบแล้ว กรุณาตรวจสอบ';
        $_SESSION['sys_msg_type'] = 'danger';
        header("Location: direct_order.php"); 
        exit();
    }

    // ตรวจสอบ Serial Number ซ้ำ
    for ($i = 0; $i < count($serial_nos); $i++) {
        if (!empty($serial_nos[$i])) {
            $serial_no = mysqli_real_escape_string($conn, $serial_nos[$i]);
            $check_serial = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM IT_Device WHERE serial_number = '$serial_no'");
            $serial_count = mysqli_fetch_assoc($check_serial)['cnt'];
            if ($serial_count > 0) {
                $_SESSION['sys_msg'] = 'Serial Number ' . $serial_nos[$i] . ' (รายการที่ ' . ($i + 1) . ') มีในระบบแล้ว กรุณาตรวจสอบ';
                $_SESSION['sys_msg_type'] = 'danger';
                header("Location: direct_order.php"); 
                exit();
            }
        }
    }

    mysqli_begin_transaction($conn);
    try {
        // 2.1 Insert ลง Purchase_Order
        $sql_po = "INSERT INTO Purchase_Order (purchase_order_id, repair_request_id, purchaser_id, order_date, order_number, total_price, vendor_name, purchase_reason) 
                   VALUES ('$po_id', NULL, '$user_id', '$order_date', '$order_number', '$total_price', '$vendor_name', '$purchase_reason')";
        mysqli_query($conn, $sql_po);

        // 2.2 วนลูป Insert รายการสินค้า (ไม่สร้าง IT_Device ตอนนี้ เพราะต้องรอกด "รับของเข้า")
        for ($i = 0; $i < count($item_names); $i++) {
            $name = mysqli_real_escape_string($conn, $item_names[$i]);
            $d_type = mysqli_real_escape_string($conn, $device_types[$i]);
            $brand = mysqli_real_escape_string($conn, $brands[$i]);
            $model = mysqli_real_escape_string($conn, $models[$i]);
            $serial_no = mysqli_real_escape_string($conn, $serial_nos[$i]);
            $location = mysqli_real_escape_string($conn, $locations[$i]);
            $qty = (int)$quantities[$i];
            $prc = (float)$prices[$i];
            $poi_id = 'POI-' . date('Ymd') . '-' . rand(100, 999) . $i;
            
            // เก็บ device_id = 0 ตอนนี้ (ยังไม่สร้าง Device)
            // จะสร้าง Device เมื่อกด "รับของเข้า" ใน report_purchase.php
            $sql_poi = "INSERT INTO Purchase_Order_Item (purchase_order_item_id, purchase_order_id, device_id, brand, device_type, model, serial_number, location, item_name_custom, quantity, price) 
                        VALUES ('$poi_id', '$po_id', 0, '$brand', '$d_type', '$model', '$serial_no', '$location', '$name', '$qty', '$prc')";
            if (!mysqli_query($conn, $sql_poi)) {
                throw new Exception("ไม่สามารถบันทึกรายการอุปกรณ์: " . mysqli_error($conn));
            }
        }

        mysqli_commit($conn);
        $_SESSION['sys_msg'] = 'บันทึกใบสั่งซื้อเลขที่ ' . $order_number . ' เรียบร้อยแล้ว'; 
        $_SESSION['sys_msg_type'] = 'success';
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['sys_msg'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage(); 
        $_SESSION['sys_msg_type'] = 'danger';
    }
    header("Location: direct_order.php"); 
    exit();
}

echo getSystemHeader("สั่งซื้ออุปกรณ์ทั่วไป - IT Repair");
?>

<nav class="bg-white shadow-sm sticky-top w-100 mb-4">
    <div class="container d-flex justify-content-between align-items-center py-3">
        <a href="index.php" class="text-decoration-none font-bold text-2xl" style="color: #1a1a2e;">
            IT<span style="color: #9b51e0;">Repair</span>
        </a>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted d-none d-md-inline text-sm">ผู้ใช้งาน: <strong><?php echo htmlspecialchars($full_name); ?></strong></span>
            <a href="profile.php" class="btn btn-purple btn-sm rounded-pill px-4">ข้อมูลส่วนตัว</a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-4">ออกจากระบบ</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="font-bold text-wix-dark">สร้างใบสั่งซื้อแบบอิสระ (Direct PO)</h3>
        <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">กลับหน้าหลัก</a>
    </div>

    <?php if(isset($_SESSION['sys_msg'])): ?>
        <div class="alert alert-<?php echo $_SESSION['sys_msg_type']; ?> alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <i class="bi <?php echo ($_SESSION['sys_msg_type'] == 'success') ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
            <?php echo $_SESSION['sys_msg']; unset($_SESSION['sys_msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-body p-4 p-md-5 border-top border-4 border-success">
            <form action="direct_order.php" method="POST">
                <h5 class="font-bold text-dark mb-4"><i class="bi bi-file-earmark-plus me-2"></i>ข้อมูลใบสั่งซื้อ</h5>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label font-bold">เลขที่ PO <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="order_number" placeholder="รูปแบบ: PO-YYYY-XXXX (เช่น PO-2026-0001)" pattern="^PO-\d{4}-\d{3,4}$" required>
                        <small class="text-muted">ตัวอย่าง: PO-2026-0001, PO-2026-001</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-bold">ร้านค้า <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="vendor_name" placeholder="ชื่อร้านค้า" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-bold">เหตุผลความจำเป็น <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="purchase_reason" placeholder="เช่น ซื้ออะไหล่สำรอง" required>
                    </div>
                </div>

                <h5 class="font-bold text-success mb-3"><i class="bi bi-cart-plus me-2"></i>รายการอุปกรณ์ที่สั่งซื้อ</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="bg-light text-center text-muted text-xs text-uppercase">
                            <tr>
                                <th>ชื่ออุปกรณ์ <span class="text-danger">*</span></th>
                                <th width="11%">ประเภท <span class="text-danger">*</span></th>
                                <th width="10%">ยี่ห้อ</th>
                                <th width="9%">รุ่น</th>
                                <th width="11%">Serial No</th>
                                <th width="9%">จำนวน</th>
                                <th width="11%">ราคา/หน่วย (บาท)</th>
                                <th width="11%">สถานที่</th>
                                <th width="7%">ลบ</th>
                            </tr>
                        </thead>
                        <tbody id="itemBody">
                            <tr class="item-row">
                                <td><input type="text" class="form-control form-control-sm" name="item_name[]" placeholder="ชื่ออุปกรณ์" required></td>
                                <td>
                                    <select class="form-select form-select-sm" name="device_type[]">
                                        <option value="">เลือก</option>
                                        <option value="คอมพิวเตอร์ (PC)">PC</option>
                                        <option value="โน้ตบุ๊ก (Notebook)">Notebook</option>
                                        <option value="เครื่องพิมพ์ (Printer)">Printer</option>
                                        <option value="จอภาพ (Monitor)">Monitor</option>
                                        <option value="อุปกรณ์เครือข่าย (Network)">Network</option>
                                        <option value="อื่นๆ">อื่นๆ</option>
                                    </select>
                                </td>
                                <td><input type="text" class="form-control form-control-sm" name="brand[]" placeholder="Brand" style="font-size: 0.85rem;"></td>
                                <td><input type="text" class="form-control form-control-sm" name="model[]" placeholder="Model" style="font-size: 0.85rem;"></td>
                                <td><input type="text" class="form-control form-control-sm" name="serial_number[]" placeholder="S/N" style="font-size: 0.85rem;"></td>
                                <td><input type="number" class="form-control form-control-sm text-center" name="quantity[]" min="1" value="1" required></td>
                                <td><input type="number" step="0.01" class="form-control form-control-sm text-end" name="price[]" placeholder="0.00" required style="font-size: 0.85rem;"></td>
                                <td><input type="text" class="form-control form-control-sm" name="location[]" placeholder="แผนก" style="font-size: 0.85rem;"></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="this.closest('tr').remove();" title="ลบรายการ">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3 mb-5">
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-4" onclick="addRow()">
                        <i class="bi bi-plus-lg me-1"></i> เพิ่มรายการสินค้า
                    </button>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill shadow-sm py-3">
                    <i class="bi bi-save me-2"></i> บันทึกใบสั่งซื้อทั้งหมด
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function addRow() {
        const tbody = document.getElementById('itemBody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" class="form-control form-control-sm" name="item_name[]" placeholder="ชื่ออุปกรณ์" required></td>
            <td>
                <select class="form-select form-select-sm" name="device_type[]">
                    <option value="">เลือก</option>
                    <option value="คอมพิวเตอร์ (PC)">PC</option>
                    <option value="โน้ตบุ๊ก (Notebook)">Notebook</option>
                    <option value="เครื่องพิมพ์ (Printer)">Printer</option>
                    <option value="จอภาพ (Monitor)">Monitor</option>
                    <option value="อุปกรณ์เครือข่าย (Network)">Network</option>
                    <option value="อื่นๆ">อื่นๆ</option>
                </select>
            </td>
            <td><input type="text" class="form-control form-control-sm" name="brand[]" placeholder="Brand" style="font-size: 0.85rem;"></td>
            <td><input type="text" class="form-control form-control-sm" name="model[]" placeholder="Model" style="font-size: 0.85rem;"></td>
            <td><input type="text" class="form-control form-control-sm" name="serial_number[]" placeholder="S/N" style="font-size: 0.85rem;"></td>
            <td><input type="number" class="form-control form-control-sm text-center" name="quantity[]" min="1" value="1" required></td>
            <td><input type="number" step="0.01" class="form-control form-control-sm text-end" name="price[]" placeholder="0.00" required style="font-size: 0.85rem;"></td>
            <td><input type="text" class="form-control form-control-sm" name="location[]" placeholder="แผนก" style="font-size: 0.85rem;"></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="this.closest('tr').remove();" title="ลบรายการ">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    }
</script>

<?php echo getSystemFooter(); ?>