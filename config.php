<?php
// ไฟล์: config.php
session_start();

// 1. ตั้งค่าการเชื่อมต่อฐานข้อมูล
$db_host = "localhost";
$db_user = "root"; 
$db_pass = "";     
$db_name = "it_repair_system";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) { die("Connection failed: " . mysqli_connect_error()); }
mysqli_set_charset($conn, "utf8mb4");

// 2. ตั้งค่า Timezone เป็นเวลาประเทศไทย
date_default_timezone_set('Asia/Bangkok');

// 3. ฟังก์ชันแปลงวันที่เป็นรูปแบบ วันเดือนปีไทย
function ThaiDate($datetime) {
    if(!$datetime || $datetime == '0000-00-00 00:00:00') return '-';
    $year = date('Y', strtotime($datetime)) + 543;
    $month = date('n', strtotime($datetime));
    $day = date('j', strtotime($datetime));
    $thai_months = array(1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม');
    return "$day {$thai_months[$month]} $year";
}

// 4. Header และ CDN รวม (Theme สีม่วงตาม Reference)
function getSystemHeader($title = "ระบบแจ้งซ่อมอุปกรณ์ IT") {
    return '
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>'.$title.'</title>
        
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        
        <script src="https://cdn.tailwindcss.com"></script>
        
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            wix: {
                                purple: "#9b51e0",
                                dark: "#1a1a2e",
                                hover: "#8a2be2"
                            }
                        },
                        fontFamily: {
                            sans: ["Prompt", "sans-serif"],
                        }
                    }
                }
            }
        </script>
        
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        
        <style>
            body {
                font-family: "Prompt", sans-serif;
                background-color: #ffffff;
                color: #1a1a2e;
            }
            /* พื้นหลังลายตาราง (Grid) ตามภาพ */
            .bg-grid-pattern {
                background-image: 
                    linear-gradient(to right, rgba(155, 81, 224, 0.05) 1px, transparent 1px),
                    linear-gradient(to bottom, rgba(155, 81, 224, 0.05) 1px, transparent 1px);
                background-size: 50px 50px;
                background-position: center center;
            }
            /* ปุ่มสีม่วงขอบมน */
            .btn-purple {
                background-color: #9b51e0;
                color: white;
                border-radius: 50px;
                padding: 10px 28px;
                font-weight: 500;
                border: none;
                transition: all 0.3s;
            }
            .btn-purple:hover {
                background-color: #8a2be2;
                color: white;
                box-shadow: 0 4px 12px rgba(155, 81, 224, 0.3);
            }
        </style>
    </head>
    <body>
    ';
}

function getSystemFooter() {
    return '
    </body>
    </html>
    ';
}
// --- ฟังก์ชันนับแจ้งเตือนรวม (Senior Version: แยก Role และ Logic ชัดเจน) ---
function getTotalNotiCount($conn, $user_id) {
    $role = $_SESSION['role'];
    if ($role == 'ช่างเทคนิค') {
        // งานใหม่ที่ไม่มีคนรับ + งานตัวเองที่ PO ของมาถึง (เฉพาะกรณีช่างสั่งซื้ออุปกรณ์)
        $sql = "SELECT (SELECT COUNT(*) FROM Repair_Request WHERE technician_id IS NULL) + 
                       (SELECT COUNT(*) FROM Repair_Request r INNER JOIN Purchase_Order po ON r.repair_request_id = po.repair_request_id 
                        WHERE r.technician_id = '$user_id' AND r.tech_noti_read = 0 AND po.po_status = 'ได้รับของแล้ว' AND r.repair_status = 'สั่งซื้ออุปกรณ์') as total";
    } elseif ($role == 'ฝ่ายจัดซื้อ') {
        // คำขอเบิกเครื่องใหม่จาก User และคำขออะไหล่จากช่าง (และยังไม่อ่าน)
        $sql = "SELECT COUNT(*) as total FROM Repair_Request WHERE repair_status IN ('ไม่สามารถซ่อมได้', 'สั่งซื้ออุปกรณ์') AND pur_noti_read = 0 AND problem_description LIKE '%[คำร้องขอจัดซื้อ%'";
    } else {
        // สำหรับ User: นับเฉพาะสถานะที่อนุญาตให้เห็น
        $sql = "SELECT COUNT(*) as total FROM Repair_Request r LEFT JOIN Purchase_Order po ON r.repair_request_id = po.repair_request_id 
                WHERE r.user_id = '$user_id' AND r.user_noti_read = 0 
                AND ((r.repair_status = 'กำลังดำเนินการ' AND r.technician_id IS NOT NULL) OR r.repair_status IN ('สำเร็จ', 'ไม่สามารถซ่อมได้') OR (po.po_status = 'ได้รับของแล้ว' AND r.repair_status = 'ไม่สามารถซ่อมได้'))";
    }
    $res = mysqli_query($conn, $sql);
    return (int)mysqli_fetch_assoc($res)['total'];
}

// --- ฟังก์ชันดึงรายละเอียดแจ้งเตือน (แก้บัคแสดงผลผิดพลาด) ---
function getRoleNotifications($conn, $user_id, $role) {
    if ($role == 'ช่างเทคนิค') {
        return mysqli_query($conn, "SELECT r.*, d.device_name, u.full_name, po.po_status FROM Repair_Request r 
            LEFT JOIN IT_Device d ON r.device_id = d.device_id LEFT JOIN User u ON r.user_id = u.user_id
            LEFT JOIN Purchase_Order po ON r.repair_request_id = po.repair_request_id 
            WHERE (r.technician_id IS NULL) OR (r.technician_id = '$user_id' AND r.tech_noti_read = 0 AND po.po_status = 'ได้รับของแล้ว' AND r.repair_status = 'สั่งซื้ออุปกรณ์')
            ORDER BY r.request_date DESC LIMIT 5");
    } elseif ($role == 'ฝ่ายจัดซื้อ') {
        return mysqli_query($conn, "SELECT r.*, d.device_name, u.full_name FROM Repair_Request r 
            LEFT JOIN IT_Device d ON r.device_id = d.device_id LEFT JOIN User u ON r.user_id = u.user_id 
            WHERE r.repair_status IN ('ไม่สามารถซ่อมได้', 'สั่งซื้ออุปกรณ์') AND r.pur_noti_read = 0 AND r.problem_description LIKE '%[คำร้องขอจัดซื้อ%' ORDER BY r.request_date DESC LIMIT 5");
    } else {
        // User: กรอง 'รอดำเนินการ' ออกจากกระดิ่งแจ้งเตือน
        return mysqli_query($conn, "SELECT r.*, d.device_name, po.po_status FROM Repair_Request r 
            LEFT JOIN IT_Device d ON r.device_id = d.device_id LEFT JOIN Purchase_Order po ON r.repair_request_id = po.repair_request_id 
            WHERE r.user_id = '$user_id' AND r.user_noti_read = 0 
            AND ((r.repair_status = 'กำลังดำเนินการ' AND r.technician_id IS NOT NULL) OR r.repair_status IN ('สำเร็จ', 'ไม่สามารถซ่อมได้') OR (po.po_status = 'ได้รับของแล้ว' AND r.repair_status = 'ไม่สามารถซ่อมได้'))
            ORDER BY r.request_date DESC LIMIT 5");
    }
}
?>