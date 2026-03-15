<?php
require_once 'config.php';

// เพิ่ม column device_type ลงใน purchase_order_item
$queries = [
    "ALTER TABLE purchase_order_item ADD COLUMN device_type VARCHAR(100) DEFAULT NULL AFTER brand"
];

foreach ($queries as $query) {
    if (mysqli_query($conn, $query)) {
        echo "✅ เพิ่ม device_type สำเร็จ\n";
    } else {
        echo "⚠️ อาจมีแล้ว หรือ " . mysqli_error($conn) . "\n";
    }
}

mysqli_close($conn);
?>
