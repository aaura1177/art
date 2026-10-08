# Bootstrap 5 migration (service/)

Source of truth: **`public_html/service/`** only.

## Rebuild ui-vendor

```bash
cd .ui-vendor-build
npm install   # only if node_modules missing
bash populate-ui-vendor.sh
```

## Re-run Blade migration

```bash
php scripts/migrate-bs5-all.php
```

## Verification

```bash
rg -l 'cdn\.|jsdelivr|cdnjs|code\.jquery' stock/resources/views --glob '*.blade.php' | wc -l   # 0
rg -l 'data-toggle|data-dismiss' stock/resources/views --glob '*.blade.php' | wc -l              # 0
test ! -f ui-vendor/chart.js/Chart.min.js && echo OK
cd stock && php artisan view:clear
```
