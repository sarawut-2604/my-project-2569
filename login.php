<?php
// ไฟล์: login.php
require_once 'config.php';

// หากล็อกอินอยู่แล้ว ให้เด้งไปหน้า index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $query = "SELECT * FROM User WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        
        // ตรวจสอบรหัสผ่านที่ Hash ไว้
        if (password_verify($password, $row['password'])) {
            // สร้าง Session
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['role'] = $row['role'];
            
            // เปลี่ยนหน้าไปยัง Dashboard
            header("Location: index.php");
            exit();
        } else {
            $message = 'รหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
            $msg_type = 'danger';
        }
    } else {
        $message = 'ไม่พบชื่อผู้ใช้งานนี้ในระบบ';
        $msg_type = 'danger';
    }
}

echo getSystemHeader("เข้าสู่ระบบ - ระบบแจ้งซ่อมอุปกรณ์ IT");
?>

<div class="container mt-5">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow border-0 rounded-4">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h3 class="font-bold text-primary">เข้าสู่ระบบ</h3>
                        <p class="text-muted">ระบบแจ้งซ่อมอุปกรณ์ IT</p>
                    </div>

                    <?php if($message != ''): ?>
                        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label text-sm font-semibold">ชื่อผู้ใช้งาน (Username)</label>
                            <input type="text" class="form-control form-control-lg bg-light" id="username" name="username" placeholder="กรอกชื่อผู้ใช้งาน" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label text-sm font-semibold">รหัสผ่าน (Password)</label>
                            <input type="password" class="form-control form-control-lg bg-light" id="password" name="password" placeholder="กรอกรหัสผ่าน" required>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg shadow-sm">เข้าสู่ระบบ</button>
                            <a href="register.php" class="btn btn-outline-secondary">ยังไม่มีบัญชี? สมัครสมาชิก</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="text-center mt-4 text-muted text-sm">
                &copy; <?php echo date('Y') + 543; ?> ระบบแจ้งซ่อมอุปกรณ์ IT
            </div>
        </div>
    </div>
</div>

<?php echo getSystemFooter(); ?>