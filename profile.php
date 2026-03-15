<?php
// ไฟล์: profile.php
require_once 'config.php';

// ตรวจสอบการ Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$msg_type = '';

// หากมีการกดปุ่มบันทึกข้อมูล (POST Request)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);

    // อัปเดตข้อมูลลงฐานข้อมูล (ไม่อนุญาตให้แก้ username และ role)
    $update_query = "UPDATE User SET 
                        full_name = '$full_name', 
                        email = '$email', 
                        phone_number = '$phone_number', 
                        department = '$department' 
                     WHERE user_id = '$user_id'";

    if (mysqli_query($conn, $update_query)) {
        $message = 'อัปเดตข้อมูลส่วนตัวเรียบร้อยแล้ว';
        $msg_type = 'success';
        // อัปเดต Session ให้แสดงชื่อใหม่ทันที
        $_SESSION['full_name'] = $full_name;
    } else {
        $message = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . mysqli_error($conn);
        $msg_type = 'danger';
    }
}

// ดึงข้อมูลผู้ใช้ปัจจุบันมาแสดงในฟอร์ม
$query = "SELECT * FROM User WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $query);
$user_data = mysqli_fetch_assoc($result);

echo getSystemHeader("ข้อมูลส่วนตัว - ระบบแจ้งซ่อมอุปกรณ์ IT");
?>

<nav class="navbar navbar-expand-lg py-3 sticky-top bg-white border-bottom shadow-sm">
    <div class="container">
        <a class="navbar-brand font-bold text-2xl text-wix-dark" href="index.php">IT<span class="text-wix-purple">Repair</span></a>
        <div class="d-flex align-items-center">
            <span class="me-3 text-gray-600 d-none d-md-block">สวัสดี, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">กลับหน้าหลัก</a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            
            <div class="mb-4 text-center">
                <h2 class="font-extrabold text-3xl text-wix-dark">ข้อมูลส่วนตัว <span class="text-wix-purple">(Profile)</span></h2>
                <p class="text-gray-500">จัดการและแก้ไขข้อมูลพื้นฐานของคุณ</p>
            </div>

            <?php if($message != ''): ?>
                <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show shadow-sm rounded-3" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-body p-4 p-md-5">
                    <form action="profile.php" method="POST">
                        
                        <div class="row mb-4 bg-gray-50 p-3 rounded-3 border border-gray-100">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label text-sm text-gray-500 font-semibold mb-1">ชื่อผู้ใช้งาน (Username)</label>
                                <input type="text" class="form-control bg-transparent border-0 p-0 font-medium" value="<?php echo htmlspecialchars($user_data['username']); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-sm text-gray-500 font-semibold mb-1">บทบาท (Role)</label>
                                <div class="mt-1">
                                    <span class="badge bg-wix-purple px-3 py-2 rounded-pill"><?php echo htmlspecialchars($user_data['role']); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="full_name" class="form-label font-medium">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg focus:border-wix-purple focus:ring-wix-purple" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="department" class="form-label font-medium">แผนก / ฝ่าย <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg focus:border-wix-purple focus:ring-wix-purple" id="department" name="department" value="<?php echo htmlspecialchars($user_data['department']); ?>" required>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="email" class="form-label font-medium">อีเมล</label>
                                <input type="email" class="form-control form-control-lg focus:border-wix-purple focus:ring-wix-purple" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="phone_number" class="form-label font-medium">เบอร์โทรศัพท์</label>
                                <input type="text" class="form-control form-control-lg focus:border-wix-purple focus:ring-wix-purple" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user_data['phone_number']); ?>">
                            </div>
                        </div>

                        <div class="d-grid pt-2">
                            <button type="submit" class="btn-purple btn-lg w-100">
                                บันทึกการเปลี่ยนแปลง
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php echo getSystemFooter(); ?>