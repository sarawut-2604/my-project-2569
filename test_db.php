<?php
$conn = mysqli_connect('localhost', 'root', '', 'it_repair_system');
if (!$conn) die('connection failed');
$res = mysqli_query($conn, 'DESCRIBE Repair_Request');
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
}
?>
