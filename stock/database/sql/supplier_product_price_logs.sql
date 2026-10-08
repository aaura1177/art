-- Supplier product price change logs
-- Run this once on production (phpMyAdmin / mysql CLI). No Laravel migration.
-- Tracks rate, UK 45 rate, revise/pending, and approve events.

CREATE TABLE IF NOT EXISTS `supplier_product_price_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `supplier_product_id` int unsigned DEFAULT NULL,
  `product_id` int unsigned NOT NULL,
  `supplier_id` int unsigned NOT NULL,
  `event_type` varchar(40) NOT NULL COMMENT 'create|update|revise|approve|remove',
  `source` varchar(60) NOT NULL COMMENT 'import|supplier_create|supplier_update|revise|approve|allocation|csv_import',
  `snapshot_json` longtext,
  `changed_fields_json` longtext,
  `change_summary` varchar(500) DEFAULT NULL,
  `changed_by` int unsigned DEFAULT NULL,
  `changed_by_label` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_spp_logs_product_supplier` (`product_id`, `supplier_id`),
  KEY `idx_spp_logs_supplier_product` (`supplier_product_id`),
  KEY `idx_spp_logs_created` (`created_at`),
  KEY `idx_spp_logs_event` (`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
