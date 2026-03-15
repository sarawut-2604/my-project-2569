<?php
// ไฟล์: register.php
require_once 'config.php';

$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // ตรวจสอบว่ามี username ซ้ำหรือไม่
    $check_query = "SELECT username FROM User WHERE username = '$username'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $message = 'ชื่อผู้ใช้งาน (Username) นี้มีในระบบแล้ว กรุณาใช้ชื่ออื่น';
        $msg_type = 'danger';
    } else {
        // เข้ารหัสผ่าน
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $insert_query = "INSERT INTO User (username, password, full_name, email, phone_number, department, role) 
                         VALUES ('$username', '$hashed_password', '$full_name', '$email', '$phone_number', '$department', '$role')";

        if (mysqli_query($conn, $insert_query)) {
            $message = 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ';
            $msg_type = 'success';
        } else {
            $message = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn);
            $msg_type = 'danger';
        }
    }
}

echo getSystemHeader("สมัครสมาชิก - ระบบแจ้งซ่อมอุปกรณ์ IT");
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-primary text-white text-center py-3 rounded-top-3">
                    <h4 class="mb-0 font-semibold">สมัครสมาชิก</h4>
                    <p class="mb-0 text-sm opacity-75">ระบบแจ้งซ่อมอุปกรณ์ IT</p>
                </div>
                <div class="card-body p-4">
                    
                    <?php if($message != ''): ?>
                        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="register.php" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">ชื่อผู้ใช้งาน (Username) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">รหัสผ่าน (Password) <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="mb-3">
                            <label for="full_name" class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">อีเมล</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            <div class="col-md-6">
                                <label for="phone_number" class="form-label">เบอร์โทรศัพท์</label>
                                <input type="text" class="form-control" id="phone_number" name="phone_number">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="department" class="form-label">แผนก / ฝ่าย <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="department" name="department" required>
                        </div>

                        <div class="mb-4">
                            <label for="role" class="form-label">บทบาทผู้ใช้งาน (Role) <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="" disabled selected>-- เลือกบทบาท --</option>
                                <option value="ผู้ใช้งานทั่วไป">ผู้ใช้งานทั่วไป (User)</option>
                                <option value="ช่างเทคนิค">เจ้าหน้าที่ซ่อมบำรุง (Maintenance Staff)</option>
                                <option value="ฝ่ายจัดซื้อ">เจ้าหน้าที่จัดซื้อ (Purchasing Staff)</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">ลงทะเบียน</button>
                            <a href="login.php" class="btn btn-outline-secondary">มีบัญชีอยู่แล้ว? เข้าสู่ระบบ</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php echo getSystemFooter(); ?>