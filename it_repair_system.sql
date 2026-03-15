-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 10, 2026 at 03:58 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `it_repair_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `it_device`
--

CREATE TABLE `it_device` (
  `device_id` int(11) NOT NULL,
  `device_status` varchar(50) DEFAULT NULL,
  `device_name` varchar(100) NOT NULL,
  `device_type` varchar(100) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `purchase_date` datetime DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `device_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `it_device`
--

INSERT INTO `it_device` (`device_id`, `device_status`, `device_name`, `device_type`, `brand`, `model`, `serial_number`, `purchase_date`, `location`, `description`, `device_image`) VALUES
(0, 'ใช้งานปกติ', 'รายการสั่งซื้อทั่วไป / อะไหล่สำรอง', 'อื่นๆ', 'Generic', 'Generic', 'GEN-0000', '2026-02-24 00:00:00', 'IT ROOM', 'รหัสกลางสำหรับใช้บันทึกใบสั่งซื้ออิสระ (Direct PO) ที่ไม่ต้องอ้างอิงเครื่องเดิม', NULL),
(7, 'ใช้งานปกติ', 'HP-leser4044', 'เครื่องพิมพ์ (Printer)', 'HP', 'leser', '123456789', '2026-02-26 00:00:00', 'ห้อง MD', NULL, NULL),
(18, 'ใช้งานปกติ', 'ram 8 GB ', 'อื่นๆ', 'kingstone', 'kingstone', '11', '2026-03-10 00:00:00', 'บัญชีชั้น2', NULL, NULL),
(19, 'ใช้งานปกติ', 'mouse', 'อื่นๆ', 'logitech', 'logitech', '22', '2026-03-10 00:00:00', 'บัญชีชั้น3', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order`
--

CREATE TABLE `purchase_order` (
  `purchase_order_id` varchar(50) NOT NULL,
  `repair_request_id` int(11) DEFAULT NULL,
  `purchaser_id` int(11) NOT NULL,
  `order_date` datetime NOT NULL,
  `order_number` varchar(50) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `vendor_name` varchar(100) DEFAULT NULL,
  `purchase_reason` text DEFAULT NULL,
  `po_status` enum('รอดำเนินการ','ได้รับของแล้ว') DEFAULT 'รอดำเนินการ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_order`
--

INSERT INTO `purchase_order` (`purchase_order_id`, `repair_request_id`, `purchaser_id`, `order_date`, `order_number`, `total_price`, `vendor_name`, `purchase_reason`, `po_status`) VALUES
('PO-20260310-4338', NULL, 2, '2026-03-10 03:35:45', 'PO-2026-0001', 100.00, 'JIB', 'stock', 'ได้รับของแล้ว'),
('PO-20260310-4998', NULL, 2, '2026-03-10 03:37:36', 'PO-2026-0002', 200.00, 'JIB', 'stock', 'ได้รับของแล้ว');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_item`
--

CREATE TABLE `purchase_order_item` (
  `purchase_order_item_id` varchar(50) NOT NULL,
  `purchase_order_id` varchar(50) NOT NULL,
  `device_id` int(11) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `device_type` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `item_name_custom` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_order_item`
--

INSERT INTO `purchase_order_item` (`purchase_order_item_id`, `purchase_order_id`, `device_id`, `brand`, `device_type`, `model`, `serial_number`, `location`, `item_name_custom`, `quantity`, `price`) VALUES
('POI-20260310-6440', 'PO-20260310-4998', 0, 'kingstone', 'อื่นๆ', 'kingstone', '11', 'บัญชีชั้น2', 'ram 8 GB ', 1, 100.00),
('POI-20260310-6691', 'PO-20260310-4998', 0, 'logitech', 'อื่นๆ', 'logitech', '22', 'บัญชีชั้น3', 'mouse', 1, 100.00),
('POI-20260310-8870', 'PO-20260310-4338', 0, 'kingstone', 'อื่นๆ', 'kingstone', '123', 'บัญชีชั้น2', 'ram 8 GB ', 1, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `repair_request`
--

CREATE TABLE `repair_request` (
  `repair_request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `technician_id` int(11) DEFAULT NULL,
  `device_id` int(11) NOT NULL,
  `request_date` datetime NOT NULL,
  `problem_description` text NOT NULL,
  `repair_location` varchar(100) DEFAULT NULL,
  `repair_image` varchar(255) DEFAULT NULL,
  `repair_status` varchar(50) DEFAULT 'กำลังดำเนินการ' COMMENT 'กำลังดำเนินการ, สั่งซื้ออุปกรณ์, สำเร็จ, ไม่สามารถซ่อมได้',
  `tech_noti_read` int(1) DEFAULT 0,
  `user_noti_read` int(1) DEFAULT 0,
  `pur_noti_read` int(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `role` varchar(50) NOT NULL COMMENT 'ช่างเทคนิค, ฝ่ายจัดซื้อ, ผู้ใช้ทั่วไป'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `username`, `password`, `full_name`, `email`, `phone_number`, `department`, `role`) VALUES
(1, 'user01', '$2y$10$w9iAmOJVcumacuDw7SalT.p3yx5Tivf0/SglVZNTftRHmSAm5BACu', 'sarawut rungwongwat', 'ssss@gmail.com', '', 'บัญชี', 'ผู้ใช้งานทั่วไป'),
(2, 'staff01', '$2y$10$cbbVBwxVUimcmpzFcS3lG.LUJNuBtQFl9HCaKBQbFFISf97VQdBya', 'sa sa', '', '', 'จัดซื้อ', 'ฝ่ายจัดซื้อ'),
(3, 'it01', '$2y$10$X528lXV.ZdA3NxVqTDMM..LFY8YP.FZIUXnVldJsrLLe3siSEUy1i', 'it it', '', '', 'ช่างซ่อมอุปกรณ์', 'ช่างเทคนิค'),
(4, 'it02', '$2y$10$mrLC0m01jIyRAERwrT/8fOw4IqQf4U219isE64eeIYyMwgrR0DGou', 'it ia', '', '', 'ช่างซ่อมอุปกรณ์', 'ช่างเทคนิค'),
(5, 'user02', '$2y$10$qDXxizV7vEUjXDyAXO/Y0.jgmlTnrxDeWiBrLeU/0rDKwi.u0uYSG', 'อนุทิน สีน้ำเงิน', 'somchai@gmail.com', '0984321234', 'ฝ่ายบัญชี', 'ผู้ใช้งานทั่วไป'),
(6, 'it03', '$2y$10$y1vyifJ6X997yQsM3dVM0uZsFUq7rkNy2C7jyBjspGLMx9EeXfycq', 'ช่างเด้อ', 'it03@gmail.com', '0984321255', 'IT', 'ช่างเทคนิค');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `it_device`
--
ALTER TABLE `it_device`
  ADD PRIMARY KEY (`device_id`);

--
-- Indexes for table `purchase_order`
--
ALTER TABLE `purchase_order`
  ADD PRIMARY KEY (`purchase_order_id`),
  ADD KEY `repair_request_id` (`repair_request_id`),
  ADD KEY `purchaser_id` (`purchaser_id`);

--
-- Indexes for table `purchase_order_item`
--
ALTER TABLE `purchase_order_item`
  ADD PRIMARY KEY (`purchase_order_item_id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexes for table `repair_request`
--
ALTER TABLE `repair_request`
  ADD PRIMARY KEY (`repair_request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `technician_id` (`technician_id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `it_device`
--
ALTER TABLE `it_device`
  MODIFY `device_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `repair_request`
--
ALTER TABLE `repair_request`
  MODIFY `repair_request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `purchase_order`
--
ALTER TABLE `purchase_order`
  ADD CONSTRAINT `purchase_order_ibfk_1` FOREIGN KEY (`repair_request_id`) REFERENCES `repair_request` (`repair_request_id`),
  ADD CONSTRAINT `purchase_order_ibfk_2` FOREIGN KEY (`purchaser_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `purchase_order_item`
--
ALTER TABLE `purchase_order_item`
  ADD CONSTRAINT `purchase_order_item_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_order` (`purchase_order_id`),
  ADD CONSTRAINT `purchase_order_item_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `it_device` (`device_id`);

--
-- Constraints for table `repair_request`
--
ALTER TABLE `repair_request`
  ADD CONSTRAINT `repair_request_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`),
  ADD CONSTRAINT `repair_request_ibfk_2` FOREIGN KEY (`technician_id`) REFERENCES `user` (`user_id`),
  ADD CONSTRAINT `repair_request_ibfk_3` FOREIGN KEY (`device_id`) REFERENCES `it_device` (`device_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
