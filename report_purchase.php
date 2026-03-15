<?php
// ไฟล์: report_purchase.php
require_once 'config.php';

// 1. ตรวจสอบสิทธิ์ (Security Check)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ฝ่ายจัดซื้อ') {
    echo "<script>alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้ เฉพาะฝ่ายจัดซื้อเท่านั้น'); window.location.href='index.php';</script>";
    exit();
}

// --- ส่วนที่เพิ่มใหม่: Logic สำหรับการรับอุปกรณ์เข้าคลัง ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'receive_item') {
    $po_id_received = mysqli_real_escape_string($conn, $_POST['po_id']);
    
    mysqli_begin_transaction($conn);
    try {
        // 1. ดึงรายการอุปกรณ์จาก Purchase_Order_Item
        $sql_items = "SELECT * FROM Purchase_Order_Item WHERE purchase_order_id = '$po_id_received'";
        $result_items = mysqli_query($conn, $sql_items);
        while($item = mysqli_fetch_assoc($result_items)) {
            if ($item['device_id'] == 0 || $item['device_id'] == '') {
                // ไม่มี device_id ให้สร้างใหม่
                $device_id_new = 'DEV-' . date('Ymd') . '-' . rand(10000, 99999);
                $item_name = mysqli_real_escape_string($conn, $item['item_name_custom']);
                $item_type = mysqli_real_escape_string($conn, $item['device_type'] ?? '');
                $item_brand = mysqli_real_escape_string($conn, $item['brand'] ?? '');
                $item_model = mysqli_real_escape_string($conn, $item['model'] ?? '');
                $item_serial = mysqli_real_escape_string($conn, $item['serial_number'] ?? '');
                $item_location = mysqli_real_escape_string($conn, $item['location'] ?? '');
                
                // สร้าง IT_Device ครบถ้วน (รวม device_type, purchase_date, brand, model, serial_number, location)
                $sql_new_device = "INSERT INTO IT_Device (device_id, device_name, device_type, brand, model, serial_number, purchase_date, location, device_status) 
                                   VALUES ('$device_id_new', '$item_name', '$item_type', '$item_brand', '$item_model', '$item_serial', CURDATE(), '$item_location', 'ใช้งานปกติ')";
                if (!mysqli_query($conn, $sql_new_device)) {
                    throw new Exception("ไม่สามารถสร้างอุปกรณ์: " . mysqli_error($conn));
                }
                // อัปเดต device_id ใน Purchase_Order_Item
                mysqli_query($conn, "UPDATE Purchase_Order_Item SET device_id = '$device_id_new' WHERE purchase_order_item_id = '{$item['purchase_order_item_id']}'");
            }
        }
        
        // 2. อัปเดตสถานะใน Purchase_Order
        mysqli_query($conn, "UPDATE Purchase_Order SET po_status = 'ได้รับของแล้ว' WHERE purchase_order_id = '$po_id_received'");

        // 3. ดึงข้อมูลเพื่อไปอัปเดต Log ใน Repair_Request
        $sql_info = "SELECT repair_request_id, order_number FROM Purchase_Order WHERE purchase_order_id = '$po_id_received'";
        $res_info = mysqli_query($conn, $sql_info);
        if($info = mysqli_fetch_assoc($res_info)) {
            $rep_id = $info['repair_request_id'];
            if($rep_id) {
                $update_msg = "\n\n[คลังสินค้า]: ได้รับอุปกรณ์ตาม PO: ".$info['order_number']." เรียบร้อยแล้ว (พร้อมสำหรับการซ่อม)";
                mysqli_query($conn, "UPDATE Repair_Request SET problem_description = CONCAT(problem_description, '$update_msg'), tech_noti_read = 0, user_noti_read = 0 WHERE repair_request_id = '$rep_id'");
            }
        }
        mysqli_commit($conn);
        echo "<script>alert('บันทึกการรับของเรียบร้อยแล้ว สถานะจะแจ้งไปยังช่างและผู้แจ้งทันที'); window.location.href='report_purchase.php';</script>";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . addslashes($e->getMessage()) . "');</script>";
    }
}
// ---------------------------------------------------

$full_name = $_SESSION['full_name'];

// --- ส่วนที่เพิ่ม: รับค่าจาก Filter ---
$filter_year = isset($_GET['filter_year']) ? $_GET['filter_year'] : date('Y');
$filter_month = isset($_GET['filter_month']) ? $_GET['filter_month'] : '';

