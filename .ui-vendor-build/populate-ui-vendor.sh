#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
NM="$ROOT/.ui-vendor-build/node_modules"
OUT="$ROOT/ui-vendor"

mkdir -p "$OUT"/{bootstrap/{css,js},jquery,jquery-easing,jquery-ui/{css,js},fontawesome-free/css,chart.js,datatables/buttons,bootstrap-select/{css,js},moment,select2/{css,js},parsley/{css,js},flatpickr/plugins/monthSelect,ckeditor}

cp "$NM/bootstrap/dist/css/bootstrap.min.css" "$NM/bootstrap/dist/css/bootstrap.min.css.map" "$OUT/bootstrap/css/"
cp "$NM/bootstrap/dist/js/bootstrap.bundle.min.js" "$NM/bootstrap/dist/js/bootstrap.bundle.min.js.map" "$OUT/bootstrap/js/"
cp "$NM/jquery/dist/jquery.min.js" "$OUT/jquery/"
cp "$NM/jquery.easing/jquery.easing.min.js" "$OUT/jquery-easing/"
cp "$NM/jquery-ui/dist/themes/base/jquery-ui.min.css" "$OUT/jquery-ui/css/"
cp "$NM/jquery-ui/dist/jquery-ui.min.js" "$OUT/jquery-ui/js/"
cp -r "$NM/@fortawesome/fontawesome-free/css/"* "$OUT/fontawesome-free/css/"
cp "$NM/chart.js/dist/chart.umd.js" "$OUT/chart.js/chart.umd.min.js"
cp "$NM/datatables.net/js/jquery.dataTables.min.js" "$OUT/datatables/"
cp "$NM/datatables.net-bs5/css/dataTables.bootstrap5.min.css" "$OUT/datatables/"
cp "$NM/datatables.net-bs5/js/dataTables.bootstrap5.min.js" "$OUT/datatables/"
cp "$NM/datatables.net-fixedcolumns/js/dataTables.fixedColumns.min.js" "$OUT/datatables/"
cp "$NM/datatables.net-plugins/sorting/datetime-moment.js" "$OUT/datatables/datetime-moment.js"
cp "$NM/datatables.net-buttons/js/dataTables.buttons.min.js" "$OUT/datatables/buttons/"
cp "$NM/datatables.net-buttons/js/buttons.html5.min.js" "$OUT/datatables/buttons/"
cp "$NM/datatables.net-buttons/js/buttons.print.min.js" "$OUT/datatables/buttons/"
cp "$NM/datatables.net-buttons/js/buttons.colVis.min.js" "$OUT/datatables/buttons/"
cp "$NM/datatables.net-buttons-dt/css/buttons.dataTables.min.css" "$OUT/datatables/buttons/"
cp "$NM/jszip/dist/jszip.min.js" "$OUT/datatables/buttons/"
cp "$NM/bootstrap-select/dist/css/bootstrap-select.min.css" "$OUT/bootstrap-select/css/"
cp "$NM/bootstrap-select/dist/js/bootstrap-select.min.js" "$OUT/bootstrap-select/js/"
cp "$NM/moment/min/moment.min.js" "$OUT/moment/"
cp "$NM/select2/dist/css/select2.min.css" "$OUT/select2/css/"
cp "$NM/select2/dist/js/select2.min.js" "$OUT/select2/js/"
cp "$NM/parsleyjs/dist/parsley.min.js" "$OUT/parsley/js/"
cp "$NM/parsleyjs/src/parsley.css" "$OUT/parsley/css/parsley.css"
cp "$NM/flatpickr/dist/flatpickr.min.css" "$NM/flatpickr/dist/flatpickr.min.js" "$OUT/flatpickr/"
cp "$NM/flatpickr/dist/plugins/monthSelect/"* "$OUT/flatpickr/plugins/monthSelect/" 2>/dev/null || true
cp "$NM/@ckeditor/ckeditor5-build-classic/build/ckeditor.js" "$OUT/ckeditor/"

rm -f "$OUT/chart.js/Chart.min.js" "$OUT/chart.js/Chart.js" "$OUT/chart.js/Chart.bundle.js" "$OUT/chart.js/Chart.bundle.min.js" 2>/dev/null || true
rm -f "$OUT/datatables/dataTables.bootstrap4."* "$OUT/datatables/jquery.dataTables.js" 2>/dev/null || true

echo "ui-vendor populated from node_modules"
