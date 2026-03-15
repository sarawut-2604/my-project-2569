<?php
require_once 'config.php';

// ตรวจสอบ Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$request_id = isset($_GET['id']) ? mysqli_real_escape_with_like($conn, $_GET['id']) : null;

// Logic การเลือก SQL ตาม Role และเงื่อนไข (ล้างเฉพาะอัน หรือ ล้างทั้งหมด)
if ($user_role == 'ช่างเทคนิค') {
    // ล้างแจ้งเตือนสำหรับช่าง: เฉพาะสถานะที่ของมาถึงแล้ว (tech_noti_read)
    $sql = "UPDATE Repair_Request SET tech_noti_read = 1 WHERE technician_id = '$user_id'";
    if ($request_id) {
        $sql .= " AND repair_request_id = '$request_id'";
    } else {
        $sql .= " AND tech_noti_read = 0";
    }

} elseif ($user_role == 'ฝ่ายจัดซื้อ') {
    // ล้างแจ้งเตือนสำหรับฝ่ายจัดซื้อ: เมื่อมีใบแจ้งซ่อมใหม่หรือใบขอเบิก (pur_noti_read)
    $sql = "UPDATE Repair_Request SET pur_noti_read = 1 WHERE 1=1";
    if ($request_id) {
        $sql .= " AND repair_request_id = '$request_id'";
    } else {
        $sql .= " AND pur_noti_read = 0";
    }

} else {
    // สำหรับผู้ใช้งานทั่วไป: ล้างแจ้งเตือนสถานะงาน (user_noti_read)
    $sql = "UPDATE Repair_Request SET user_noti_read = 1 WHERE user_id = '$user_id'";
    if ($request_id) {
        $sql .= " AND repair_request_id = '$request_id'";
    } else {
        $sql .= " AND user_noti_read = 0";
    }
}

// ประมวลผล
if (mysqli_query($conn, $sql)) {
    // กลับไปหน้าเดิมที่มา หรือไปหน้าแรกถ้าไม่ระบุ
    $referrer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header("Location: " . $referrer);
} else {
    die("Error updating record: " . mysqli_error($conn));
}
exit();