// สร้างเงื่อนไข SQL ตามตัวกรอง
$where_clause = "WHERE 1=1";
if ($filter_year) {
    $where_clause .= " AND YEAR(po.order_date) = '$filter_year'";
}
if ($filter_month) {
    $where_clause .= " AND MONTH(po.order_date) = '$filter_month'";
}

// 2. คำนวณสถิติภาพรวมสำหรับ Dashboard
$sql_stats = "SELECT 
                COUNT(*) as total_po, 
                SUM(total_price) as grand_total,
                SUM(CASE WHEN repair_request_id IS NULL THEN 1 ELSE 0 END) as direct_po,
                SUM(CASE WHEN repair_request_id IS NOT NULL THEN 1 ELSE 0 END) as repair_po
              FROM Purchase_Order po
              $where_clause";
$res_stats = mysqli_query($conn, $sql_stats);
$row_stat = mysqli_fetch_assoc($res_stats);

// 3. ดึงรายการสินค้า (Items) ทั้งหมด
$po_items_map = [];
$sql_items = "SELECT poi.*, d.device_name, d.device_type 
              FROM Purchase_Order_Item poi
              JOIN IT_Device d ON poi.device_id = d.device_id";
$result_items = mysqli_query($conn, $sql_items);
while ($item = mysqli_fetch_assoc($result_items)) {
    $po_items_map[$item['purchase_order_id']][] = $item;
}

// 4. ดึงข้อมูลใบสั่งซื้อหลัก
$sql_po = "SELECT po.*, u.full_name as purchaser_name 
           FROM Purchase_Order po
           LEFT JOIN User u ON po.purchaser_id = u.user_id
           $where_clause
           ORDER BY po.order_date DESC";
$result_po = mysqli_query($conn, $sql_po);

echo getSystemHeader("รายงานการสั่งซื้อ - IT Repair");
?>

