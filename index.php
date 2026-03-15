<?php
require_once 'config.php';

// ตรวจสอบการ Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['role'];
$full_name = $_SESSION['full_name'];
$user_id = $_SESSION['user_id'];

echo getSystemHeader("หน้าหลัก - ระบบแจ้งซ่อมอุปกรณ์ IT");
?>

<style>
    /* ปรับแต่ง Style ให้ดู Modern และสีไม่แปลก */
    .card-menu { 
        transition: all 0.3s ease; 
        border: 1px solid rgba(0,0,0,0.08) !important;
        border-radius: 20px !important;
    }
    .card-menu:hover { 
        transform: translateY(-12px); 
        box-shadow: 0 15px 35px rgba(155, 81, 224, 0.15) !important; 
    }
    .icon-circle { 
        width: 80px; 
        height: 80px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        margin: 0 auto 20px; 
        border-radius: 50%;
    }
    .bg-grid-pattern { 
        background-color: #fbfbfb;
        background-image: radial-gradient(#9b51e0 0.5px, transparent 0.5px);
        background-size: 30px 30px;
    }
    .btn-nav-custom { border-radius: 50px; font-weight: 500; transition: 0.3s; }
    .hover-bg-light:hover { background-color: #f8f9fa; }
    .text-wix-dark { color: #1a1a2e; }
    .text-wix-purple { color: #9b51e0; }
</style>

<div class="bg-white shadow-sm sticky-top w-100" style="z-index: 1020;">
    <div class="container d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center" style="gap: 20px;">
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm rounded-circle p-2" 
                        type="button" id="menuDropdown" data-bs-toggle="dropdown" aria-expanded="false" 
                        style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-list" style="font-size: 1.2rem;"></i>
                </button>
                <ul class="dropdown-menu shadow-lg border-0 rounded-3 mt-2" aria-labelledby="menuDropdown">
                    <?php if($user_role == 'ผู้ใช้งานทั่วไป'): ?>
                        <li><a class="dropdown-item py-2 px-3" href="repair_request.php"><i class="bi bi-tools me-2"></i>แจ้งซ่อมอุปกรณ์</a></li>
                        <li><a class="dropdown-item py-2 px-3" href="repair_history.php"><i class="bi bi-search me-2"></i>ตรวจสอบสถานะ</a></li>
                        <li><a class="dropdown-item py-2 px-3" href="request_new_device.php"><i class="bi bi-cart-plus me-2"></i>ขอจัดซื้อเครื่องใหม่</a></li>
                    <?php elseif($user_role == 'ช่างเทคนิค'): ?>
                        <li><a class="dropdown-item py-2 px-3" href="job_list.php"><i class="bi bi-clipboard-check me-2"></i>รับงานซ่อม & ปรับสถานะ</a></li>
                        <li><a class="dropdown-item py-2 px-3" href="job_history.php"><i class="bi bi-clock-history me-2"></i>ประวัติงานของฉัน</a></li>
                        <li><a class="dropdown-item py-2 px-3" href="request_parts.php"><i class="bi bi-cpu me-2"></i>ขอจัดซื้ออะไหล่ใหม่</a></li>
                    <?php elseif($user_role == 'ฝ่ายจัดซื้อ'): ?>
                        <li><a class="dropdown-item py-2 px-3" href="device_manage.php"><i class="bi bi-pc-display me-2"></i>จัดการคลังอุปกรณ์</a></li>
                        <li><a class="dropdown-item py-2 px-3" href="order_device.php"><i class="bi bi-bag-check me-2"></i>สั่งซื้ออุปกรณ์ทดแทน</a></li>
                        <li><a class="dropdown-item py-2 px-3" href="direct_order.php"><i class="bi bi-cart-check me-2"></i>สั่งซื้อทั่วไป (PO อิสระ)</a></li>
                        <li><a class="dropdown-item py-2 px-3" href="report_purchase.php"><i class="bi bi-graph-up me-2"></i>รายงานการสั่งซื้อ</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <a href="index.php" class="text-decoration-none" style="font-size: 1.5rem; font-weight: 700; color: #1a1a2e;">
                IT <span class="text-wix-purple">Repair</span>
            </a>
        </div>
        
        <div class="d-flex align-items-center" style="gap: 15px;">
            <span class="text-muted d-none d-md-inline me-2">
                สวัสดี, <strong><?php echo htmlspecialchars($full_name); ?></strong> (<?php echo htmlspecialchars($user_role); ?>)
            </span>

            <div class="dropdown me-2">
                <a href="#" class="text-decoration-none position-relative d-inline-block p-2" id="notiBell" data-bs-toggle="dropdown" aria-expanded="false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-bell text-secondary" viewBox="0 0 16 16">
                        <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2M8 1.918l-.797.161A4 4 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4 4 0 0 0-3.203-3.92zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5 5 0 0 1 13 6c0 .88.32 4.2 1.22 6"/>
                    </svg>
                    
                    <?php 
                    $count = getTotalNotiCount($conn, $user_id);
                    if($count > 0): 
                    ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger border border-white" 
                              style="font-size: 0.65rem; padding: 0.35em 0.6em; transform: translate(-35%, 15%) !important;">
                            <?php echo $count; ?>
                        </span>
                    <?php endif; ?>
                </a>
                
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-3 py-0 overflow-hidden" aria-labelledby="notiBell" style="width: 340px;">
                    <li class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                        <span class="font-bold text-sm text-dark">รายการแจ้งเตือน</span>
                        <?php if($count > 0): ?>
                            <a href="clear_noti.php" class="text-xs text-danger text-decoration-none font-bold" onclick="return confirm('ล้างแจ้งเตือนทั้งหมด?')">ล้างทั้งหมด</a>
                        <?php endif; ?>
                    </li>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php 
                        $notis = getRoleNotifications($conn, $user_id, $user_role);
                        if(mysqli_num_rows($notis) > 0):
                            while($n = mysqli_fetch_assoc($notis)):
                                $bg = ""; $title = ""; $icon = ""; $desc = ""; $btn_text = "ดูรายละเอียด"; $btn_link = "index.php";

                                if($user_role == 'ช่างเทคนิค') {
                                    if($n['technician_id'] == NULL) {
                                        $bg = "#ffffff"; $title = "งานใหม่จาก User"; $icon = "bi-person-plus text-primary"; 
                                        $desc = "ผู้แจ้ง: ".$n['full_name']." (".$n['device_name'].")";
                                        $btn_text = "รับงานทันที"; $btn_link = "job_list.php?accept_id=".$n['repair_request_id'];
                                    } else {
                                        $bg = "#f0fff4"; $title = "อะไหล่มาถึงคลังแล้ว!";
                                        $icon = "bi-box-seam text-success"; $desc = "อุปกรณ์: ".$n['device_name'];
                                        $btn_text = "ดำเนินการต่อ"; $btn_link = "job_list.php";
                                    }
                                } elseif($user_role == 'ฝ่ายจัดซื้อ') {
                                    $bg = "#fffbeb"; $title = "คำขอจัดซื้อใหม่"; $icon = "bi-cart-plus text-warning";
                                    $desc = "จาก: ".$n['full_name']." เรื่อง: ".$n['device_name'];
                                    $btn_text = "เปิดรายการสั่งซื้อ"; $btn_link = "order_device.php";
                                } else {
                                    if($n['repair_status'] == 'สำเร็จ') { $bg = "#f0fdf4"; $title = "ซ่อมเสร็จสิ้นแล้ว"; $icon = "bi-check-circle text-success"; }
                                    elseif($n['po_status'] == 'ได้รับของแล้ว' && $n['repair_status'] == 'ไม่สามารถซ่อมได้') { $bg = "#f5f3ff"; $title = "เครื่องใหม่มาถึงแล้ว!"; $icon = "bi-box-seam text-purple"; }
                                    elseif($n['repair_status'] == 'ไม่สามารถซ่อมได้') { $bg = "#fff1f2"; $title = "ไม่สามารถซ่อมได้"; $icon = "bi-x-circle text-danger"; }
                                    elseif($n['repair_status'] == 'กำลังดำเนินการ') { $bg = "#eff6ff"; $title = "ช่างรับงานแล้ว"; $icon = "bi-person-check text-primary"; }
                                    $desc = "อุปกรณ์: ".$n['device_name'];
                                    $btn_text = "ตรวจสอบผล"; $btn_link = "repair_history.php";
                                }
                        ?>
                            <li class="p-3 border-bottom hover-bg-light" style="background-color: <?php echo $bg; ?>;">
                                <div class="font-bold text-sm mb-1"><i class="bi <?php echo $icon; ?> me-2"></i><?php echo $title; ?></div>
                                <div class="text-xs text-dark mb-2"><?php echo htmlspecialchars($desc); ?></div>
                                <a href="<?php echo $btn_link; ?>" class="btn btn-sm btn-outline-dark w-100 rounded-pill py-1 text-xs font-bold"><?php echo $btn_text; ?></a>
                            </li>
                        <?php endwhile; else: ?>
                            <li class="p-5 text-center text-muted text-sm fst-italic">ไม่มีรายการแจ้งเตือน</li>
                        <?php endif; ?>
                    </div>
                    <li>
                        <a class="dropdown-item text-center py-2 bg-light text-primary font-bold text-xs" 
                           href="<?php echo ($user_role == 'ช่างเทคนิค' ? 'job_list.php' : 'repair_history.php'); ?>">
                            ดูรายการงานทั้งหมด
                        </a>
                    </li>
                </ul>
            </div>

            <a href="profile.php" class="btn text-white px-4 btn-nav-custom shadow-sm" style="background-color: #9b51e0;">ข้อมูลส่วนตัว</a>
            <a href="logout.php" class="btn btn-outline-danger px-4 btn-nav-custom shadow-sm">ออกจากระบบ</a>
        </div>
    </div>
</div>

<div class="relative bg-grid-pattern pt-20 pb-32 overflow-hidden position-relative">
    <div class="container position-relative z-index-10">
        <div class="row">
            <div class="col-lg-7">
                <h1 class="display-3 fw-extrabold text-wix-dark mb-4 tracking-tight">ระบบแจ้งซ่อม<br><span class="text-wix-purple">อุปกรณ์ IT</span> ขององค์กร</h1>
                <p class="text-lg text-secondary mb-5 leading-relaxed">จัดการปัญหาอุปกรณ์ IT ตรวจสอบสถานะการซ่อม และติดตามงานจัดซื้อได้รวดเร็วในที่เดียว</p>
                <?php if($user_role == 'ผู้ใช้งานทั่วไป'): ?>
                    <a href="repair_request.php" class="btn btn-lg text-white px-5 py-3 shadow-lg" style="background-color: #9b51e0; border-radius: 50px; font-weight: 600;">แจ้งซ่อมอุปกรณ์ทันที</a>
                <?php elseif($user_role == 'ช่างเทคนิค'): ?>
                    <a href="job_list.php" class="btn btn-lg text-white px-5 py-3 shadow-lg" style="background-color: #9b51e0; border-radius: 50px; font-weight: 600;">ดูรายการงานซ่อม</a>
                <?php else: ?>
                    <a href="device_manage.php" class="btn btn-lg text-white px-5 py-3 shadow-lg" style="background-color: #9b51e0; border-radius: 50px; font-weight: 600;">จัดการระบบคลัง</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="position-absolute bottom-0 start-0 w-100 overflow-hidden" style="line-height: 0;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 320" preserveAspectRatio="none" style="width: 100%; height: 160px;">
            <path fill="#9b51e0" fill-opacity="0.1" d="M0,224L48,213.3C96,203,192,181,288,197.3C384,213,480,267,576,261.3C672,256,768,192,864,165.3C960,139,1056,149,1104,154.7L1152,160L1152,320L1104,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            <path fill="#9b51e0" fill-opacity="1" d="M0,160L48,170.7C96,181,192,203,288,181.3C384,160,480,96,576,90.7C672,85,768,139,864,170.7C960,203,1056,213,1104,218.7L1152,224L1152,320L1104,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
        </svg>
    </div>
</div>

<div class="container py-5" style="margin-top: -30px;">
    <div class="row g-4 justify-content-center">
        <?php if($user_role == 'ผู้ใช้งานทั่วไป'): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm card-menu p-4 text-center">
                    <div class="icon-circle shadow-sm" style="background-color: #f3e8ff; color: #9b51e0;"><i class="bi bi-tools fs-1"></i></div>
                    <h4 class="fw-bold text-wix-dark">แจ้งซ่อมอุปกรณ์</h4>
                    <p class="text-muted small mb-4">เปิดใบแจ้งซ่อมอุปกรณ์ IT เมื่อพบปัญหา</p>
                    <a href="repair_request.php" class="btn btn-outline-primary rounded-pill w-100 py-2 fw-bold">เข้าใช้งาน</a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm card-menu p-4 text-center">
                    <div class="icon-circle shadow-sm" style="background-color: #e0f2fe; color: #0284c7;"><i class="bi bi-search fs-1"></i></div>
                    <h4 class="fw-bold text-wix-dark">ตรวจสอบสถานะ</h4>
                    <p class="text-muted small mb-4">ติดตามความคืบหน้าและประวัติการซ่อม</p>
                    <a href="repair_history.php" class="btn btn-outline-info rounded-pill w-100 py-2 fw-bold">เข้าใช้งาน</a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm card-menu p-4 text-center">
                    <div class="icon-circle shadow-sm" style="background-color: #dcfce7; color: #16a34a;"><i class="bi bi-cart-plus fs-1"></i></div>
                    <h4 class="fw-bold text-wix-dark">ขอจัดซื้อเครื่องใหม่</h4>
                    <p class="text-muted small mb-4">ส่งคำร้องเบิกอุปกรณ์ใหม่กรณีซ่อมไม่ได้</p>
                    <a href="request_new_device.php" class="btn btn-outline-success rounded-pill w-100 py-2 fw-bold">เข้าใช้งาน</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if($user_role == 'ช่างเทคนิค'): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm card-menu p-4 text-center">
                    <div class="icon-circle shadow-sm" style="background-color: #fff7ed; color: #ea580c;"><i class="bi bi-clipboard-check fs-1"></i></div>
                    <h4 class="fw-bold text-wix-dark">รับงานซ่อม & ปรับสถานะ</h4>
                    <p class="text-muted small mb-4">จัดการงานซ่อมที่ได้รับมอบหมายและรับงานใหม่</p>
                    <a href="job_list.php" class="btn btn-warning text-white rounded-pill w-100 py-2 fw-bold shadow-sm">เข้าใช้งาน</a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm card-menu p-4 text-center">
                    <div class="icon-circle shadow-sm" style="background-color: #eff6ff; color: #2563eb;"><i class="bi bi-clock-history fs-1"></i></div>
                    <h4 class="fw-bold text-wix-dark">ประวัติงานของฉัน</h4>
                    <p class="text-muted small mb-4">ดูรายการซ่อมย้อนหลังที่คุณรับผิดชอบ</p>
                    <a href="job_history.php" class="btn btn-primary rounded-pill w-100 py-2 fw-bold shadow-sm">เข้าใช้งาน</a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm card-menu p-4 text-center">
                    <div class="icon-circle shadow-sm" style="background-color: #f0fdf4; color: #16a34a;"><i class="bi bi-cpu fs-1"></i></div>
                    <h4 class="fw-bold text-wix-dark">ขอจัดซื้ออะไหล่ใหม่</h4>
                    <p class="text-muted small mb-4">ส่งคำร้องเบิกอะไหล่ (RAM, SSD) เพื่อใช้ซ่อม</p>
                    <a href="request_parts.php" class="btn btn-success rounded-pill w-100 py-2 fw-bold shadow-sm">เข้าใช้งาน</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if($user_role == 'ฝ่ายจัดซื้อ'): ?>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm card-menu p-4 text-center">
                    <div class="icon-circle shadow-sm" style="background-color: #eef2ff; color: #4338ca;"><i class="bi bi-pc-display fs-1"></i></div>
                    <h5 class="fw-bold text-wix-dark">จัดการคลังอุปกรณ์</h5>
                    <a href="device_manage.php" class="btn btn-outline-indigo rounded-pill w-100 py-2 mt-3 fw-bold border-primary text-primary">เข้าใช้งาน</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm card-menu p-4 text-center">
                    <div class="icon-circle shadow-sm" style="background-color: #f0fdfa; color: #0d9488;"><i class="bi bi-bag-check fs-1"></i></div>
                    <h5 class="fw-bold text-wix-dark">สั่งซื้ออุปกรณ์ทดแทน</h5>
                    <a href="order_device.php" class="btn btn-teal rounded-pill w-100 py-2 mt-3 fw-bold bg-teal-600 text-white shadow-sm">เข้าใช้งาน</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm card-menu p-4 text-center">
                    <div class="icon-circle shadow-sm" style="background-color: #f5f3ff; color: #7c3aed;"><i class="bi bi-cart-check fs-1"></i></div>
                    <h5 class="fw-bold text-wix-dark">สั่งซื้อทั่วไป (PO อิสระ)</h5>
                    <a href="direct_order.php" class="btn btn-purple rounded-pill w-100 py-2 mt-3 fw-bold text-white shadow-sm" style="background-color: #7c3aed;">เข้าใช้งาน</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 shadow-sm card-menu p-4 text-center">
                    <div class="icon-circle shadow-sm" style="background-color: #fdf2f8; color: #db2777;"><i class="bi bi-graph-up fs-1"></i></div>
                    <h5 class="fw-bold text-wix-dark">รายงานการสั่งซื้อ</h5>
                    <a href="report_purchase.php" class="btn btn-outline-danger rounded-pill w-100 py-2 mt-3 fw-bold border-danger text-danger">เข้าใช้งาน</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php echo getSystemFooter(); ?>