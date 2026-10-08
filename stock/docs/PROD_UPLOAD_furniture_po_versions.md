# Furniture PO versioning — files to upload to production

Run SQL first (phpMyAdmin / mysql):
  database/sql/purchase_order_versions.sql

(Creates `purchase_order_versions` + `purchase_order_activity_logs`)

## Behaviour reminder
- Create PO: no version
- First content update: v1, then v2…
- Send / cancel / status / accept-PO: activity log only
- Raise invoice (single + multi furniture): blocked until ALL versions accepted
- Live PO tables unchanged for invoice/remqty

## New files
- database/sql/purchase_order_versions.sql
- app/PurchaseOrderVersion.php
- app/PurchaseOrderActivityLog.php
- app/Support/PurchaseOrderVersionWriter.php
- resources/views/purchaseOrder/version_history.blade.php
- resources/views/purchaseOrder/partials/version_changes.blade.php
- resources/views/supplierUser/po_version_history.blade.php
- resources/views/supplierUser/partials/pending_po_versions_modal.blade.php
- docs/PROD_UPLOAD_furniture_po_versions.md

## Updated files
- app/Http/Controllers/purchaseOrderController.php
- app/Http/Controllers/supplierUserController.php
- app/Providers/AppServiceProvider.php
- routes/web.php
- resources/views/layouts/app.blade.php
- resources/views/purchaseOrder/index.blade.php
- resources/views/supplierUser/acceptedPurchaseOrders.blade.php

## After upload
1. Run the SQL on prod DB (if not already).
2. Clear caches if used: `php artisan view:clear` / `php artisan route:clear`.
3. Admin: `/purchaseOrder` shows vN + history icon → `/purchaseOrder/version-history/{id}`
4. Supplier: Accepted POs → history / Accept versions → then Raise Invoice
