-- Furniture purchase order versioning + activity logs
-- Run once on production (phpMyAdmin / mysql CLI). No Laravel migration.
-- Versions: first content UPDATE = v1, then v2... (create PO does NOT create a version)
-- Activity: send / cancel / status / accept-PO (no version number)

CREATE TABLE IF NOT EXISTS `purchase_order_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int unsigned NOT NULL,
  `pono` varchar(100) DEFAULT NULL,
  `version` int unsigned NOT NULL,
  `snapshot_json` longtext,
  `before_snapshot_json` longtext,
  `changed_fields_json` longtext,
  `change_summary` varchar(500) DEFAULT NULL,
  `changed_by` int unsigned DEFAULT NULL,
  `changed_by_label` varchar(255) DEFAULT NULL,
  `supplier_accepted_at` timestamp NULL DEFAULT NULL,
  `supplier_accepted_by` int unsigned DEFAULT NULL,
  `supplier_accepted_by_label` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_po_version` (`purchase_order_id`, `version`),
  KEY `idx_po_versions_po` (`purchase_order_id`),
  KEY `idx_po_versions_accepted` (`purchase_order_id`, `supplier_accepted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `purchase_order_activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int unsigned NOT NULL,
  `pono` varchar(100) DEFAULT NULL,
  `event_type` varchar(40) NOT NULL COMMENT 'send|cancel|status|accept_po|other',
  `from_value` varchar(255) DEFAULT NULL,
  `to_value` varchar(255) DEFAULT NULL,
  `summary` varchar(500) DEFAULT NULL,
  `changed_by` int unsigned DEFAULT NULL,
  `changed_by_label` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_po_activity_po` (`purchase_order_id`),
  KEY `idx_po_activity_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
