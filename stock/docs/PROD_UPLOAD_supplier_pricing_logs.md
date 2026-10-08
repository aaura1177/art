# Supplier pricing change logs — files to upload to production

Run SQL first (phpMyAdmin / mysql):
  database/sql/supplier_product_price_logs.sql

Then upload these paths (relative to Laravel `stock/` app root):

## New files
- database/sql/supplier_product_price_logs.sql
- app/SupplierProductPriceLog.php
- app/Support/SupplierProductPriceLogWriter.php
- resources/views/supplier/pricing_history/index.blade.php
- resources/views/supplier/pricing_history/detail.blade.php

## Updated files
- app/supplierProduct.php
- app/Http/Controllers/supplierController.php
- app/Http/Controllers/ProductSupplierController.php
- app/Http/Controllers/AllocationManagmentController.php
- app/Imports/SupplierPricingImport.php
- app/Imports/SupplierProductImport.php
- app/Imports/SuppliersImports.php
- routes/web.php
- resources/views/supplier/pricing.blade.php
- resources/views/layouts/sidebar.blade.php

## After upload
1. Run the SQL on prod DB.
2. Clear view/route cache if used: `php artisan view:clear` and `php artisan route:clear`.
3. Open `/supplier/pricing` — Last update + history icon should show.
4. Open `/supplier/pricing/history` for the full log list.
