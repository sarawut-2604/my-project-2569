<?php
require_once 'config.php';

// เพิ่ม column ใหม่ลงใน purchase_order_item
$queries = [
    "ALTER TABLE purchase_order_item ADD COLUMN brand VARCHAR(100) DEFAULT NULL AFTER device_id",
    "ALTER TABLE purchase_order_item ADD COLUMN model VARCHAR(100) DEFAULT NULL AFTER brand",
    "ALTER TABLE purchase_order_item ADD COLUMN serial_number VARCHAR(100) DEFAULT NULL AFTER model",
    "ALTER TABLE purchase_order_item ADD COLUMN location VARCHAR(100) DEFAULT NULL AFTER serial_number"
];

foreach ($queries as $query) {
    if (mysqli_query($conn, $query)) {
        echo "✅ Query สำเร็จ: " . substr($query, 0, 50) . "...\n";
    } else {
        // ถ้า column มีอยู่แล้ว จะมี warning ซึ่งถือว่าเรียบร้อย
        echo "⚠️ Query: " . substr($query, 0, 50) . "... (อาจมีแล้ว)\n";
        echo "Error: " . mysqli_error($conn) . "\n\n";
    }
}

// ตรวจสอบ column ใหม่
$check = mysqli_query($conn, "DESCRIBE purchase_order_item");
echo "\n📋 โครงสร้างตาราง purchase_order_item ปัจจุบัน:\n";
while ($row = mysqli_fetch_assoc($check)) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

mysqli_close($conn);
?>
