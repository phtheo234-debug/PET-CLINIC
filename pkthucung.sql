/*
SQLyog Community v13.3.1 (64 bit)
MySQL - 10.4.32-MariaDB : Database - pkthucung
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`pkthucung` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `pkthucung`;

/*Table structure for table `cages` */

DROP TABLE IF EXISTS `cages`;

CREATE TABLE `cages` (
  `cage_id` int(11) NOT NULL AUTO_INCREMENT,
  `malong` varchar(20) NOT NULL,
  `ten_long` varchar(50) NOT NULL,
  `loailong` enum('THUONG','CAP_CUU_ICU','CACH_LY') DEFAULT 'THUONG',
  `succhua` int(11) NOT NULL DEFAULT 1,
  `soluonghientai` int(11) DEFAULT 0,
  `trangthai` enum('CON_TRONG','DA_CO_THU','DANG_VE_SINH','BAO_TRI') DEFAULT 'CON_TRONG',
  PRIMARY KEY (`cage_id`),
  UNIQUE KEY `malong` (`malong`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `cages` */

insert  into `cages`(`cage_id`,`malong`,`ten_long`,`loailong`,`succhua`,`soluonghientai`,`trangthai`) values 
(1,'L01','Lồng cún nhỏ A1','THUONG',1,0,'CON_TRONG');

/*Table structure for table `inventory` */

DROP TABLE IF EXISTS `inventory`;

CREATE TABLE `inventory` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `masanpham` varchar(20) NOT NULL,
  `ten_sanpham` varchar(100) NOT NULL,
  `danhmuc` enum('THUOC','VAC_XIN','THUC_AN','VAT_TU','DICH_VU_SPA') NOT NULL,
  `donvitinh` varchar(20) NOT NULL,
  `dongia` decimal(12,2) NOT NULL,
  `tonkhotoithieu` int(11) DEFAULT 10,
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `masanpham` (`masanpham`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `inventory` */

/*Table structure for table `inventory_batches` */

DROP TABLE IF EXISTS `inventory_batches`;

CREATE TABLE `inventory_batches` (
  `batch_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `solo` varchar(50) NOT NULL,
  `soluong` int(11) NOT NULL DEFAULT 0,
  `ngaynhap` date NOT NULL,
  `ngayhethan` date NOT NULL,
  `trangthai` enum('DANG_SU_DUNG','HET_HAN','DA_TIEU_HUY') DEFAULT 'DANG_SU_DUNG',
  PRIMARY KEY (`batch_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `inventory_batches_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`item_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `inventory_batches` */

/*Table structure for table `invoices` */

DROP TABLE IF EXISTS `invoices`;

CREATE TABLE `invoices` (
  `invoice_id` int(11) NOT NULL AUTO_INCREMENT,
  `mahoadon` varchar(20) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `tongtien` decimal(12,2) NOT NULL,
  `phuongthucthanhtoan` enum('TIEN_MAT','CHUYEN_KHOAN','THE') DEFAULT 'TIEN_MAT',
  `trangthai` enum('CHO_THANH_TOAN','DA_THANH_TOAN','DA_HUY') DEFAULT 'DA_THANH_TOAN',
  `ngaytao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`invoice_id`),
  UNIQUE KEY `mahoadon` (`mahoadon`),
  KEY `pet_id` (`pet_id`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`),
  CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `owners` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `invoices` */

/*Table structure for table `medical_records` */

DROP TABLE IF EXISTS `medical_records`;

CREATE TABLE `medical_records` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT,
  `pet_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `ngaykham` timestamp NOT NULL DEFAULT current_timestamp(),
  `trieuchung` text DEFAULT NULL,
  `chandoan` text NOT NULL,
  `cannangkhi_kham` decimal(5,2) NOT NULL,
  `trangthai` enum('DANG_DIEU_TRI','HOAN_THANH','CHUYEN_VIEN') DEFAULT 'HOAN_THANH',
  PRIMARY KEY (`record_id`),
  KEY `pet_id` (`pet_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`),
  CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `medical_records` */

/*Table structure for table `owners` */

DROP TABLE IF EXISTS `owners`;

CREATE TABLE `owners` (
  `owner_id` int(11) NOT NULL AUTO_INCREMENT,
  `ho_ten` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `diachi` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ngaytao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`owner_id`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `owners` */

/*Table structure for table `personal_access_tokens` */

DROP TABLE IF EXISTS `personal_access_tokens`;

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `personal_access_tokens` */

insert  into `personal_access_tokens`(`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) values 
(1,'App\\Models\\User',1,'auth_token','425fda1213c4b6934e2940a22881342b9052d60c520c69b44ec8a6079ae4fbb3','[\"*\"]',NULL,NULL,'2026-11-05 04:41:57','2026-11-05 04:41:57');

/*Table structure for table `pets` */

DROP TABLE IF EXISTS `pets`;

CREATE TABLE `pets` (
  `pet_id` int(11) NOT NULL AUTO_INCREMENT,
  `mathucung` varchar(20) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `cage_id` int(11) DEFAULT NULL,
  `ten_thucung` varchar(50) NOT NULL,
  `loai` varchar(30) NOT NULL,
  `giong` varchar(50) DEFAULT NULL,
  `gioitinh` enum('DUC','CAI','CHUA_XAC_DINH') DEFAULT 'CHUA_XAC_DINH',
  `ngaysinh` date DEFAULT NULL,
  `machip` varchar(50) DEFAULT NULL,
  `trangthai` enum('NOI_TRU','NGOAI_TRU','DA_XUAT_VIEN','CHUYEN_VIEN') DEFAULT 'NGOAI_TRU',
  `ngaytao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`pet_id`),
  UNIQUE KEY `mathucung` (`mathucung`),
  UNIQUE KEY `machip` (`machip`),
  KEY `owner_id` (`owner_id`),
  KEY `cage_id` (`cage_id`),
  CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `owners` (`owner_id`) ON DELETE CASCADE,
  CONSTRAINT `pets_ibfk_2` FOREIGN KEY (`cage_id`) REFERENCES `cages` (`cage_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `pets` */

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `vaitro` enum('QUAN_TRI','BAC_SI','NHAN_VIEN') NOT NULL DEFAULT 'NHAN_VIEN',
  `trangthai` enum('HOAT_DONG','TAM_NGUNG','KHOA') DEFAULT 'HOAT_DONG',
  `phai_doimatkhau` tinyint(1) DEFAULT 1,
  `ngaytao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `users` */

insert  into `users`(`user_id`,`username`,`mat_khau`,`ho_ten`,`email`,`phone`,`vaitro`,`trangthai`,`phai_doimatkhau`,`ngaytao`) values 
(1,'admin','$2y$12$BOZpIHq05kUry/rrTkFNa.Igag/R1syn5TDrRl0bNsDNTy4MkcapS','Quản trị viên','admin@clinic.com','0901234567','QUAN_TRI','HOAT_DONG',0,'2026-11-04 13:47:38');

/*Table structure for table `weight_logs` */

DROP TABLE IF EXISTS `weight_logs`;

CREATE TABLE `weight_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pet_id` int(11) NOT NULL,
  `cannang` decimal(5,2) NOT NULL,
  `ngaydo` timestamp NOT NULL DEFAULT current_timestamp(),
  `nguoido` int(11) NOT NULL,
  `ghichu` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pet_id` (`pet_id`),
  KEY `nguoido` (`nguoido`),
  CONSTRAINT `weight_logs_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE,
  CONSTRAINT `weight_logs_ibfk_2` FOREIGN KEY (`nguoido`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `weight_logs` */

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
