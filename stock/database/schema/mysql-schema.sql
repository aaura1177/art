/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `allocation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `allocation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contractor_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `quantity` double(8,2) NOT NULL DEFAULT 0.00,
  `finish` varchar(191) DEFAULT NULL,
  `refno` varchar(191) DEFAULT NULL,
  `ucost` double(12,2) NOT NULL DEFAULT 0.00,
  `vol_unit` varchar(191) DEFAULT NULL,
  `tvol` double(12,4) NOT NULL DEFAULT 0.0000,
  `tcost` double(12,2) NOT NULL DEFAULT 0.00,
  `remarks` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `allocation_contractor_id_foreign` (`contractor_id`),
  KEY `allocation_product_id_foreign` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `buyers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `buyers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(191) NOT NULL,
  `c_name` varchar(191) NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `address1` varchar(191) DEFAULT NULL,
  `address2` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `state` varchar(191) DEFAULT NULL,
  `country` varchar(191) DEFAULT NULL,
  `postcode` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `buyertype` int(11) NOT NULL DEFAULT 0,
  `gstno` varchar(255) DEFAULT NULL,
  `state_code` int(11) DEFAULT NULL,
  `is_uk` int(11) NOT NULL DEFAULT 0,
  `is_us` tinyint(4) NOT NULL DEFAULT 0,
  `is_eu` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `certificate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificate` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `channel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `channel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `consumables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `consumables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) DEFAULT NULL,
  `unit` varchar(191) DEFAULT NULL,
  `rate` float DEFAULT NULL,
  `supplier` int(11) DEFAULT NULL,
  `payment_terms` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `quantity` double(8,2) DEFAULT NULL,
  `is_deleted` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contractor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractor` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `c_name` varchar(191) NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `address1` varchar(191) DEFAULT NULL,
  `address2` varchar(191) DEFAULT NULL,
  `city` varchar(191) NOT NULL,
  `state` varchar(191) NOT NULL,
  `country` varchar(191) NOT NULL,
  `postcode` varchar(191) DEFAULT NULL,
  `gst` tinyint(1) NOT NULL DEFAULT 0,
  `gstin` varchar(191) DEFAULT NULL,
  `state_code` varchar(191) DEFAULT NULL,
  `pan` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone1` bigint(20) DEFAULT NULL,
  `phone2` bigint(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tds` int(11) DEFAULT NULL,
  `tdspercent` double(8,2) DEFAULT NULL,
  `gstpercent` double(8,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contractor_bill`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contractor_bill` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `amount` double(8,2) DEFAULT NULL,
  `finishing_rate` double(8,2) DEFAULT NULL,
  `finishing` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `corner_bill`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `corner_bill` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `corners` int(11) DEFAULT NULL,
  `total_corners` int(11) DEFAULT NULL,
  `total_corners_amount` double(8,2) DEFAULT NULL,
  `l` int(11) DEFAULT NULL,
  `total_l` int(11) DEFAULT NULL,
  `total_l_amount` double(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `product_quantity` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cornerpackaging`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cornerpackaging` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_ids` text DEFAULT NULL,
  `month` varchar(191) DEFAULT NULL,
  `po_no` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cornerpackaging_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cornerpackaging_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cornerpackaging_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `amount` float DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `corner_quantity` int(11) DEFAULT NULL,
  `l_quantity` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `courier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courier` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `rate` double NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `rate_per_kg` double(8,2) DEFAULT NULL,
  `fixed_rate_weight` double(8,2) DEFAULT NULL,
  `fuel_charge_percent` double(8,2) NOT NULL DEFAULT 0.00,
  `country` varchar(50) NOT NULL DEFAULT 'UK',
  `is_default` int(11) NOT NULL DEFAULT 0,
  `custom_condition` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `credit_note_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `credit_note_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `credit_note_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rate` double(8,2) NOT NULL,
  `tax` double(8,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `amount` double(8,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `credit_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `credit_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `num` varchar(191) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `total_quantity` int(11) NOT NULL,
  `amount` double(8,2) NOT NULL,
  `total_amount` double(8,2) NOT NULL,
  `total_tax` double(8,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `erp_eu_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `erp_eu_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(191) NOT NULL,
  `quantity` int(11) NOT NULL,
  `zone_name` varchar(191) NOT NULL DEFAULT 'MEZZ',
  `zone_serial` varchar(191) NOT NULL DEFAULT 'artisan-4000 3009900003500',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `eu_quantity` int(11) DEFAULT NULL,
  `product_type` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `erp_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `erp_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sheet_id` int(11) NOT NULL,
  `sku` varchar(191) NOT NULL,
  `quantity` int(11) NOT NULL,
  `type` varchar(191) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `reason` varchar(191) DEFAULT '',
  `site_access` varchar(50) NOT NULL DEFAULT 'UK',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `erp_history_copy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `erp_history_copy` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sheet_id` int(11) NOT NULL,
  `sku` varchar(191) NOT NULL,
  `quantity` int(11) NOT NULL,
  `type` varchar(191) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `reason` varchar(191) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `erp_history_manager`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `erp_history_manager` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sheet_id` int(11) NOT NULL,
  `sku` varchar(191) NOT NULL,
  `quantity` int(11) NOT NULL,
  `type` varchar(191) NOT NULL,
  `date` date NOT NULL,
  `remark` text DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reason` varchar(191) DEFAULT NULL,
  `site_access` varchar(50) NOT NULL DEFAULT 'UK',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `erp_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `erp_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(191) NOT NULL,
  `quantity` int(11) NOT NULL,
  `zone_name` varchar(191) NOT NULL DEFAULT 'MEZZ',
  `zone_serial` varchar(191) NOT NULL DEFAULT 'artisan-4000 3009900003500',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `warehouse_quantity` int(11) DEFAULT NULL,
  `product_type` varchar(191) DEFAULT NULL,
  `fullfillment_qty` int(11) NOT NULL DEFAULT 0,
  `site_access` varchar(50) NOT NULL DEFAULT 'UK',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `erp_sheets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `erp_sheets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `date` date NOT NULL,
  `type` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `site_access` varchar(50) NOT NULL DEFAULT 'UK',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `erp_sheets_manager`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `erp_sheets_manager` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `date` date NOT NULL,
  `type` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `site_access` varchar(50) NOT NULL DEFAULT 'UK',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `erp_us_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `erp_us_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sheet_id` int(11) NOT NULL,
  `sku` varchar(191) NOT NULL,
  `quantity` int(11) NOT NULL,
  `type` varchar(191) NOT NULL,
  `date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `reason` varchar(191) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `erp_us_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `erp_us_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(191) NOT NULL,
  `quantity` int(11) NOT NULL,
  `zone_name` varchar(191) NOT NULL DEFAULT 'MEZZ',
  `zone_serial` varchar(191) NOT NULL DEFAULT 'artisan-4000 3009900003500',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `us_quantity` int(11) DEFAULT NULL,
  `product_type` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `erp_us_sheets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `erp_us_sheets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `date` date NOT NULL,
  `type` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `erp_us_sheets_manager`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `erp_us_sheets_manager` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `date` date NOT NULL,
  `type` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `finishing_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `finishing_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) DEFAULT NULL,
  `rate` double(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hardware_suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hardware_suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hardwares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hardwares` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `rate` double NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `location` varchar(191) DEFAULT NULL,
  `hardware_supplier` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `interested_company`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `interested_company` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `establishment_date` date DEFAULT NULL,
  `contact_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `phone` bigint(20) DEFAULT NULL,
  `website_link` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `location` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `product_type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `work_with_companies_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `interested_company_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `interested_company_photos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `interested_company_id` int(10) unsigned NOT NULL,
  `photo` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoiceno` varchar(191) NOT NULL,
  `date` date DEFAULT NULL,
  `consignee_id` varchar(191) DEFAULT NULL,
  `buyer_id` int(10) unsigned NOT NULL,
  `buyerorderno` varchar(191) DEFAULT NULL,
  `containerno` varchar(191) DEFAULT NULL,
  `vehicleno` varchar(191) DEFAULT NULL,
  `ewaybillno` varchar(191) DEFAULT NULL,
  `pkgs` varchar(191) DEFAULT NULL,
  `currency` varchar(191) DEFAULT NULL,
  `conrate` double DEFAULT NULL,
  `declaration` longtext NOT NULL,
  `fob` varchar(191) DEFAULT NULL,
  `payterms` varchar(191) DEFAULT NULL,
  `shipmentby` varchar(191) DEFAULT NULL,
  `desgoods` text DEFAULT NULL,
  `carriage` varchar(191) DEFAULT NULL,
  `receipt` varchar(191) DEFAULT NULL,
  `shipment` varchar(191) DEFAULT NULL,
  `postloading` varchar(191) DEFAULT NULL,
  `discharge` varchar(191) DEFAULT NULL,
  `destination` varchar(191) DEFAULT NULL,
  `shipping_charges` double(12,2) DEFAULT NULL,
  `packing_charges` double(12,2) DEFAULT NULL,
  `discount` double(12,2) DEFAULT NULL,
  `totalamount` double(12,2) DEFAULT NULL,
  `totalgst` double(12,2) DEFAULT NULL,
  `rateamount` double(12,2) DEFAULT NULL,
  `totalquantity` double DEFAULT NULL,
  `totalwt` double(12,2) DEFAULT NULL,
  `totalgrosswt` double(12,2) DEFAULT NULL,
  `totalbox` int(11) DEFAULT NULL,
  `status` int(10) unsigned NOT NULL DEFAULT 0,
  `invoicetype` int(11) NOT NULL,
  `exportstatus` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `shipping_bill_no` varchar(191) DEFAULT NULL,
  `shipping_bill_date` date DEFAULT NULL,
  `ewaybilldate` date DEFAULT NULL,
  `port_code` varchar(191) DEFAULT NULL,
  `shipping_exchange_rate` double(8,2) DEFAULT NULL,
  `additional_info` text DEFAULT NULL,
  `booking_value` double(20,2) DEFAULT NULL,
  `fbc` varchar(191) DEFAULT NULL,
  `shipdawn` date DEFAULT NULL,
  `inrat_booking_value` double(20,2) DEFAULT NULL,
  `tax_type` varchar(191) DEFAULT 'IGST',
  `bl_no` varchar(191) DEFAULT NULL,
  `bl_date` date DEFAULT NULL,
  `egm_no` varchar(191) DEFAULT NULL,
  `egm_date` date DEFAULT NULL,
  `agent_name` varchar(191) DEFAULT NULL,
  `eta` date DEFAULT NULL,
  `etd` date DEFAULT NULL,
  `irn` varchar(191) DEFAULT '',
  `ack_no` varchar(191) DEFAULT '',
  `ack_date` varchar(191) DEFAULT NULL,
  `einvoice_qr` varchar(191) DEFAULT '',
  `lr_rr_no` varchar(191) DEFAULT '',
  `billty_no` varchar(191) DEFAULT NULL,
  `billty_date` date DEFAULT NULL,
  `agent` varchar(191) DEFAULT NULL,
  `port_of_loading` varchar(191) DEFAULT NULL,
  `port_of_discharge` varchar(191) DEFAULT NULL,
  `bill_to` varchar(191) DEFAULT NULL,
  `ship_to` varchar(191) DEFAULT NULL,
  `send_mail` int(11) NOT NULL DEFAULT 0,
  `shipping_gst` double(8,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `invoice_buyer_id_foreign` (`buyer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoiceTable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoiceTable` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `rate` double(12,2) NOT NULL,
  `amount` double(12,2) NOT NULL,
  `weight` double(12,2) DEFAULT NULL,
  `subtotalnetwt` double(12,2) DEFAULT NULL,
  `grosswt` double(12,2) DEFAULT NULL,
  `subtotalgrosswt` double(12,2) DEFAULT NULL,
  `gstslab` double(12,2) DEFAULT NULL,
  `gstamount` double(12,2) DEFAULT NULL,
  `box` int(11) NOT NULL DEFAULT 0,
  `endbox` int(11) NOT NULL DEFAULT 0,
  `subtotalbox` int(11) NOT NULL DEFAULT 0,
  `qtybox` varchar(191) NOT NULL DEFAULT '1 Pc/Box',
  `remqty` int(11) NOT NULL,
  `descriptionBox` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `finishing_price` double(8,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoicetable_invoice_id_foreign` (`invoice_id`),
  KEY `invoicetable_product_id_foreign` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoiceTable_eu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoiceTable_eu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `rate` double(12,2) NOT NULL,
  `amount` double(12,2) NOT NULL,
  `weight` double(12,2) DEFAULT NULL,
  `subtotalnetwt` double(12,2) DEFAULT NULL,
  `grosswt` double(12,2) DEFAULT NULL,
  `subtotalgrosswt` double(12,2) DEFAULT NULL,
  `gstslab` double(12,2) DEFAULT NULL,
  `gstamount` double(12,2) DEFAULT NULL,
  `box` int(11) NOT NULL DEFAULT 0,
  `endbox` int(11) NOT NULL DEFAULT 0,
  `subtotalbox` int(11) NOT NULL DEFAULT 0,
  `qtybox` varchar(191) NOT NULL DEFAULT '1 Pc/Box',
  `remqty` int(11) NOT NULL,
  `descriptionBox` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `finishing_price` double(8,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoicetable_invoice_id_foreign` (`invoice_id`),
  KEY `invoicetable_product_id_foreign` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoiceTable_uk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoiceTable_uk` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `rate` double(12,2) NOT NULL,
  `amount` double(12,2) NOT NULL,
  `weight` double(12,2) DEFAULT NULL,
  `subtotalnetwt` double(12,2) DEFAULT NULL,
  `grosswt` double(12,2) DEFAULT NULL,
  `subtotalgrosswt` double(12,2) DEFAULT NULL,
  `gstslab` double(12,2) DEFAULT NULL,
  `gstamount` double(12,2) DEFAULT NULL,
  `box` int(11) NOT NULL DEFAULT 0,
  `endbox` int(11) NOT NULL DEFAULT 0,
  `subtotalbox` int(11) NOT NULL DEFAULT 0,
  `qtybox` varchar(191) NOT NULL DEFAULT '1 Pc/Box',
  `remqty` int(11) NOT NULL,
  `descriptionBox` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `finishing_price` double(8,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoicetable_invoice_id_foreign` (`invoice_id`),
  KEY `invoicetable_product_id_foreign` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoiceTable_us`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoiceTable_us` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `rate` double(12,2) NOT NULL,
  `amount` double(12,2) NOT NULL,
  `weight` double(12,2) DEFAULT NULL,
  `subtotalnetwt` double(12,2) DEFAULT NULL,
  `grosswt` double(12,2) DEFAULT NULL,
  `subtotalgrosswt` double(12,2) DEFAULT NULL,
  `gstslab` double(12,2) DEFAULT NULL,
  `gstamount` double(12,2) DEFAULT NULL,
  `box` int(11) NOT NULL DEFAULT 0,
  `endbox` int(11) NOT NULL DEFAULT 0,
  `subtotalbox` int(11) NOT NULL DEFAULT 0,
  `qtybox` varchar(191) NOT NULL DEFAULT '1 Pc/Box',
  `remqty` int(11) NOT NULL,
  `descriptionBox` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `finishing_price` double(8,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoicetable_invoice_id_foreign` (`invoice_id`),
  KEY `invoicetable_product_id_foreign` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_eu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_eu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoiceno` varchar(191) NOT NULL,
  `date` date DEFAULT NULL,
  `consignee_id` varchar(191) DEFAULT NULL,
  `buyer_id` int(10) unsigned DEFAULT NULL,
  `buyerorderno` varchar(191) DEFAULT NULL,
  `containerno` varchar(191) DEFAULT NULL,
  `vehicleno` varchar(191) DEFAULT NULL,
  `ewaybillno` varchar(191) DEFAULT NULL,
  `pkgs` varchar(191) DEFAULT NULL,
  `currency` varchar(191) DEFAULT NULL,
  `conrate` double DEFAULT NULL,
  `declaration` longtext DEFAULT NULL,
  `fob` varchar(191) DEFAULT NULL,
  `payterms` varchar(191) DEFAULT NULL,
  `shipmentby` varchar(191) DEFAULT NULL,
  `desgoods` text DEFAULT NULL,
  `carriage` varchar(191) DEFAULT NULL,
  `receipt` varchar(191) DEFAULT NULL,
  `shipment` varchar(191) DEFAULT NULL,
  `postloading` varchar(191) DEFAULT NULL,
  `discharge` varchar(191) DEFAULT NULL,
  `destination` varchar(191) DEFAULT NULL,
  `shipping_charges` double(12,2) DEFAULT NULL,
  `packing_charges` double(12,2) DEFAULT NULL,
  `discount` double(12,2) DEFAULT NULL,
  `totalamount` double(12,2) DEFAULT NULL,
  `totalgst` double(12,2) DEFAULT NULL,
  `rateamount` double(12,2) DEFAULT NULL,
  `totalquantity` double DEFAULT NULL,
  `totalwt` double(12,2) DEFAULT NULL,
  `totalgrosswt` double(12,2) DEFAULT NULL,
  `totalbox` int(11) DEFAULT NULL,
  `status` int(10) unsigned NOT NULL DEFAULT 0,
  `invoicetype` int(11) NOT NULL,
  `exportstatus` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `shipping_bill_no` varchar(191) DEFAULT NULL,
  `shipping_bill_date` date DEFAULT NULL,
  `ewaybilldate` date DEFAULT NULL,
  `port_code` varchar(191) DEFAULT NULL,
  `shipping_exchange_rate` double(8,2) DEFAULT NULL,
  `additional_info` text DEFAULT NULL,
  `booking_value` double(20,2) DEFAULT NULL,
  `fbc` varchar(191) DEFAULT NULL,
  `shipdawn` date DEFAULT NULL,
  `inrat_booking_value` double(20,2) DEFAULT NULL,
  `tax_type` varchar(191) DEFAULT 'IGST',
  `bl_no` varchar(191) DEFAULT NULL,
  `bl_date` date DEFAULT NULL,
  `egm_no` varchar(191) DEFAULT NULL,
  `egm_date` date DEFAULT NULL,
  `agent_name` varchar(191) DEFAULT NULL,
  `eta` date DEFAULT NULL,
  `etd` date DEFAULT NULL,
  `irn` varchar(191) DEFAULT '',
  `ack_no` varchar(191) DEFAULT '',
  `ack_date` varchar(191) DEFAULT NULL,
  `einvoice_qr` varchar(191) DEFAULT '',
  `lr_rr_no` varchar(191) DEFAULT '',
  `billty_no` varchar(191) DEFAULT NULL,
  `billty_date` date DEFAULT NULL,
  `agent` varchar(191) DEFAULT NULL,
  `port_of_loading` varchar(191) DEFAULT NULL,
  `port_of_discharge` varchar(191) DEFAULT NULL,
  `bill_to` text DEFAULT NULL,
  `ship_to` text DEFAULT NULL,
  `send_mail` int(11) NOT NULL DEFAULT 0,
  `container_size` varchar(191) DEFAULT NULL,
  `delivery_term` varchar(191) DEFAULT NULL,
  `deposit` double(8,2) DEFAULT NULL,
  `deposit_date` date DEFAULT NULL,
  `vat` double(8,2) NOT NULL DEFAULT 0.00,
  `refund_statement` varchar(191) DEFAULT NULL,
  `refund` double(8,2) NOT NULL DEFAULT 0.00,
  `oceanic_freight` double(8,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `invoice_buyer_id_foreign` (`buyer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_export`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_export` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `realisation_date` date DEFAULT NULL,
  `realisation_fc` varchar(191) DEFAULT NULL,
  `rate` double(8,2) DEFAULT NULL,
  `bank_reference` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `invoice_id` int(11) NOT NULL,
  `fbc` varchar(191) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_uk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_uk` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoiceno` varchar(191) NOT NULL,
  `date` date DEFAULT NULL,
  `consignee_id` varchar(191) DEFAULT NULL,
  `buyer_id` int(10) unsigned DEFAULT NULL,
  `buyerorderno` varchar(191) DEFAULT NULL,
  `containerno` varchar(191) DEFAULT NULL,
  `vehicleno` varchar(191) DEFAULT NULL,
  `ewaybillno` varchar(191) DEFAULT NULL,
  `pkgs` varchar(191) DEFAULT NULL,
  `currency` varchar(191) DEFAULT NULL,
  `conrate` double DEFAULT NULL,
  `declaration` longtext DEFAULT NULL,
  `fob` varchar(191) DEFAULT NULL,
  `payterms` varchar(191) DEFAULT NULL,
  `shipmentby` varchar(191) DEFAULT NULL,
  `desgoods` text DEFAULT NULL,
  `carriage` varchar(191) DEFAULT NULL,
  `receipt` varchar(191) DEFAULT NULL,
  `shipment` varchar(191) DEFAULT NULL,
  `postloading` varchar(191) DEFAULT NULL,
  `discharge` varchar(191) DEFAULT NULL,
  `destination` varchar(191) DEFAULT NULL,
  `shipping_charges` double(12,2) DEFAULT NULL,
  `packing_charges` double(12,2) DEFAULT NULL,
  `discount` double(12,2) DEFAULT NULL,
  `totalamount` double(12,2) DEFAULT NULL,
  `totalgst` double(12,2) DEFAULT NULL,
  `rateamount` double(12,2) DEFAULT NULL,
  `totalquantity` double DEFAULT NULL,
  `totalwt` double(12,2) DEFAULT NULL,
  `totalgrosswt` double(12,2) DEFAULT NULL,
  `totalbox` int(11) DEFAULT NULL,
  `status` int(10) unsigned NOT NULL DEFAULT 0,
  `invoicetype` int(11) NOT NULL,
  `exportstatus` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `shipping_bill_no` varchar(191) DEFAULT NULL,
  `shipping_bill_date` date DEFAULT NULL,
  `ewaybilldate` date DEFAULT NULL,
  `port_code` varchar(191) DEFAULT NULL,
  `shipping_exchange_rate` double(8,2) DEFAULT NULL,
  `additional_info` text DEFAULT NULL,
  `booking_value` double(20,2) DEFAULT NULL,
  `fbc` varchar(191) DEFAULT NULL,
  `shipdawn` date DEFAULT NULL,
  `inrat_booking_value` double(20,2) DEFAULT NULL,
  `tax_type` varchar(191) DEFAULT 'IGST',
  `bl_no` varchar(191) DEFAULT NULL,
  `bl_date` date DEFAULT NULL,
  `egm_no` varchar(191) DEFAULT NULL,
  `egm_date` date DEFAULT NULL,
  `agent_name` varchar(191) DEFAULT NULL,
  `eta` date DEFAULT NULL,
  `etd` date DEFAULT NULL,
  `irn` varchar(191) DEFAULT '',
  `ack_no` varchar(191) DEFAULT '',
  `ack_date` varchar(191) DEFAULT NULL,
  `einvoice_qr` varchar(191) DEFAULT '',
  `lr_rr_no` varchar(191) DEFAULT '',
  `billty_no` varchar(191) DEFAULT NULL,
  `billty_date` date DEFAULT NULL,
  `agent` varchar(191) DEFAULT NULL,
  `port_of_loading` varchar(191) DEFAULT NULL,
  `port_of_discharge` varchar(191) DEFAULT NULL,
  `bill_to` text DEFAULT NULL,
  `ship_to` text DEFAULT NULL,
  `send_mail` int(11) NOT NULL DEFAULT 0,
  `container_size` varchar(191) DEFAULT NULL,
  `delivery_term` varchar(191) DEFAULT NULL,
  `deposit` double(8,2) DEFAULT NULL,
  `deposit_date` date DEFAULT NULL,
  `vat` double(8,2) NOT NULL DEFAULT 0.00,
  `refund_statement` varchar(191) DEFAULT NULL,
  `refund` double(8,2) NOT NULL DEFAULT 0.00,
  `oceanic_freight` double(8,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `invoice_buyer_id_foreign` (`buyer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_us`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_us` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoiceno` varchar(191) NOT NULL,
  `date` date DEFAULT NULL,
  `consignee_id` varchar(191) DEFAULT NULL,
  `buyer_id` int(10) unsigned DEFAULT NULL,
  `buyerorderno` varchar(191) DEFAULT NULL,
  `containerno` varchar(191) DEFAULT NULL,
  `vehicleno` varchar(191) DEFAULT NULL,
  `ewaybillno` varchar(191) DEFAULT NULL,
  `pkgs` varchar(191) DEFAULT NULL,
  `currency` varchar(191) DEFAULT NULL,
  `conrate` double DEFAULT NULL,
  `declaration` longtext DEFAULT NULL,
  `fob` varchar(191) DEFAULT NULL,
  `payterms` varchar(191) DEFAULT NULL,
  `shipmentby` varchar(191) DEFAULT NULL,
  `desgoods` text DEFAULT NULL,
  `carriage` varchar(191) DEFAULT NULL,
  `receipt` varchar(191) DEFAULT NULL,
  `shipment` varchar(191) DEFAULT NULL,
  `postloading` varchar(191) DEFAULT NULL,
  `discharge` varchar(191) DEFAULT NULL,
  `destination` varchar(191) DEFAULT NULL,
  `shipping_charges` double(12,2) DEFAULT NULL,
  `packing_charges` double(12,2) DEFAULT NULL,
  `discount` double(12,2) DEFAULT NULL,
  `totalamount` double(12,2) DEFAULT NULL,
  `totalgst` double(12,2) DEFAULT NULL,
  `rateamount` double(12,2) DEFAULT NULL,
  `totalquantity` double DEFAULT NULL,
  `totalwt` double(12,2) DEFAULT NULL,
  `totalgrosswt` double(12,2) DEFAULT NULL,
  `totalbox` int(11) DEFAULT NULL,
  `status` int(10) unsigned NOT NULL DEFAULT 0,
  `invoicetype` int(11) NOT NULL,
  `exportstatus` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `shipping_bill_no` varchar(191) DEFAULT NULL,
  `shipping_bill_date` date DEFAULT NULL,
  `ewaybilldate` date DEFAULT NULL,
  `port_code` varchar(191) DEFAULT NULL,
  `shipping_exchange_rate` double(8,2) DEFAULT NULL,
  `additional_info` text DEFAULT NULL,
  `booking_value` double(20,2) DEFAULT NULL,
  `fbc` varchar(191) DEFAULT NULL,
  `shipdawn` date DEFAULT NULL,
  `inrat_booking_value` double(20,2) DEFAULT NULL,
  `tax_type` varchar(191) DEFAULT 'IGST',
  `bl_no` varchar(191) DEFAULT NULL,
  `bl_date` date DEFAULT NULL,
  `egm_no` varchar(191) DEFAULT NULL,
  `egm_date` date DEFAULT NULL,
  `agent_name` varchar(191) DEFAULT NULL,
  `eta` date DEFAULT NULL,
  `etd` date DEFAULT NULL,
  `irn` varchar(191) DEFAULT '',
  `ack_no` varchar(191) DEFAULT '',
  `ack_date` varchar(191) DEFAULT NULL,
  `einvoice_qr` varchar(191) DEFAULT '',
  `lr_rr_no` varchar(191) DEFAULT '',
  `billty_no` varchar(191) DEFAULT NULL,
  `billty_date` date DEFAULT NULL,
  `agent` varchar(191) DEFAULT NULL,
  `port_of_loading` varchar(191) DEFAULT NULL,
  `port_of_discharge` varchar(191) DEFAULT NULL,
  `bill_to` text DEFAULT NULL,
  `ship_to` text DEFAULT NULL,
  `send_mail` int(11) NOT NULL DEFAULT 0,
  `container_size` varchar(191) DEFAULT NULL,
  `delivery_term` varchar(191) DEFAULT NULL,
  `deposit` double(8,2) DEFAULT NULL,
  `deposit_date` date DEFAULT NULL,
  `vat` double(8,2) NOT NULL DEFAULT 0.00,
  `refund_statement` varchar(191) DEFAULT NULL,
  `refund` double(8,2) NOT NULL DEFAULT 0.00,
  `oceanic_freight` double(8,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `invoice_buyer_id_foreign` (`buyer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `legs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `leg_design` varchar(191) DEFAULT NULL,
  `qty` varchar(191) DEFAULT NULL,
  `price` double(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `size` double(8,2) DEFAULT NULL,
  `height` double(8,2) DEFAULT NULL,
  `width` double(8,2) DEFAULT NULL,
  `depth` double(8,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `login_securities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_securities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `google2fa_enable` tinyint(1) NOT NULL DEFAULT 0,
  `google2fa_secret` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` int(10) unsigned NOT NULL,
  `model_type` varchar(191) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_roles` (
  `role_id` int(10) unsigned NOT NULL,
  `model_type` varchar(191) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `notification` text DEFAULT NULL,
  `is_read` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `packaging`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packaging` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `box1_height` double(8,2) DEFAULT NULL,
  `box1_width` double(8,2) DEFAULT NULL,
  `box1_depth` double(8,2) DEFAULT NULL,
  `box2_height` double(8,2) DEFAULT NULL,
  `box2_width` double(8,2) DEFAULT NULL,
  `box2_depth` double(8,2) DEFAULT NULL,
  `box1_sqinch` double(8,2) DEFAULT NULL,
  `box2_sqinch` double(8,2) DEFAULT NULL,
  `no_of_boxes` double(8,2) DEFAULT NULL,
  `box1_ply` double(8,2) DEFAULT NULL,
  `box2_ply` double(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `box1_type` varchar(191) DEFAULT NULL,
  `box2_type` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `packaging_pricing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packaging_pricing` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) DEFAULT NULL,
  `3ply` double(8,2) DEFAULT NULL,
  `5ply` double(8,2) DEFAULT NULL,
  `7ply` double(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `packinglist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packinglist` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `buyer_order_no` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tquantity` int(11) DEFAULT NULL,
  `totalwt` int(11) DEFAULT NULL,
  `grosswt` int(11) DEFAULT NULL,
  `totalbox` int(11) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `packinglistproducts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packinglistproducts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `packinglist_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `weight` double(8,2) DEFAULT NULL,
  `subtotalnetwt` double(8,2) DEFAULT NULL,
  `grosswt` double(8,2) DEFAULT NULL,
  `subtotalgrosswt` double(8,2) DEFAULT NULL,
  `box` double(8,2) DEFAULT NULL,
  `endBox` double(8,2) DEFAULT NULL,
  `subTotalBox` double(8,2) DEFAULT NULL,
  `qtybox` double(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `packingsheet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packingsheet` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) unsigned NOT NULL,
  `totalbox` int(11) NOT NULL DEFAULT 0,
  `netwt` double(12,2) NOT NULL DEFAULT 0.00,
  `grosswt` double(12,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `packingsheet_invoice_id_foreign` (`invoice_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pb_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pb_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchaseOrder_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `purchasebill_id` int(10) unsigned NOT NULL,
  `EAN` varchar(191) DEFAULT NULL,
  `orderqty` int(11) NOT NULL,
  `receiveqty` int(11) NOT NULL,
  `remainingqty` int(11) NOT NULL,
  `rate` double(12,2) DEFAULT NULL,
  `amount` double(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `location` varchar(191) DEFAULT NULL,
  `prod_remaining` int(11) DEFAULT NULL,
  `remarks` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pb_table_purchaseorder_id_foreign` (`purchaseOrder_id`),
  KEY `pb_table_product_id_foreign` (`product_id`),
  KEY `pb_table_purchasebill_id_foreign` (`purchasebill_id`),
  KEY `pb_table_ean_foreign` (`EAN`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `performance_card_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `performance_card_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `performance_card_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `qty_received` int(11) DEFAULT NULL,
  `qty_rejected` int(11) DEFAULT NULL,
  `net_qty` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `performance_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `performance_cards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contractor_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `job` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `guard_name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `poTable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poTable` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `poid` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `EAN` varchar(191) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit` varchar(191) NOT NULL,
  `rate` double(12,2) DEFAULT NULL,
  `amount` double(12,2) DEFAULT NULL,
  `gstslab` double(12,2) DEFAULT NULL,
  `gstamount` double(12,2) DEFAULT NULL,
  `remqty` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `priority` int(11) DEFAULT NULL,
  `delivery_point` varchar(191) DEFAULT NULL,
  `legs` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `potable_poid_foreign` (`poid`),
  KEY `potable_product_id_foreign` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pocTable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pocTable` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `poid` int(10) unsigned NOT NULL,
  `consumable_id` int(10) unsigned NOT NULL,
  `quantity` float DEFAULT NULL,
  `unit` varchar(191) NOT NULL,
  `rate` double(12,2) DEFAULT NULL,
  `amount` double(12,2) DEFAULT NULL,
  `gstslab` double(12,2) DEFAULT NULL,
  `gstamount` double(12,2) DEFAULT NULL,
  `remqty` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `popTable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `popTable` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `poid` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `sq_inches` varchar(191) DEFAULT '',
  `unit` varchar(191) DEFAULT '',
  `rate` double(12,2) DEFAULT NULL,
  `amount` double(12,2) DEFAULT NULL,
  `gstslab` double(12,2) DEFAULT NULL,
  `gstamount` double(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `box1_height` double(8,2) DEFAULT NULL,
  `box1_width` double(8,2) DEFAULT NULL,
  `box1_depth` double(8,2) DEFAULT NULL,
  `box2_height` double(8,2) DEFAULT NULL,
  `box2_width` double(8,2) DEFAULT NULL,
  `box2_depth` double(8,2) DEFAULT NULL,
  `box1_sqinch` double(8,2) DEFAULT NULL,
  `box2_sqinch` double(8,2) DEFAULT NULL,
  `box1_ply` double(8,2) DEFAULT NULL,
  `box2_ply` double(8,2) DEFAULT NULL,
  `box1_rate` double(8,2) DEFAULT NULL,
  `box2_rate` double(8,2) DEFAULT NULL,
  `box1_amount` double(8,2) DEFAULT NULL,
  `box2_amount` double(8,2) DEFAULT NULL,
  `line_drawing` varchar(191) DEFAULT NULL,
  `box2_qty` int(11) DEFAULT NULL,
  `remqty_box1` int(11) NOT NULL DEFAULT 0,
  `remqty_box2` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `porTable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `porTable` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `poid` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `EAN` varchar(191) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit` varchar(191) NOT NULL,
  `rate` double(12,2) DEFAULT NULL,
  `amount` double(12,2) DEFAULT NULL,
  `gstslab` double(12,2) DEFAULT NULL,
  `gstamount` double(12,2) DEFAULT NULL,
  `remqty` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `posTable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `posTable` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `poid` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `sample_id` int(10) unsigned NOT NULL,
  `EAN` varchar(191) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit` varchar(191) NOT NULL,
  `rate` double(12,2) DEFAULT NULL,
  `amount` double(12,2) DEFAULT NULL,
  `gstslab` double(12,2) DEFAULT NULL,
  `gstamount` double(12,2) DEFAULT NULL,
  `remqty` int(11) DEFAULT NULL,
  `priority` int(11) DEFAULT NULL,
  `legs` int(11) DEFAULT NULL,
  `delivery_point` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pricingTable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pricingTable` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `buyer_id` int(10) unsigned NOT NULL,
  `startDate` date DEFAULT NULL,
  `endDate` date DEFAULT NULL,
  `remarks` longtext DEFAULT NULL,
  `buyingCost` double(12,2) DEFAULT NULL,
  `fabricCost` varchar(191) DEFAULT NULL,
  `tapestryConsumed` double(12,2) DEFAULT NULL,
  `tapestryCost` double(12,2) DEFAULT NULL,
  `fillerCost` double(12,2) DEFAULT NULL,
  `labourCost` double(12,2) DEFAULT NULL,
  `hardwareCost1` double(12,2) DEFAULT NULL,
  `pUnitCost` double(12,2) DEFAULT NULL,
  `polishCost` double(12,2) DEFAULT NULL,
  `wSPackageCost` double(12,2) DEFAULT NULL,
  `dSPackageCost` double(12,2) DEFAULT NULL,
  `shippingCost` double(12,2) DEFAULT NULL,
  `costPrice` double(12,2) DEFAULT NULL,
  `adminCostPercent` double(12,2) DEFAULT NULL,
  `adminCost` double(12,2) DEFAULT NULL,
  `profitPercent` double(12,2) DEFAULT NULL,
  `finalCost` double(12,2) DEFAULT NULL,
  `currency` varchar(191) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `converRate` double(12,2) DEFAULT NULL,
  `fobINCost` double(12,2) DEFAULT NULL,
  `boxWt` double(12,2) DEFAULT NULL,
  `volWt` double(12,2) DEFAULT NULL,
  `shippingCost2` double(12,2) DEFAULT NULL,
  `StorageCost` double(12,2) DEFAULT NULL,
  `adminCost2` double(12,2) DEFAULT NULL,
  `qualityAssurance` double(12,2) DEFAULT NULL,
  `landedCost` double(12,2) DEFAULT NULL,
  `adminProfit` double(12,2) DEFAULT NULL,
  `adminPrice` double(12,2) DEFAULT NULL,
  `finalPricePer` double(12,2) DEFAULT NULL,
  `finalPrice` double(12,2) DEFAULT NULL,
  `courierType` varchar(191) DEFAULT NULL,
  `courierCost` double(12,2) DEFAULT NULL,
  `deliveryCost` double(12,2) DEFAULT NULL,
  `newDelCost` double(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `productType` tinyint(4) NOT NULL,
  `hardwareCost2` double(12,2) DEFAULT NULL,
  `hardwareCost3` double(12,2) DEFAULT NULL,
  `hardwareCost4` double(12,2) DEFAULT NULL,
  `hardwareCost5` double(12,2) DEFAULT NULL,
  `deliveredCostStatus` tinyint(4) NOT NULL,
  `buyer_id2` int(10) unsigned DEFAULT NULL,
  `tapestryUnitCost` double(12,2) NOT NULL DEFAULT 0.00,
  `productheight` double(12,2) DEFAULT NULL,
  `productwidth` double(12,2) DEFAULT NULL,
  `productdepth` double(12,2) DEFAULT NULL,
  `boxheight` double(12,2) DEFAULT NULL,
  `boxwidth` double(12,2) DEFAULT NULL,
  `boxdepth` double(12,2) DEFAULT NULL,
  `wholesalevolume` double(12,4) DEFAULT NULL,
  `dropshipvolume` double(12,4) DEFAULT NULL,
  `adjustment` double(12,2) DEFAULT NULL,
  `destination` varchar(191) DEFAULT NULL,
  `hardware1` int(11) DEFAULT NULL,
  `hardware2` int(11) DEFAULT NULL,
  `hardware3` int(11) DEFAULT NULL,
  `hardware4` int(11) DEFAULT NULL,
  `hardware5` int(11) DEFAULT NULL,
  `hardware1_quantity` int(11) DEFAULT NULL,
  `hardware2_quantity` int(11) DEFAULT NULL,
  `hardware3_quantity` int(11) DEFAULT NULL,
  `hardware4_quantity` int(11) DEFAULT NULL,
  `hardware5_quantity` int(11) DEFAULT NULL,
  `fuelCharge` double(8,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `pricingtable_product_id_foreign` (`product_id`),
  KEY `pricingtable_buyer_id_foreign` (`buyer_id`),
  KEY `pricingtable_buyer_id2_foreign` (`buyer_id2`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_grouping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_grouping` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `child_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `parent_finish` varchar(191) DEFAULT NULL,
  `child_finish` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_locations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `location` varchar(191) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_subcategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_subcategory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_swapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_swapping` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `swapped_with` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `invoice_no` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `subcategory_id` int(10) unsigned NOT NULL,
  `imageURL` varchar(191) DEFAULT NULL,
  `code` varchar(191) NOT NULL,
  `EAN` varchar(191) NOT NULL,
  `HSN` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `finishing` varchar(191) DEFAULT NULL,
  `gstslab` int(11) DEFAULT NULL,
  `width` double(8,2) DEFAULT NULL,
  `height` double(8,2) DEFAULT NULL,
  `depth` double(8,2) DEFAULT NULL,
  `volume` double(12,4) DEFAULT NULL,
  `hardware1` int(11) DEFAULT NULL,
  `addons` varchar(191) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `boxwidth` double(8,2) DEFAULT 0.00,
  `boxheight` double(8,2) DEFAULT 0.00,
  `boxdepth` double(8,2) DEFAULT 0.00,
  `wholesalevolume` double(12,4) DEFAULT 0.0000,
  `dropshipvolume` double(12,4) DEFAULT 0.0000,
  `hardware2` int(11) DEFAULT NULL,
  `hardware3` int(11) DEFAULT NULL,
  `hardware4` int(11) DEFAULT NULL,
  `hardware5` int(11) DEFAULT NULL,
  `upholstry` varchar(255) DEFAULT NULL,
  `corner` varchar(255) DEFAULT NULL,
  `lhardware` varchar(255) DEFAULT NULL,
  `hardware1_quantity` int(11) DEFAULT NULL,
  `hardware2_quantity` int(11) DEFAULT NULL,
  `hardware3_quantity` int(11) DEFAULT NULL,
  `hardware4_quantity` int(11) DEFAULT NULL,
  `hardware5_quantity` int(11) DEFAULT NULL,
  `location` varchar(191) DEFAULT NULL,
  `finishing_price` double(8,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_table_ean_unique` (`EAN`),
  KEY `product_table_category_id_foreign` (`category_id`),
  KEY `product_table_subcategory_id_foreign` (`subcategory_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `product_table_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_table_backup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `subcategory_id` int(10) unsigned NOT NULL,
  `imageURL` varchar(191) DEFAULT NULL,
  `code` varchar(191) NOT NULL,
  `EAN` varchar(191) NOT NULL,
  `HSN` varchar(191) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `finishing` varchar(191) DEFAULT NULL,
  `gstslab` int(11) NOT NULL,
  `width` double(8,2) DEFAULT NULL,
  `height` double(8,2) DEFAULT NULL,
  `depth` double(8,2) DEFAULT NULL,
  `volume` double(12,4) DEFAULT NULL,
  `hardware1` int(11) DEFAULT NULL,
  `addons` varchar(191) DEFAULT NULL,
  `remarks` varchar(191) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `boxwidth` double(8,2) NOT NULL DEFAULT 0.00,
  `boxheight` double(8,2) NOT NULL DEFAULT 0.00,
  `boxdepth` double(8,2) NOT NULL DEFAULT 0.00,
  `wholesalevolume` double(12,4) NOT NULL DEFAULT 0.0000,
  `dropshipvolume` double(12,4) NOT NULL DEFAULT 0.0000,
  `hardware2` int(11) DEFAULT NULL,
  `hardware3` int(11) DEFAULT NULL,
  `hardware4` int(11) DEFAULT NULL,
  `hardware5` int(11) DEFAULT NULL,
  `upholstry` varchar(255) DEFAULT NULL,
  `corner` varchar(255) DEFAULT NULL,
  `lhardware` varchar(255) DEFAULT NULL,
  `hardware1_quantity` int(11) DEFAULT NULL,
  `hardware2_quantity` int(11) DEFAULT NULL,
  `hardware3_quantity` int(11) DEFAULT NULL,
  `hardware4_quantity` int(11) DEFAULT NULL,
  `hardware5_quantity` int(11) DEFAULT NULL,
  `location` varchar(191) DEFAULT NULL,
  `finishing_price` double(8,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_table_backup_ean_unique` (`EAN`),
  KEY `product_table_backup_category_id_foreign` (`category_id`),
  KEY `product_table_backup_subcategory_id_foreign` (`subcategory_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pstable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pstable` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `packingSheet_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `invoice_id` int(10) unsigned NOT NULL,
  `EAN` varchar(191) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `netwt` double(12,2) NOT NULL DEFAULT 0.00,
  `grosswt` double(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pstable_packingsheet_id_foreign` (`packingSheet_id`),
  KEY `pstable_product_id_foreign` (`product_id`),
  KEY `pstable_invoice_id_foreign` (`invoice_id`),
  KEY `pstable_ean_foreign` (`EAN`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_bill`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_bill` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchaseOrder_id` int(10) unsigned NOT NULL,
  `supp_inv_no` varchar(191) NOT NULL,
  `supp_inv_date` date NOT NULL,
  `ewaybill` varchar(191) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` double(12,2) NOT NULL,
  `gst` double(12,2) DEFAULT NULL,
  `freight` double(12,2) DEFAULT NULL,
  `total` double(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `swap_id` int(11) DEFAULT NULL,
  `is_checked` int(10) unsigned NOT NULL DEFAULT 0,
  `is_downloaded` int(10) unsigned NOT NULL DEFAULT 0,
  `due_date` date DEFAULT NULL,
  `payment_status` int(11) NOT NULL DEFAULT 1,
  `invoice_status` smallint(6) NOT NULL DEFAULT 1,
  `supplier_invoice_id` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `purchase_bill_purchaseorder_id_foreign` (`purchaseOrder_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pono` varchar(191) NOT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `podate` date DEFAULT NULL,
  `del_date` date DEFAULT NULL,
  `ref_supplier` varchar(191) DEFAULT NULL,
  `buyer_orderno` varchar(191) DEFAULT NULL,
  `payterms` varchar(191) DEFAULT NULL,
  `remarks` varchar(191) DEFAULT NULL,
  `tgst` double(12,2) DEFAULT NULL,
  `tquantity` int(11) DEFAULT NULL,
  `subTotal` double(12,2) DEFAULT NULL,
  `tamount` double(12,2) DEFAULT NULL,
  `remqty` int(11) DEFAULT NULL,
  `status` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_via` varchar(191) DEFAULT 'APP',
  `supplier_status` int(11) NOT NULL DEFAULT 0,
  `supplier_remarks` text DEFAULT NULL,
  `address_option` int(11) DEFAULT NULL,
  `po_revise_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_supplier_id_foreign` (`supplier_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_order_consumables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_order_consumables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pono` varchar(191) NOT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `podate` date DEFAULT NULL,
  `del_date` date DEFAULT NULL,
  `ref_supplier` varchar(191) DEFAULT NULL,
  `buyer_orderno` varchar(191) DEFAULT NULL,
  `payterms` varchar(191) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `tgst` double(12,2) DEFAULT NULL,
  `tquantity` float DEFAULT NULL,
  `subTotal` double(12,2) DEFAULT NULL,
  `tamount` double(12,2) DEFAULT NULL,
  `remqty` int(11) DEFAULT NULL,
  `status` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `month` varchar(191) DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `address_option` int(11) DEFAULT NULL,
  `po_revise_date` date DEFAULT NULL,
  `supplier_status` int(11) NOT NULL DEFAULT 0,
  `supplier_remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `purchase_order_recommended`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_order_recommended` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pono` varchar(191) NOT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `podate` date DEFAULT NULL,
  `del_date` date DEFAULT NULL,
  `ref_supplier` varchar(191) DEFAULT NULL,
  `buyer_orderno` varchar(191) DEFAULT NULL,
  `payterms` varchar(191) DEFAULT NULL,
  `remarks` varchar(191) DEFAULT NULL,
  `tgst` double(12,2) DEFAULT NULL,
  `tquantity` int(11) DEFAULT NULL,
  `subTotal` double(12,2) DEFAULT NULL,
  `tamount` double(12,2) DEFAULT NULL,
  `remqty` int(11) DEFAULT NULL,
  `status` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_via` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quality`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quality` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `supplier_inv_no` varchar(191) DEFAULT NULL,
  `status` int(10) unsigned NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `imageURL` varchar(191) DEFAULT NULL,
  `channel_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `questionnaire`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `questionnaire` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(191) DEFAULT NULL,
  `designation` varchar(191) DEFAULT NULL,
  `work_location` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `questionnaire_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `questionnaire_responses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `questionnaire_id` varchar(191) DEFAULT NULL,
  `question_no` varchar(191) DEFAULT NULL,
  `most_likely` varchar(191) DEFAULT NULL,
  `least_likely` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recommended_pos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recommended_pos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reject_repair`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reject_repair` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned DEFAULT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `supplier_inv_no` varchar(191) DEFAULT NULL,
  `status` int(10) unsigned NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `date` date DEFAULT NULL,
  `remarks` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `receiveqty` int(11) DEFAULT NULL,
  `supplier_invoice_id` int(11) DEFAULT NULL,
  `is_debit_note` int(11) NOT NULL DEFAULT 0,
  `is_challan_raised` int(11) NOT NULL DEFAULT 0,
  `outward_challan_no` varchar(191) DEFAULT NULL,
  `send_to_supplier` int(11) NOT NULL DEFAULT 0,
  `purchase_bill_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `reject_repair_product_id_foreign` (`product_id`),
  KEY `reject_repair_supplier_id_foreign` (`supplier_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reject_repair_ptable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reject_repair_ptable` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reject_repair_id` int(11) DEFAULT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `receiveqty` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `is_challan_raised` int(11) NOT NULL DEFAULT 0,
  `is_debit_note` int(11) NOT NULL DEFAULT 0,
  `debit_note_date` date DEFAULT NULL,
  `pending_for_dn` int(11) NOT NULL DEFAULT 0,
  `purchase_bill_id` int(11) NOT NULL DEFAULT 0,
  `send_to_supplier` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `potable_product_id_foreign` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `guard_name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sample_pb_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sample_pb_table` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchaseOrder_id` int(11) NOT NULL,
  `sample_id` int(11) NOT NULL,
  `sample_purchasebill_id` int(11) NOT NULL,
  `EAN` varchar(191) NOT NULL,
  `orderqty` int(11) NOT NULL,
  `receiveqty` int(11) NOT NULL,
  `rate` double(8,2) NOT NULL,
  `amount` double(8,2) NOT NULL,
  `location` varchar(191) DEFAULT '',
  `prod_remaining` int(11) DEFAULT NULL,
  `remarks` varchar(191) DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remainingqty` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sample_purchase_bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sample_purchase_bills` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchaseOrder_id` int(11) NOT NULL DEFAULT 0,
  `supp_inv_date` date DEFAULT NULL,
  `ewaybill` varchar(191) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `subtotal` double(8,2) DEFAULT NULL,
  `gst` double(8,2) DEFAULT NULL,
  `freight` double(8,2) DEFAULT NULL,
  `total` double(8,2) DEFAULT NULL,
  `swap_id` int(11) DEFAULT NULL,
  `is_checked` int(11) DEFAULT NULL,
  `is_downloaded` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `payment_status` int(11) DEFAULT NULL,
  `invoice_status` int(11) DEFAULT NULL,
  `supplier_invoice_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `supp_inv_no` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sample_purchase_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sample_purchase_order` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pono` varchar(191) NOT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `podate` date DEFAULT NULL,
  `del_date` date DEFAULT NULL,
  `ref_supplier` varchar(191) DEFAULT NULL,
  `buyer_orderno` varchar(191) DEFAULT NULL,
  `payterms` varchar(191) DEFAULT NULL,
  `remarks` varchar(191) DEFAULT NULL,
  `tgst` double(12,2) DEFAULT NULL,
  `tquantity` int(11) DEFAULT NULL,
  `subTotal` double(12,2) DEFAULT NULL,
  `tamount` double(12,2) DEFAULT NULL,
  `remqty` int(11) DEFAULT NULL,
  `status` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `samples`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `samples` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `subcategory_id` int(11) DEFAULT NULL,
  `imageURL` varchar(191) DEFAULT NULL,
  `code` varchar(191) NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `finishing` varchar(191) DEFAULT NULL,
  `width` double(8,2) DEFAULT NULL,
  `height` double(8,2) DEFAULT NULL,
  `depth` double(8,2) DEFAULT NULL,
  `boxwidth` double(8,2) DEFAULT NULL,
  `boxheight` double(8,2) DEFAULT NULL,
  `boxdepth` double(8,2) DEFAULT NULL,
  `wholesalevolume` double(12,4) DEFAULT NULL,
  `dropshipvolume` double(12,4) DEFAULT NULL,
  `volume` double(12,4) DEFAULT NULL,
  `hardware1` int(11) DEFAULT NULL,
  `hardware2` int(11) DEFAULT NULL,
  `hardware3` int(11) DEFAULT NULL,
  `hardware4` int(11) DEFAULT NULL,
  `hardware5` int(11) DEFAULT NULL,
  `hardware1_quantity` int(11) DEFAULT NULL,
  `hardware2_quantity` int(11) DEFAULT NULL,
  `hardware3_quantity` int(11) DEFAULT NULL,
  `hardware4_quantity` int(11) DEFAULT NULL,
  `hardware5_quantity` int(11) DEFAULT NULL,
  `addons` varchar(191) DEFAULT NULL,
  `remarks` varchar(191) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `upholstry` varchar(191) DEFAULT NULL,
  `corner` varchar(191) DEFAULT NULL,
  `lhardware` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `prod_code` varchar(191) DEFAULT NULL,
  `ean` varchar(191) DEFAULT NULL,
  `hsn` varchar(191) DEFAULT NULL,
  `gstslab` int(11) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `location` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `c_name` varchar(191) DEFAULT NULL,
  `pan` varchar(191) DEFAULT NULL,
  `gstin` varchar(191) DEFAULT NULL,
  `address1` varchar(191) DEFAULT NULL,
  `address2` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `state` varchar(191) DEFAULT NULL,
  `country` varchar(191) DEFAULT NULL,
  `postcode` varchar(191) DEFAULT NULL,
  `iec` varchar(191) DEFAULT NULL,
  `rbi` varchar(191) DEFAULT NULL,
  `gsp` varchar(191) DEFAULT NULL,
  `lut` varchar(191) DEFAULT NULL,
  `website` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone1` varchar(191) DEFAULT NULL,
  `phone2` varchar(191) DEFAULT NULL,
  `logoUrl` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `volWt` double(12,2) NOT NULL DEFAULT 4000.00,
  `shippingCost2` double(12,2) NOT NULL DEFAULT 30.00,
  `StorageCost` double(12,2) NOT NULL DEFAULT 11.00,
  `corner_rate` double(8,2) DEFAULT NULL,
  `packaging_per_sqinch_rate` double(8,2) DEFAULT NULL,
  `cpo_no` varchar(191) DEFAULT NULL,
  `opo_no` varchar(191) DEFAULT NULL,
  `carton_price` double(8,2) DEFAULT NULL,
  `carton_price_3ply` double(8,2) DEFAULT NULL,
  `carton_price_7ply` double(8,2) DEFAULT NULL,
  `ctnpo_no` varchar(191) DEFAULT NULL,
  `spo_no` varchar(191) DEFAULT NULL,
  `factory_address` text DEFAULT NULL,
  `show_gsp` int(11) DEFAULT 1,
  `wspackaging` double(8,2) DEFAULT NULL,
  `dspackaging` double(8,2) DEFAULT NULL,
  `ishippingcost` double(8,2) DEFAULT NULL,
  `in2047` int(11) DEFAULT NULL,
  `in2108` int(11) DEFAULT NULL,
  `usa_remaining_stock` int(11) DEFAULT NULL,
  `us_product_1` varchar(255) DEFAULT NULL,
  `us_product_2` varchar(255) DEFAULT NULL,
  `eu_product_1` varchar(255) DEFAULT NULL,
  `eu_product_2` varchar(255) DEFAULT NULL,
  `eu_product_1_stock` int(11) DEFAULT NULL,
  `eu_product_2_stock` int(11) DEFAULT NULL,
  `eu_remaining_stock` int(11) DEFAULT NULL,
  `volumetric_weight_lbs` double(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings_option`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings_option` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(191) NOT NULL,
  `setting_value` double(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settingseu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settingseu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `c_name` varchar(191) DEFAULT NULL,
  `pan` varchar(191) DEFAULT NULL,
  `gstin` varchar(191) DEFAULT NULL,
  `address1` varchar(191) DEFAULT NULL,
  `address2` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `state` varchar(191) DEFAULT NULL,
  `country` varchar(191) DEFAULT NULL,
  `postcode` varchar(191) DEFAULT NULL,
  `iec` varchar(191) DEFAULT NULL,
  `rbi` varchar(191) DEFAULT NULL,
  `gsp` varchar(191) DEFAULT NULL,
  `lut` varchar(191) DEFAULT NULL,
  `website` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone1` varchar(191) DEFAULT NULL,
  `phone2` varchar(191) DEFAULT NULL,
  `logoUrl` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `volWt` double(12,2) NOT NULL DEFAULT 4000.00,
  `shippingCost2` double(12,2) NOT NULL DEFAULT 30.00,
  `StorageCost` double(12,2) NOT NULL DEFAULT 11.00,
  `corner_rate` double(8,2) DEFAULT NULL,
  `packaging_per_sqinch_rate` double(8,2) DEFAULT NULL,
  `cpo_no` varchar(191) DEFAULT NULL,
  `opo_no` varchar(191) DEFAULT NULL,
  `carton_price` double(8,2) DEFAULT NULL,
  `carton_price_3ply` double(8,2) DEFAULT NULL,
  `carton_price_7ply` double(8,2) DEFAULT NULL,
  `ctnpo_no` varchar(191) DEFAULT NULL,
  `spo_no` varchar(191) DEFAULT NULL,
  `factory_address` text DEFAULT NULL,
  `show_gsp` int(11) DEFAULT 1,
  `wspackaging` double(8,2) DEFAULT NULL,
  `dspackaging` double(8,2) DEFAULT NULL,
  `ishippingcost` double(8,2) DEFAULT NULL,
  `bank_details` text DEFAULT NULL,
  `vat` varchar(255) DEFAULT '',
  `bank_details_euro` text DEFAULT NULL,
  `bank_details_dollar` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settingsuk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settingsuk` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `c_name` varchar(191) DEFAULT NULL,
  `pan` varchar(191) DEFAULT NULL,
  `gstin` varchar(191) DEFAULT NULL,
  `address1` varchar(191) DEFAULT NULL,
  `address2` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `state` varchar(191) DEFAULT NULL,
  `country` varchar(191) DEFAULT NULL,
  `postcode` varchar(191) DEFAULT NULL,
  `iec` varchar(191) DEFAULT NULL,
  `rbi` varchar(191) DEFAULT NULL,
  `gsp` varchar(191) DEFAULT NULL,
  `lut` varchar(191) DEFAULT NULL,
  `website` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone1` varchar(191) DEFAULT NULL,
  `phone2` varchar(191) DEFAULT NULL,
  `logoUrl` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `volWt` double(12,2) NOT NULL DEFAULT 4000.00,
  `shippingCost2` double(12,2) NOT NULL DEFAULT 30.00,
  `StorageCost` double(12,2) NOT NULL DEFAULT 11.00,
  `corner_rate` double(8,2) DEFAULT NULL,
  `packaging_per_sqinch_rate` double(8,2) DEFAULT NULL,
  `cpo_no` varchar(191) DEFAULT NULL,
  `opo_no` varchar(191) DEFAULT NULL,
  `carton_price` double(8,2) DEFAULT NULL,
  `carton_price_3ply` double(8,2) DEFAULT NULL,
  `carton_price_7ply` double(8,2) DEFAULT NULL,
  `ctnpo_no` varchar(191) DEFAULT NULL,
  `spo_no` varchar(191) DEFAULT NULL,
  `factory_address` text DEFAULT NULL,
  `show_gsp` int(11) DEFAULT 1,
  `wspackaging` double(8,2) DEFAULT NULL,
  `dspackaging` double(8,2) DEFAULT NULL,
  `ishippingcost` double(8,2) DEFAULT NULL,
  `bank_details` text DEFAULT NULL,
  `vat` varchar(255) DEFAULT '',
  `bank_details_euro` text DEFAULT NULL,
  `bank_details_dollar` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settingsus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settingsus` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `c_name` varchar(191) DEFAULT NULL,
  `pan` varchar(191) DEFAULT NULL,
  `gstin` varchar(191) DEFAULT NULL,
  `address1` varchar(191) DEFAULT NULL,
  `address2` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `state` varchar(191) DEFAULT NULL,
  `country` varchar(191) DEFAULT NULL,
  `postcode` varchar(191) DEFAULT NULL,
  `iec` varchar(191) DEFAULT NULL,
  `rbi` varchar(191) DEFAULT NULL,
  `gsp` varchar(191) DEFAULT NULL,
  `lut` varchar(191) DEFAULT NULL,
  `website` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone1` varchar(191) DEFAULT NULL,
  `phone2` varchar(191) DEFAULT NULL,
  `logoUrl` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `volWt` double(12,2) NOT NULL DEFAULT 4000.00,
  `shippingCost2` double(12,2) NOT NULL DEFAULT 30.00,
  `StorageCost` double(12,2) NOT NULL DEFAULT 11.00,
  `corner_rate` double(8,2) DEFAULT NULL,
  `packaging_per_sqinch_rate` double(8,2) DEFAULT NULL,
  `cpo_no` varchar(191) DEFAULT NULL,
  `opo_no` varchar(191) DEFAULT NULL,
  `carton_price` double(8,2) DEFAULT NULL,
  `carton_price_3ply` double(8,2) DEFAULT NULL,
  `carton_price_7ply` double(8,2) DEFAULT NULL,
  `ctnpo_no` varchar(191) DEFAULT NULL,
  `spo_no` varchar(191) DEFAULT NULL,
  `factory_address` text DEFAULT NULL,
  `show_gsp` int(11) DEFAULT 1,
  `wspackaging` double(8,2) DEFAULT NULL,
  `dspackaging` double(8,2) DEFAULT NULL,
  `ishippingcost` double(8,2) DEFAULT NULL,
  `bank_details` text DEFAULT NULL,
  `vat` varchar(255) DEFAULT '',
  `bank_details_euro` text DEFAULT NULL,
  `bank_details_dollar` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shipping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shipping` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shipping_line_id` int(11) NOT NULL,
  `container` varchar(191) DEFAULT NULL,
  `reference` varchar(191) DEFAULT NULL,
  `thc` double(8,2) DEFAULT NULL,
  `bl` double(8,2) DEFAULT NULL,
  `incidental_charges` double(8,2) DEFAULT NULL,
  `transit_time` double(8,2) DEFAULT NULL,
  `type` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `ocean_freight` double(8,2) DEFAULT NULL,
  `thc_uk` double(8,2) DEFAULT NULL,
  `handling_charges` double(8,2) DEFAULT NULL,
  `other` double(8,2) DEFAULT NULL,
  `total_india` double(8,2) DEFAULT NULL,
  `total_uk` double(8,2) DEFAULT NULL,
  `conversion_rate` double(8,2) DEFAULT NULL,
  `month` varchar(191) DEFAULT NULL,
  `magnus` int(11) DEFAULT NULL,
  `magnus_date` datetime DEFAULT NULL,
  `magnus_tracking_number` varchar(191) DEFAULT NULL,
  `eta_at_port` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shipping_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shipping_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `agent_name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `agent_uk` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sku_fulfillment_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sku_fulfillment_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(50) NOT NULL,
  `order_id` varchar(50) NOT NULL,
  `qty` int(11) NOT NULL,
  `order_type` varchar(50) NOT NULL,
  `wp_customers_info_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `type` varchar(50) NOT NULL COMMENT 'Add / Less',
  `site_access` varchar(191) NOT NULL DEFAULT 'UK',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sku_fulfillment_qtys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sku_fulfillment_qtys` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `wp_customers_info_id` int(11) DEFAULT NULL,
  `sku` varchar(50) NOT NULL,
  `qty` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `site_access` varchar(50) NOT NULL DEFAULT 'UK',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `small_hardware`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `small_hardware` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `dust_cover_size` varchar(191) DEFAULT NULL,
  `dust_cover_price` double DEFAULT NULL,
  `pouch` int(11) DEFAULT NULL,
  `pouch_price` double DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `supplier_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `small_hardware_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `small_hardware_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `small_hardware_id` int(11) DEFAULT NULL,
  `size` varchar(191) DEFAULT NULL,
  `quantity` float DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `price` double(8,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `smallhardware_suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smallhardware_suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `c_name` varchar(191) DEFAULT '',
  `name` varchar(191) DEFAULT NULL,
  `address1` varchar(191) DEFAULT NULL,
  `address2` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT '',
  `state` varchar(191) DEFAULT '',
  `country` varchar(191) DEFAULT '',
  `postcode` varchar(191) DEFAULT NULL,
  `gst` tinyint(1) DEFAULT 0,
  `gstin` varchar(191) DEFAULT NULL,
  `state_code` varchar(191) DEFAULT NULL,
  `pan` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone1` bigint(20) DEFAULT NULL,
  `phone2` bigint(20) DEFAULT NULL,
  `tds` int(11) DEFAULT NULL,
  `tdspercent` double DEFAULT NULL,
  `gstpercent` double DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `smallhardwares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `smallhardwares` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) DEFAULT NULL,
  `rate` double(8,2) DEFAULT NULL,
  `location` varchar(191) DEFAULT NULL,
  `supplier` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `buyer` int(11) DEFAULT NULL,
  `ratenuk` double(8,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `states` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `statecode` varchar(191) NOT NULL,
  `statename` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ref_no` varchar(191) DEFAULT NULL,
  `voucher_no` text DEFAULT NULL,
  `type` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `opening_balance` int(11) DEFAULT NULL,
  `remaining_stock` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `supplier_inv_no` text DEFAULT NULL,
  `entity_id` int(11) NOT NULL DEFAULT 0,
  `supplier_name` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stockout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stockout` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) unsigned NOT NULL,
  `buyer_ref_no` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `swap_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stockout_invoice_id_foreign` (`invoice_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stockoutable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stockoutable` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `stock_id` int(10) unsigned NOT NULL,
  `EAN` varchar(191) NOT NULL,
  `orderqty` int(11) NOT NULL,
  `receiveqty` int(11) NOT NULL,
  `remainingqty` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `location` varchar(191) DEFAULT NULL,
  `supp_inv_no` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stockoutable_product_id_foreign` (`product_id`),
  KEY `stockoutable_stock_id_foreign` (`stock_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suggested_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suggested_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(191) DEFAULT NULL,
  `last_month_sale` double(8,2) DEFAULT NULL,
  `current_price` double(8,2) DEFAULT NULL,
  `current_stock` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supplier_invoice_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_invoice_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_invoice_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `returned_qty` int(11) NOT NULL DEFAULT 0,
  `total` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `purchase_order_id` int(11) DEFAULT NULL,
  `amount` double(20,2) DEFAULT NULL,
  `gst` double(20,2) DEFAULT NULL,
  `quantity2` int(11) NOT NULL DEFAULT 0,
  `return_qty2` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supplier_invoice_return_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_invoice_return_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_invoice_id` int(10) unsigned NOT NULL,
  `supplier_invoice_return_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `qty` double(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supplier_invoice_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_invoice_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_invoice_id` int(10) unsigned NOT NULL,
  `status` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `type` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supplier_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int(11) NOT NULL,
  `supplier_invoice_number` varchar(191) DEFAULT NULL,
  `internal_invoice_number` varchar(191) DEFAULT '',
  `eway_bill_no` varchar(191) DEFAULT NULL,
  `eway_bill_pdf` varchar(191) DEFAULT NULL,
  `vehicle_no` varchar(191) DEFAULT NULL,
  `tquantity` int(11) DEFAULT NULL,
  `tgst` double(20,2) DEFAULT NULL,
  `subTotal` double(20,2) DEFAULT NULL,
  `tamount` double(20,2) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `is_approved` int(11) NOT NULL DEFAULT 0,
  `purchase_order_type` varchar(191) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supplier_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `rate` double(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `c_name` varchar(191) NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `address1` varchar(191) DEFAULT NULL,
  `address2` varchar(191) DEFAULT NULL,
  `city` varchar(191) NOT NULL,
  `state` varchar(191) NOT NULL,
  `country` varchar(191) NOT NULL,
  `postcode` varchar(191) DEFAULT NULL,
  `gst` tinyint(1) NOT NULL DEFAULT 0,
  `gstin` varchar(191) DEFAULT NULL,
  `state_code` varchar(191) DEFAULT NULL,
  `pan` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone1` bigint(20) DEFAULT NULL,
  `phone2` bigint(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tds` int(11) DEFAULT NULL,
  `tdspercent` double(8,2) DEFAULT NULL,
  `gstpercent` double(8,2) DEFAULT NULL,
  `type` varchar(191) NOT NULL DEFAULT 'Furniture',
  `tds194q` double(8,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `temp_buyer_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp_buyer_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(191) NOT NULL,
  `c_name` varchar(191) DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `address1` varchar(191) DEFAULT NULL,
  `address2` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `state` varchar(191) DEFAULT NULL,
  `country` varchar(191) DEFAULT NULL,
  `postcode` varchar(191) DEFAULT NULL,
  `profit` double(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `final_price_percent` double(8,2) DEFAULT NULL,
  `cost_adjustments` double(8,2) DEFAULT NULL,
  `courierType` int(11) DEFAULT NULL,
  `admin_profit` double DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `temp_product_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `temp_product_table` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `subcategory_id` int(10) unsigned NOT NULL,
  `imageURL` varchar(191) DEFAULT NULL,
  `code` varchar(191) NOT NULL,
  `EAN` varchar(191) DEFAULT NULL,
  `HSN` varchar(191) DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `finishing` varchar(191) DEFAULT NULL,
  `gstslab` int(11) DEFAULT NULL,
  `width` double(8,2) DEFAULT NULL,
  `height` double(8,2) DEFAULT NULL,
  `depth` double(8,2) DEFAULT NULL,
  `volume` double(12,4) DEFAULT NULL,
  `hardware` varchar(191) DEFAULT NULL,
  `addons` varchar(191) DEFAULT NULL,
  `remarks` varchar(191) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `boxwidth` double(8,2) DEFAULT NULL,
  `boxheight` double(8,2) DEFAULT NULL,
  `boxdepth` double(8,2) DEFAULT NULL,
  `wholesalevolume` double(12,4) DEFAULT NULL,
  `dropshipvolume` double(12,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `temp_product_table_code_unique` (`code`),
  KEY `temp_product_table_category_id_foreign` (`category_id`),
  KEY `temp_product_table_subcategory_id_foreign` (`subcategory_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `upholestry_bill`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `upholestry_bill` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `contractor_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `amount` double(8,2) DEFAULT NULL,
  `upholestry_rate` double(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `upholestry_contractors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `upholestry_contractors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `c_name` varchar(191) NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `address1` varchar(191) DEFAULT NULL,
  `address2` varchar(191) DEFAULT NULL,
  `city` varchar(191) DEFAULT NULL,
  `state` varchar(191) DEFAULT NULL,
  `country` varchar(191) DEFAULT NULL,
  `postcode` varchar(191) DEFAULT NULL,
  `gst` tinyint(1) DEFAULT 0,
  `gstin` varchar(191) DEFAULT NULL,
  `state_code` varchar(191) DEFAULT NULL,
  `pan` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `phone1` bigint(20) DEFAULT NULL,
  `phone2` bigint(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tds` int(11) DEFAULT NULL,
  `tdspercent` double(8,2) DEFAULT NULL,
  `gstpercent` double(8,2) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `firstname` varchar(191) NOT NULL,
  `lastname` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `role` varchar(191) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `api_token` varchar(191) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `device_token` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wp_customers_infos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wp_customers_infos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `wp_customer_id` int(11) DEFAULT NULL,
  `wp_customer_name` varchar(191) DEFAULT NULL,
  `wp_customer_email` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `site_access` varchar(191) NOT NULL DEFAULT 'UK',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2018_09_08_122116_create_product_category_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2018_09_08_123739_create_product_table_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2018_09_10_081628_create_buyers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2018_09_10_082040_create_suppliers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2018_09_10_082642_create_contractor_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2018_09_30_202842_create_allocation_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2018_10_06_174356_create_reject_repair_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2018_10_07_125917_create_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2018_10_07_131619_create_purchase_order_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2018_10_09_081646_create_purchase_bill_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2018_10_09_082522_create_invoice_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2018_12_08_080721_create_po_table_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2018_12_18_122051_create_invoice_table_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2018_12_24_124054_create_pb_table_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2018_12_26_193321_create_packing_sheet_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2018_12_29_173430_create_packing_sheet_table_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2019_01_05_171230_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2019_01_23_093209_create_stockout_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2019_01_23_113111_create_stockout_table_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2019_03_19_062539_create_statename_statecode_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2019_03_27_094446_create_certificate_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2018_09_08_123730_create_product_subcategory_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2019_06_12_093414_change__eway_datatype_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2019_07_12_115937_add_five_columns_on_product_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2019_07_25_110101_update_lenght_totalgrosswt_and_totalwt_on_invoice_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2019_07_16_071424_temp__product_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2019_07_16_092554_temp_buyer_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2019_07_27_150239_pricing_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2019_07_29_110520_add_multiple_foreign_key_on_pricing_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2019_08_22_131143_add_hardware_columns_in_pricing_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2019_09_02_110758_add_buyer2_in_pricing_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2019_09_20_104829_add_tapestry_uni_cost_in_pricing_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2019_10_11_120816_add_eight_product_columns_in_pricing_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2019_10_18_074256_add_adjustment_under_pricing_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2019_10_18_105545_add_pricing_setting_fields_in_setting_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2019_10_18_125247_add_destination_column_in_pricing_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2019_11_07_164556_create_courier_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2020_07_28_132248_add_hardware_fields_to_product_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2020_08_02_072650_create_hardwares_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2020_08_02_074958_modify_hardware_fields_in_product_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2020_08_09_083803_add_hardware_quantity_to_product',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2020_08_19_112544_add_formula_fields_to_courier',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2020_08_26_035610_add_location_field_to_product',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2020_09_03_070454_add_location_field_to_pbtable',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2020_09_10_053653_create_product_locations_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2020_09_14_125546_add_location_field_to_stockouttable',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2020_09_22_061927_add_hardware_fields_to_pricing',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2020_09_27_074941_add_hardware_supplier_field_to_hardwares',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2020_09_27_171735_add_fuelchargepercent_in_couriers',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2020_09_28_063837_create_hardware_suppliers_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2020_09_28_071933_modify_hardware_supplier',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2020_09_29_042845_add_fuelsurcharge_in_pricing',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2020_10_04_054736_add_api_token',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2020_10_07_040125_add_finish_in_products',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2020_10_07_044927_add_contratctor_to_invoicetable',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2020_10_07_151052_create_packaging_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2020_10_09_074258_create_contractor_bill_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2020_10_14_041614_add_pricing_fields_to_temp_buyer',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2020_10_14_123732_create_stock_log_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2020_10_15_194525_modify_courier_type_in_tempbuyer',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2020_10_17_090601_create_product_grouping_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2020_10_18_153736_create_consumables_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2020_10_18_155555_create_product_swapping_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2020_10_21_104618_add_prod_remaining_field',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2020_10_21_112615_add_supp_inv_no_field',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2020_10_22_053246_add_swap_id_field',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2020_10_22_053424_add_swap_id_field_to_purchase_bill',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2020_10_22_053452_add_remarks_field_to_pb_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2020_10_22_054011_add_invoice_no_to_product_swapping',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2020_10_27_063009_create_upholestry_contractors_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2020_10_27_063310_create_upholestry_bill_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2020_10_30_075834_add_gst_and_tds_percent_to_supplier',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2020_10_30_084441_add_gst_and_tds_percent_to_contractor',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2020_10_30_092504_create_corner_bill_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2020_10_30_094256_add_gst_and_tds_percent_to_upho_contractor',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2020_11_04_082951_add_created_via_field_purchase_order',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2020_11_04_105921_add_product_quantity_field_corner_bill',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2020_11_05_141727_create_packaging',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2020_11_07_074811_add_corner_packaging_rates',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2020_11_07_083147_create_legs_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2020_11_09_065306_add_finish',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2020_11_09_110723_add_quantity_field',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2020_11_11_184811_create_cornerpackaging_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2020_11_11_185600_create_cornerpack_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2020_11_17_052608_add_supp_inv_no_stocklog',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2020_11_17_081128_add_shipping_bill_fields',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2020_11_22_062117_add_corner_and_l_quantity',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2020_11_25_062942_add_shipping_exchange_rate_field',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2020_11_28_083804_add_box_type_fields',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2020_11_29_144823_add_cpo_field',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2020_12_01_101047_add_size_field',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2020_12_02_062558_create_purchase_order_consumables',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2020_12_02_063108_create_poctable',49);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2020_12_02_072608_add_status_field',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2020_12_03_083127_add_type_field',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2020_12_04_182143_add_month_field',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2020_12_05_061329_create_recommended_pos_table',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2020_12_06_173547_create_purchase_order_recommended',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2020_12_06_174037_create_por_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2020_12_08_083920_add_sizes_fields_legs',50);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2020_12_16_075018_create_packinglist_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2020_12_16_075603_create_packinglistproducts_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2020_12_17_042410_add_weight_qty_fields',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2020_12_18_065636_add_carton_price_field',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2020_12_18_065924_create_pop_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2020_12_18_082915_add_type_field',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2020_12_22_102044_add_carton_price_fields',52);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2020_12_22_113853_add_box_fields_cartonpo_fields',52);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2020_12_23_032143_add_box_fields_price_fields',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2020_12_26_064727_create_quality_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2020_12_27_060744_add_imageurl_field',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2020_12_29_075151_create_channel',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2020_12_29_081456_add_channel_id_field',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2020_12_31_054520_create_performance_cards_table',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2020_12_31_055235_create_performance_card_products_table',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2021_01_04_074600_add_supplier_id_field',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2021_01_05_095438_add_priority_field',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2021_01_11_061807_create_packaging_pricing_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2021_01_14_102509_create_samples_table',58);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2021_01_19_054533_add_product_related_field',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2021_01_19_074714_add_status_field',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2021_01_19_091226_add_status_field',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2021_01_21_084218_add_line_drawing_field',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2021_01_22_115037_add_addtional_info_field',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2021_01_25_175923_add_ctnpo_no_field',63);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2021_01_27_070501_add_created_via_field',64);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2021_01_29_082334_add_receiveqty_field',64);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2021_01_31_174404_create_supplier_products_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2021_02_01_083057_create_finishing_rates_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2021_02_01_171029_create_suggested_products_table',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2021_02_04_082736_add_supplier_status_remark_field',67);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2021_02_05_150855_create_supplier_invoice_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2021_02_05_153835_create_supplier_invoice_products_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2021_02_10_173741_create_shipping_table',69);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2021_02_10_181059_create_shipping_lines_table',69);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2021_02_11_123926_add_po_field_so',69);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2021_02_11_170221_add_uk_fields_shipping',70);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2021_02_11_171345_add_uk_agent_field',70);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2021_02_12_170559_update_shipping_table',70);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2021_02_13_132029_add_user_id_filed_supplier_invoice',71);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2021_02_14_082850_add_month_to_shipping',71);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2021_02_19_050210_add_amount_gst_supplier_invoice_products',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2021_02_19_052941_add_delpoint_fields',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2021_02_24_080737_create_invoice_export_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2021_02_24_081555_add_export_field_in_invoice',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2021_02_24_082830_add_invoice_id_invoice_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2021_03_02_090402_create_sample_purchase_order_table',74);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2021_03_02_093014_create_postable_table',74);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2021_03_03_083715_add_is_deleted_field',74);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2021_03_03_094945_add_spo_no_field',74);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2021_03_08_073731_add_is_checked_field',75);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2021_03_10_103640_create_supplier_invoice_returns_table',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2021_03_10_104537_add_type_field',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2021_03_10_111415_create_supplier_invoice_return_products_table',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2021_03_16_052830_add_shipping_magnus_fields',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2021_03_16_170327_add_eta_at_port_shipping',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2021_04_06_081129_add_address_option_field_po',78);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2021_04_06_082324_add_description_field_potable',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2021_04_08_035351_add_factory_field',80);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2021_04_08_062724_create_notifications_table',81);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2021_04_08_120031_add_address_option_field_in_poc',82);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2021_04_08_120542_add_description_in_poc',82);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2021_04_13_114745_add_show_gsp_field',83);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2021_04_15_043549_add_sales_fields_to_invoice',84);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2021_05_03_061316_add_supplier_status_to_cpo',85);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2021_05_04_061551_add_po_type_field',86);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2021_07_16_065947_additional_fields_in_buyers',87);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2021_07_16_081909_create_login_securities_table',87);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2021_07_23_045655_create_small_hardware_table',88);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2021_07_27_094221_create_smallhardware_suppliers_table',89);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2021_07_27_094451_add_supplier_id_to_smallhardware',89);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2021_09_03_052505_add_box2_qty_field_cartonpo',90);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2021_09_13_091344_add_paymentstatus_duedate_fields',91);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2021_09_27_050556_create_device_token_users',92);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2021_09_27_053521_add_tds194q_suppliers',92);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2021_11_19_085657_create_smallhardwares_table',93);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2021_11_22_040014_create_small_hardware_products_table',94);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2021_12_14_072307_add_supplier_invoice_id_field_to_reject_repair',95);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2021_12_14_114443_add_is_debit_note_to_reject_repair',95);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2021_12_15_041137_add_is_challan_raised_to_reject_repair',95);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2021_12_15_045548_add_is_fields_to_reject_repair',95);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2021_12_27_142026_add_price_to_smhp',96);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2021_12_29_171846_add_debit_note_date',97);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2022_01_04_050122_create_questionnaire_table',97);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2022_01_04_050320_create_questionnaire_responses_table',97);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2022_01_07_085318_add_buyer_field',98);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2022_01_10_052347_add_ratenuk_field',98);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2022_01_25_064210_add_addtional_pricing_settings',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2022_03_03_093425_add_invoice_date_suppinv',100);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2022_04_05_074555_add_einvoice_fields',101);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2022_07_05_091408_add_export_sales_fields_invoices',102);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2022_07_05_093105_add_fbc_field_invoice_export',103);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2022_07_20_123732_create_sample_purchase_bills_table',104);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2022_07_20_153823_create_sample_pb_table_table',105);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2022_07_28_192659_add_send_mail_flag_invoice',106);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2022_08_02_044153_add_location_samples',107);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2022_08_02_044230_add_remainingqty_spb',107);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (204,'2022_08_02_044307_add_supp_inv_no_spb',107);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (206,'2022_09_19_055726_add_dnpending_reject_repair',108);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (207,'2022_10_12_102555_add_rr_manage_fields_rejrep',109);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (208,'2022_10_17_071137_add_outward_challan_rr',110);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (209,'2022_10_22_081859_add_purchase_bill_id_rr',111);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (210,'2022_10_22_081917_add_purchase_bill_id_rrp',111);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (211,'2022_11_01_062725_add_send_to_supplier_rrp',112);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (212,'2022_12_11_055820_create_erp_products_table',113);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (213,'2022_12_11_061049_create_erp_sheets_table',113);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (214,'2022_12_11_061227_create_erp_history_table',113);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (215,'2022_12_13_055619_add_q2_supp_inv_pro',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (216,'2022_12_13_061115_add_rem_box_qtys_pop',115);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (217,'2022_12_13_082851_add_remark_erp_history',116);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (218,'2022_12_25_151506_add_remark_field',117);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (219,'2023_01_01_165459_add_inv_uk_fields',118);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (220,'2023_01_02_085927_add_vat_field',119);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (221,'2023_01_03_033801_add_is_uk_field',119);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (222,'2023_01_04_084926_add_stock_erp_history',120);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (223,'2023_01_04_133607_add_uk_qty_erp_products',121);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (224,'2023_01_06_114915_create_erp_history_manager_table',121);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (225,'2023_01_06_115051_create_erp_sheets_manager_table',121);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (226,'2023_01_07_051805_add_bank_settings_uk',122);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (227,'2023_01_18_111930_add_bank_settings_other_uk',123);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (228,'2023_02_07_113956_add_reason_to_history',124);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (229,'2023_02_07_114751_add_reason_to_history_manager',124);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (230,'2023_02_07_183916_add_product_type_erp_products',125);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (231,'2023_04_21_102840_add_shipping_gst_invoice',126);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (232,'2023_04_26_085706_add_additional_fields_invoice_uk',127);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (233,'2023_06_05_100350_create_credit_notes_table',128);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (234,'2023_06_05_100529_create_credit_note_products_table',128);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (235,'2023_06_16_065619_add_entity_id_sl',129);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (236,'2023_06_26_114510_add_supplier_name_sl',130);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (237,'2023_07_31_073718_add_is_us_buyers',131);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (238,'2023_08_09_090255_add_is_eu_buyers',132);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (239,'2024_01_20_110132_create_sku_fulfillment_logs_table',133);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (240,'2024_01_20_110420_create_sku_fulfillment_qtys_table',133);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (241,'2024_01_20_110528_create_wp_customers_infos_table',133);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (242,'2024_01_20_110700_fulfillment_qty_to_erp_products',133);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (243,'2024_01_27_104614_site_access_to_erp_history',134);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (244,'2024_01_29_061215_site_access_to_erp_sheets',134);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (245,'2024_01_29_071121_site_access_to_erp_history_manager',134);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (246,'2024_01_29_071206_site_access_to_erp_sheets_manager',134);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (247,'2024_01_30_120211_update_uk_quantity_in_erp_products',134);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (248,'2024_02_09_123804_create_settings_option',135);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (249,'2024_02_14_051151_isadd_column_to_courier',135);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (250,'2024_02_19_104213_add_volumetric_weigth_lbs_to_settings',136);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (251,'2024_02_22_063104_add_custom_condition_to_courier',137);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (252,'2024_02_28_053432_admin_profit_to_temp_buyer_table',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (253,'2024_02_28_061134_add_profit_admincostdata_to_settings_option_table',138);
