/*
SQLyog Community v13.3.1 (64 bit)
MySQL - 10.4.32-MariaDB : Database - pk_thu_cung
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`pk_thu_cung` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `pk_thu_cung`;

/*Table structure for table `cache` */

DROP TABLE IF EXISTS `cache`;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `cache` */

/*Table structure for table `cache_locks` */

DROP TABLE IF EXISTS `cache_locks`;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `cache_locks` */

/*Table structure for table `cages` */

DROP TABLE IF EXISTS `cages`;

CREATE TABLE `cages` (
  `cage_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `malong` varchar(20) NOT NULL,
  `ten_long` varchar(50) NOT NULL,
  `loailong` enum('THUONG','CAP_CUU_ICU','CACH_LY') NOT NULL DEFAULT 'THUONG',
  `succhua` int(11) NOT NULL DEFAULT 1,
  `soluonghientai` int(11) NOT NULL DEFAULT 0,
  `trangthai` enum('CON_TRONG','DA_CO_THU','DANG_VE_SINH','BAO_TRI') NOT NULL DEFAULT 'CON_TRONG',
  PRIMARY KEY (`cage_id`),
  UNIQUE KEY `cages_malong_unique` (`malong`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `cages` */

/*Table structure for table `failed_jobs` */

DROP TABLE IF EXISTS `failed_jobs`;

CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `failed_jobs` */

/*Table structure for table `inventory` */

DROP TABLE IF EXISTS `inventory`;

CREATE TABLE `inventory` (
  `item_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `masanpham` varchar(20) NOT NULL,
  `ten_sanpham` varchar(100) NOT NULL,
  `danhmuc` enum('THUOC','VAC_XIN','THUC_AN','VAT_TU','DICH_VU_SPA') NOT NULL,
  `donvitinh` varchar(20) NOT NULL,
  `dongia` decimal(12,2) NOT NULL,
  `lieu_mg_kg` decimal(10,2) DEFAULT NULL,
  `mg_moi_don_vi` decimal(10,2) DEFAULT NULL,
  `tonkhotoithieu` int(11) NOT NULL DEFAULT 10,
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `inventory_masanpham_unique` (`masanpham`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `inventory` */

/*Table structure for table `inventory_batches` */

DROP TABLE IF EXISTS `inventory_batches`;

CREATE TABLE `inventory_batches` (
  `batch_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` bigint(20) unsigned NOT NULL,
  `solo` varchar(50) NOT NULL,
  `soluong` int(11) NOT NULL DEFAULT 0,
  `ngaynhap` date NOT NULL,
  `ngayhethan` date NOT NULL,
  `trangthai` enum('DANG_SU_DUNG','HET_HAN','DA_TIEU_HUY') NOT NULL DEFAULT 'DANG_SU_DUNG',
  PRIMARY KEY (`batch_id`),
  KEY `inventory_batches_item_id_foreign` (`item_id`),
  CONSTRAINT `inventory_batches_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`item_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `inventory_batches` */

/*Table structure for table `invoice_details` */

DROP TABLE IF EXISTS `invoice_details`;

CREATE TABLE `invoice_details` (
  `detail_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `prescription_id` bigint(20) unsigned DEFAULT NULL,
  `loai_chiphi` enum('THUOC','LUU_TRU') NOT NULL DEFAULT 'THUOC',
  `ten_sanpham` varchar(100) NOT NULL,
  `donvitinh` varchar(20) NOT NULL,
  `soluong` int(10) unsigned NOT NULL,
  `dongia` decimal(12,2) NOT NULL,
  `thanhtien` decimal(12,2) NOT NULL,
  `ghichu` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`detail_id`),
  UNIQUE KEY `invoice_details_prescription_id_unique` (`prescription_id`),
  KEY `invoice_details_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `invoice_details_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_details_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`prescription_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `invoice_details` */

/*Table structure for table `invoices` */

DROP TABLE IF EXISTS `invoices`;

CREATE TABLE `invoices` (
  `invoice_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mahoadon` varchar(20) NOT NULL,
  `pet_id` bigint(20) unsigned NOT NULL,
  `owner_id` bigint(20) unsigned NOT NULL,
  `tongtien` decimal(12,2) NOT NULL,
  `phuongthucthanhtoan` enum('TIEN_MAT','CHUYEN_KHOAN','THE') NOT NULL DEFAULT 'TIEN_MAT',
  `trangthai` enum('CHO_THANH_TOAN','DA_THANH_TOAN','DA_HUY') NOT NULL DEFAULT 'DA_THANH_TOAN',
  `ngaytao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`invoice_id`),
  UNIQUE KEY `invoices_mahoadon_unique` (`mahoadon`),
  KEY `invoices_pet_id_foreign` (`pet_id`),
  KEY `invoices_owner_id_foreign` (`owner_id`),
  CONSTRAINT `invoices_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `owners` (`owner_id`),
  CONSTRAINT `invoices_pet_id_foreign` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `invoices` */

/*Table structure for table `job_batches` */

DROP TABLE IF EXISTS `job_batches`;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `job_batches` */

/*Table structure for table `jobs` */

DROP TABLE IF EXISTS `jobs`;

CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `jobs` */

/*Table structure for table `medical_records` */

DROP TABLE IF EXISTS `medical_records`;

CREATE TABLE `medical_records` (
  `record_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pet_id` bigint(20) unsigned NOT NULL,
  `doctor_id` bigint(20) unsigned NOT NULL,
  `ngaykham` timestamp NOT NULL DEFAULT current_timestamp(),
  `trieuchung` text DEFAULT NULL,
  `chandoan` text NOT NULL,
  `cannangkhi_kham` decimal(5,2) NOT NULL,
  `trangthai` enum('DANG_DIEU_TRI','HOAN_THANH','CHUYEN_VIEN') NOT NULL DEFAULT 'HOAN_THANH',
  PRIMARY KEY (`record_id`),
  KEY `medical_records_pet_id_foreign` (`pet_id`),
  KEY `medical_records_doctor_id_foreign` (`doctor_id`),
  CONSTRAINT `medical_records_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `medical_records_pet_id_foreign` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `medical_records` */

/*Table structure for table `migrations` */

DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `migrations` */

insert  into `migrations`(`id`,`migration`,`batch`) values 
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2026_11_05_034403_create_personal_access_tokens_table',1),
(5,'2026_11_10_070719_create_owners_table',1),
(6,'2026_11_10_070800_create_cages_table',1),
(7,'2026_11_10_070802_create_pets_table',1),
(8,'2026_11_10_070826_create_pet_boardings_table',1),
(9,'2026_11_10_070830_create_weight_logs_table',1),
(10,'2026_11_11_185340_create_invoices_table',1),
(11,'2026_11_11_185455_create_medical_records_table',1),
(12,'2026_11_11_185520_create_inventory_table',1),
(13,'2026_11_11_185524_create_inventory_batches_table',1),
(14,'2026_11_11_185530_create_prescriptions_table',1),
(15,'2026_11_12_152720_add_foreign_keys_to_prescriptions_table',1),
(16,'2026_11_12_152723_create_invoice_details_table',1),
(17,'2026_11_12_153210_update_invoice_details_for_boarding_fee',1),
(18,'2026_11_12_160046_add_dosage_fields_to_inventory_table',1);

/*Table structure for table `owners` */

DROP TABLE IF EXISTS `owners`;

CREATE TABLE `owners` (
  `owner_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ho_ten` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `diachi` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ngaytao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`owner_id`),
  UNIQUE KEY `owners_phone_unique` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `owners` */

/*Table structure for table `password_reset_tokens` */

DROP TABLE IF EXISTS `password_reset_tokens`;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `password_reset_tokens` */

/*Table structure for table `personal_access_tokens` */

DROP TABLE IF EXISTS `personal_access_tokens`;

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `personal_access_tokens` */

/*Table structure for table `pet_boardings` */

DROP TABLE IF EXISTS `pet_boardings`;

CREATE TABLE `pet_boardings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pet_id` bigint(20) unsigned NOT NULL,
  `cage_id` bigint(20) unsigned NOT NULL,
  `check_in` datetime NOT NULL,
  `check_out` datetime DEFAULT NULL,
  `status` enum('checked_in','checked_out') NOT NULL DEFAULT 'checked_in',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pet_boardings_pet_id_foreign` (`pet_id`),
  KEY `pet_boardings_cage_id_foreign` (`cage_id`),
  CONSTRAINT `pet_boardings_cage_id_foreign` FOREIGN KEY (`cage_id`) REFERENCES `cages` (`cage_id`) ON DELETE CASCADE,
  CONSTRAINT `pet_boardings_pet_id_foreign` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `pet_boardings` */

/*Table structure for table `pets` */

DROP TABLE IF EXISTS `pets`;

CREATE TABLE `pets` (
  `pet_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mathucung` varchar(20) NOT NULL,
  `owner_id` bigint(20) unsigned NOT NULL,
  `cage_id` bigint(20) unsigned DEFAULT NULL,
  `ten_thucung` varchar(50) NOT NULL,
  `loai` varchar(30) NOT NULL,
  `giong` varchar(50) DEFAULT NULL,
  `gioitinh` enum('DUC','CAI','CHUA_XAC_DINH') NOT NULL DEFAULT 'CHUA_XAC_DINH',
  `ngaysinh` date DEFAULT NULL,
  `machip` varchar(50) DEFAULT NULL,
  `trangthai` enum('NOI_TRU','NGOAI_TRU','DA_XUAT_VIEN','CHUYEN_VIEN') NOT NULL DEFAULT 'NGOAI_TRU',
  `ngaytao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`pet_id`),
  UNIQUE KEY `pets_mathucung_unique` (`mathucung`),
  UNIQUE KEY `pets_machip_unique` (`machip`),
  KEY `pets_owner_id_foreign` (`owner_id`),
  KEY `pets_cage_id_foreign` (`cage_id`),
  CONSTRAINT `pets_cage_id_foreign` FOREIGN KEY (`cage_id`) REFERENCES `cages` (`cage_id`) ON DELETE SET NULL,
  CONSTRAINT `pets_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `owners` (`owner_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `pets` */

/*Table structure for table `prescriptions` */

DROP TABLE IF EXISTS `prescriptions`;

CREATE TABLE `prescriptions` (
  `prescription_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `record_id` bigint(20) unsigned NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `soluong` int(10) unsigned NOT NULL,
  `huongdan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`prescription_id`),
  KEY `prescriptions_record_id_foreign` (`record_id`),
  KEY `prescriptions_item_id_foreign` (`item_id`),
  KEY `prescriptions_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `prescriptions_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE SET NULL,
  CONSTRAINT `prescriptions_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`item_id`),
  CONSTRAINT `prescriptions_record_id_foreign` FOREIGN KEY (`record_id`) REFERENCES `medical_records` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `prescriptions` */

/*Table structure for table `sessions` */

DROP TABLE IF EXISTS `sessions`;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `sessions` */

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `vaitro` enum('QUAN_TRI','BAC_SI','NHAN_VIEN') NOT NULL,
  `trangthai` enum('HOAT_DONG','TAM_NGUNG','KHOA') NOT NULL DEFAULT 'HOAT_DONG',
  `phai_doimatkhau` tinyint(1) NOT NULL DEFAULT 1,
  `ngaytao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `users` */

/*Table structure for table `weight_logs` */

DROP TABLE IF EXISTS `weight_logs`;

CREATE TABLE `weight_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pet_id` bigint(20) unsigned NOT NULL,
  `cannang` decimal(5,2) NOT NULL,
  `ngaydo` timestamp NOT NULL DEFAULT current_timestamp(),
  `nguoido` bigint(20) unsigned NOT NULL,
  `ghichu` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `weight_logs_pet_id_foreign` (`pet_id`),
  KEY `weight_logs_nguoido_foreign` (`nguoido`),
  CONSTRAINT `weight_logs_nguoido_foreign` FOREIGN KEY (`nguoido`) REFERENCES `users` (`user_id`),
  CONSTRAINT `weight_logs_pet_id_foreign` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `weight_logs` */

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