<div class="bg-light min-vh-100 pb-5 text-wix-dark">
    <nav class="bg-white shadow-sm sticky-top w-100 mb-4">
        <div class="container d-flex justify-content-between align-items-center py-3">
            <a href="index.php" class="text-decoration-none font-bold text-2xl" style="color: #1a1a2e;">
                IT<span style="color: #9b51e0;">Repair</span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted d-none d-md-inline text-sm">เจ้าหน้าที่จัดซื้อ: <strong><?php echo htmlspecialchars($full_name); ?></strong></span>
                <a href="profile.php" class="btn btn-purple btn-sm rounded-pill px-3">ข้อมูลส่วนตัว</a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row align-items-center mb-4 g-3">
            <div class="col-lg-5">
                <h3 class="font-bold text-dark mb-0">รายงานประวัติการสั่งซื้อ</h3>
                <p class="text-muted text-sm mb-0">สรุปยอดการจัดซื้ออุปกรณ์ IT ตามช่วงเวลา</p>
            </div>
            <div class="col-lg-7">
                <form method="GET" class="row g-2 justify-content-lg-end align-items-center" autocomplete="off">
                    <div class="col-auto">
                        <select name="filter_month" class="form-select form-select-sm rounded-pill px-2">
                            <option value="">ทุกเดือน</option>
                            <?php 
                            $months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
                            foreach($months as $idx => $name) {
                                $m_val = $idx + 1;
                                $selected = ($filter_month == $m_val) ? 'selected' : '';
                                echo "<option value='$m_val' $selected>$name</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-auto">
                       <select name="filter_year" class="form-select form-select-sm rounded-pill px-3" style="width: 85px;">
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
                        <button type="submit" class="btn btn-sm btn-dark rounded-pill px-4">ค้นหา</button>
                        <a href="report_purchase.php" class="btn btn-sm btn-light border rounded-pill px-3">ล้างค่า</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4 text-center">
            <div class="col-md-3"><div class="card border-0 shadow-sm rounded-4 bg-primary text-white p-4 h-100"><h2 class="font-extrabold mb-0"><?php echo number_format($row_stat['total_po'] ?? 0); ?></h2><div class="text-xs opacity-75">ใบสั่งซื้อทั้งหมด</div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm rounded-4 text-white p-4 h-100" style="background-color: #9b51e0;"><h2 class="font-extrabold mb-0">฿<?php echo number_format($row_stat['grand_total'] ?? 0, 2); ?></h2><div class="text-xs opacity-75">งบประมาณรวม</div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm rounded-4 bg-info text-dark p-4 h-100"><h2 class="font-extrabold mb-0"><?php echo number_format($row_stat['repair_po'] ?? 0); ?></h2><div class="text-xs opacity-75">จากงานแจ้งซ่อม</div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm rounded-4 bg-success text-white p-4 h-100"><h2 class="font-extrabold mb-0"><?php echo number_format($row_stat['direct_po'] ?? 0); ?></h2><div class="text-xs opacity-75">ซื้อเข้าคลังโดยตรง</div></div></div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom"><h6 class="mb-0 font-bold"><i class="bi bi-bar-chart-fill me-2"></i>สรุปการสั่งซื้อแยกตามประเภท</h6></div>
                    <div class="card-body py-4"><div style="height: 250px;"><canvas id="purchaseBarChart"></canvas></div></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-5">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom">
                <h5 class="mb-0 font-bold text-dark"><i class="bi bi-file-earmark-bar-graph me-2"></i>ประวัติรายการสั่งซื้อสินค้า</h5>
                <a href="index.php" class="btn btn-light btn-sm border rounded-pill px-3">หน้าหลัก</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted text-xs text-uppercase">
                            <tr>
                                <th class="py-3 px-4" style="width: 15%;">วันที่สั่งซื้อ</th>
                                <th class="py-3" style="width: 15%;">เลขที่ PO / ร้านค้า</th>
                                <th class="py-3" style="width: 40%;">รายการอุปกรณ์ที่สั่งจริง (Items)</th>
                                <th class="py-3 text-center" style="width: 10%;">ประเภท</th>
                                <th class="py-3 text-end" style="width: 10%;">ยอดสุทธิ</th>
                                <th class="py-3 text-center" style="width: 10%;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php if(mysqli_num_rows($result_po) > 0): ?>
                                <?php while($po = mysqli_fetch_assoc($result_po)): ?>
                                <tr class="border-bottom">
                                    <td class="px-4">
                                        <div class="font-bold text-dark"><?php echo ThaiDate($po['order_date']); ?></div>
                                        <div class="text-xs text-muted">เวลา <?php echo date('H:i', strtotime($po['order_date'])); ?> น.</div>
                                    </td>
                                    <td>
                                        <div class="text-primary font-bold mb-1"><?php echo htmlspecialchars($po['order_number']); ?></div>
                                        <div class="text-xs text-muted">ร้าน: <?php echo htmlspecialchars($po['vendor_name']); ?></div>
                                    </td>
                                    <td class="py-3">
                                        <div class="p-3 rounded-3 bg-white border shadow-sm">
                                            <table class="table table-sm table-borderless mb-2">
                                                <thead class="text-muted border-bottom" style="font-size: 0.7rem;">
                                                    <tr><th>รายการ</th><th class="text-center">จำนวน</th><th class="text-end">ราคา/หน่วย</th></tr>
                                                </thead>
                                                <tbody style="font-size: 0.85rem;">
                                                    <?php 
                                                    $items = $po_items_map[$po['purchase_order_id']] ?? [];
                                                    foreach($items as $itm): 
                                                        $display_name = !empty($itm['item_name_custom']) ? $itm['item_name_custom'] : $itm['device_name'];
                                                    ?>
                                                    <tr>
                                                        <td class="text-dark font-medium">• <?php echo htmlspecialchars($display_name); ?></td>
                                                        <td class="text-center"><?php echo number_format($itm['quantity']); ?></td>
                                                        <td class="text-end">฿<?php echo number_format($itm['price'], 2); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                            <div class="mt-2 pt-2 border-top">
                                                <span class="badge bg-secondary-subtle text-secondary me-1" style="font-size: 0.7rem;">เหตุผล:</span>
                                                <span class="text-muted italic" style="font-size: 0.8rem;">
                                                    <?php echo (strpos($po['purchase_reason'], '| เหตุผล: ') !== false) ? explode('| เหตุผล: ', $po['purchase_reason'])[1] : $po['purchase_reason']; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo empty($po['repair_request_id']) ? 'bg-success-subtle text-success border-success' : 'bg-primary-subtle text-primary border-primary'; ?> border rounded-pill px-3 fw-normal">
                                            <?php echo empty($po['repair_request_id']) ? 'ซื้อเข้าคลัง' : 'งานซ่อม'; ?>
                                        </span>
                                    </td>
                                    <td class="text-end font-bold px-3 text-dark fs-6">฿<?php echo number_format($po['total_price'], 2); ?></td>
                                    <td class="text-center">
                                        <div class="d-flex flex-column gap-1 align-items-center mb-1">
                                            <?php if($po['po_status'] !== 'ได้รับของแล้ว'): ?>
                                                <form method="POST" onsubmit="return confirm('ยืนยันว่าได้รับอุปกรณ์ตาม PO นี้แล้ว?');">
                                                    <input type="hidden" name="po_id" value="<?php echo $po['purchase_order_id']; ?>">
                                                    <input type="hidden" name="action" value="receive_item">
                                                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 w-100" style="font-size: 0.7rem;">รับของเข้า</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success border border-success rounded-pill px-2 py-1 mb-1" style="font-size: 0.65rem;">ได้รับของแล้ว</span>
                                            <?php endif; ?>
                                        </div>

                                        <button class="btn btn-sm btn-outline-purple rounded-pill px-3 w-100" data-bs-toggle="modal" data-bs-target="#modal_<?php echo preg_replace('/[^A-Za-z0-9]/', '', $po['purchase_order_id']); ?>">ดูรายการ</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">ไม่พบประวัติการสั่งซื้อในช่วงเวลาที่เลือก</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
mysqli_data_seek($result_po, 0); 
while($po_modal = mysqli_fetch_assoc($result_po)): 
    $safe_id = preg_replace('/[^A-Za-z0-9]/', '', $po_modal['purchase_order_id']);
?>
<div class="modal fade" id="modal_<?php echo $safe_id; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 bg-light px-4 py-3">
                <h5 class="modal-title font-bold">รายละเอียดใบสั่งซื้อ <?php echo htmlspecialchars($po_modal['order_number']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-4">
                    <div class="col-6">
                        <div class="text-muted text-xs text-uppercase font-bold">ร้านค้าผู้จำหน่าย</div>
                        <div class="font-bold fs-5 text-dark"><?php echo htmlspecialchars($po_modal['vendor_name']); ?></div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="text-muted text-xs text-uppercase font-bold">วันที่สั่งซื้อ</div>
                        <div class="font-bold text-dark"><?php echo ThaiDate($po_modal['order_date']); ?></div>
                    </div>
                </div>
                <table class="table table-bordered text-sm mb-4">
                    <thead class="bg-light text-center">
                        <tr><th width="10%">ลำดับ</th><th width="45%">รายการอุปกรณ์</th><th width="15%">จำนวน</th><th width="15%">ราคา/หน่วย</th><th width="15%">รวม</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $m_items = $po_items_map[$po_modal['purchase_order_id']] ?? [];
                        $idx = 1; 
                        foreach($m_items as $itm): 
                            $m_display_name = !empty($itm['item_name_custom']) ? $itm['item_name_custom'] : $itm['device_name'];
                        ?>
                        <tr class="text-center">
                            <td><?php echo $idx++; ?></td><td class="text-start"><?php echo htmlspecialchars($m_display_name); ?></td><td><?php echo number_format($itm['quantity']); ?></td><td><?php echo number_format($itm['price'], 2); ?></td><td class="text-end font-bold"><?php echo number_format($itm['quantity'] * $itm['price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-light">
                            <td colspan="4" class="text-end font-bold">ยอดรวมสุทธิทั้งสิ้น:</td><td class="text-end text-primary font-bold fs-5">฿<?php echo number_format($po_modal['total_price'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
                <div class="p-3 bg-light rounded-3 text-xs">
                    <strong>สถานะปัจจุบัน:</strong> <span class="text-primary"><?php echo $po_modal['po_status']; ?></span><br>
                    <strong>เหตุผลการสั่งซื้อ:</strong> <?php echo htmlspecialchars($po_modal['purchase_reason']); ?>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>
<?php endwhile; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('purchaseBarChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['สั่งซื้อจากงานซ่อม', 'ซื้อเข้าคลังโดยตรง'],
            datasets: [{
                label: 'จำนวนใบสั่งซื้อ (รายการ)',
                data: [<?php echo (int)$row_stat['repair_po']; ?>, <?php echo (int)$row_stat['direct_po']; ?>],
                backgroundColor: ['#0dcaf0', '#198754'],
                borderRadius: 8,
                barThickness: 60
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
    .bg-primary-subtle { background-color: #e7f1ff; }
    .bg-success-subtle { background-color: #e6fcf5; }
    .bg-secondary-subtle { background-color: #f1f3f5; }
    .italic { font-style: italic; }
    .btn-outline-purple { color: #9b51e0; border-color: #9b51e0; }
    .btn-outline-purple:hover { background-color: #9b51e0; color: #fff; }
</style>

<?php echo getSystemFooter(); ?>