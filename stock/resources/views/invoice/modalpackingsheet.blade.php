@extends('layouts.modal')
@include('layouts.partials.modal-print-styles', ['printFontSize' => 12])
<style>
/* Screen + print: Chrome-safe repeating header (title outside border; full-height divider) */
.invoice-export-repeat-title {
  text-align: center;
  margin: 0 0 10px;
}
.invoice-export-repeat-title .h-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
}
.invoice-export-repeat-meta {
  display: table;
  width: 100%;
  table-layout: fixed;
  border-collapse: collapse;
  border-spacing: 0;
  height: 100%;
}
.invoice-export-repeat-meta .meta-left,
.invoice-export-repeat-meta .meta-right {
  display: table-cell;
  vertical-align: top;
  width: 50%;
  box-sizing: border-box;
  padding: 0;
}
.invoice-export-repeat-meta .meta-pad {
  padding: 40px 14px 14px;
  box-sizing: border-box;
}
.invoice-export-repeat-meta .meta-right .meta-pad {
  padding-right: 8px;
}
.invoice-export-repeat-meta .meta-v-line {
  display: table-cell;
  width: 1px;
  max-width: 1px;
  padding: 0;
  margin: 0;
  border-left: 1px solid #000;
  font-size: 0;
  line-height: 0;
  vertical-align: top;
}
.invoice-export-repeat-meta img {
  width: 72px;
  height: auto;
  margin-bottom: 2px;
}
.invoice-export-repeat-meta p {
  margin: 0;
  line-height: 1.25;
  font-size: 13px;
}
.invoice-export-repeat-meta .meta-inv-no {
  font-size: 1.3rem;
  font-weight: 700;
  margin: 0 0 2px;
}
.invoice-export-repeat-meta .meta-inv-date {
  font-size: 14px;
  font-weight: 700;
  margin: 0 0 0.85rem;
}
.invoice-export-repeat-meta .meta-inv-no .packing-meta-value,
.invoice-export-repeat-meta .meta-inv-date .packing-meta-value {
  font-weight: 700 !important;
}
/* Page 1: title plain; invoice meta left; BUYER / stuffing stay right (sample layout) */
.packing-sheet-page1-wrap > header h1 {
  text-align: center;
  text-decoration: none !important;
  margin: 0 0 0.5rem;
}
.packing-sheet-page1 #buyer,
.packing-sheet-page1 #buyer.packing-sheet-page1-meta {
  text-align: left !important;
  width: 100%;
  max-width: 100%;
  float: none !important;
  padding-left: 0 !important;
  padding-right: 10px !important;
}
.packing-sheet-page1 #buyer h1,
.packing-sheet-page1 #buyer h3,
.packing-sheet-page1 #buyer p,
.packing-sheet-page1 #buyer .packing-meta-line {
  text-align: left !important;
  margin-left: 0 !important;
  margin-right: 0 !important;
  font-weight: 400 !important;
}
.packing-sheet-page1 #buyer strong {
  font-weight: 700 !important;
}
.packing-sheet-page1 #buyer .packing-meta-value {
  font-weight: 400 !important;
}
.packing-sheet-page1 #buyer h1 {
  margin: 0 0 0.15rem !important;
  line-height: 1.2 !important;
  font-size: 1.45rem !important;
  font-weight: 700 !important;
}
.packing-sheet-page1 #buyer h1 strong,
.packing-sheet-page1 #buyer h1 .packing-meta-value {
  font-size: 1.45rem !important;
  font-weight: 700 !important;
}
.packing-sheet-page1 #buyer h3 {
  margin: 0 0 0.15rem !important;
  line-height: 1.2 !important;
  font-size: 14px !important;
  font-weight: 700 !important;
}
.packing-sheet-page1 #buyer h3 strong,
.packing-sheet-page1 #buyer h3 .packing-meta-value {
  font-size: 14px !important;
  font-weight: 700 !important;
}
.packing-sheet-page1 #buyer .packing-meta-line {
  margin: 0 0 0.12rem !important;
  line-height: 1.3 !important;
  white-space: nowrap !important;
  font-size: 13px !important;
}
.packing-sheet-page1 #buyer .packing-meta-line .packing-meta-value {
  font-size: 12px !important;
}
.packing-sheet-page1 #buyer .packing-meta-line.mt-4 {
  margin-top: 1.1rem !important;
}
.packing-sheet-page1 #supplier.pull-right,
.packing-sheet-page1 #supplier {
  text-align: right !important;
  width: 100%;
  float: none !important;
}
.packing-sheet-page1 #supplier address,
.packing-sheet-page1 #supplier h3,
.packing-sheet-page1 #supplier p {
  text-align: right !important;
}

.container.packing-sheet-page2 {
  border: none !important;
}
/*
  Chrome-safe product table:
  - MUST use border-collapse:collapse so thead repeats on every printed page
  - no table-striped (BS5 box-shadow prints as uneven thick lines)
*/
.packing-sheet-products-table {
  border-collapse: collapse !important;
  border-spacing: 0 !important;
  width: 100% !important;
}
.packing-sheet-products-table thead tr.invoice-export-title-row > th {
  border: none !important;
  background: #fff !important;
  padding: 0 0 14px !important;
  font-weight: normal !important;
}
.packing-sheet-products-table thead tr.invoice-export-repeat-header > th.packing-meta-left-cell {
  border-top: 3px solid #000 !important;
  border-left: 3px solid #000 !important;
  border-bottom: 1px solid #000 !important;
  border-right: 1px solid #000 !important; /* aligns with QTY column start */
  background: #fff !important;
  padding: 0 !important;
  font-weight: normal !important;
  text-align: left !important;
  vertical-align: top !important;
}
.packing-sheet-products-table thead tr.invoice-export-repeat-header > th.packing-meta-right-cell {
  border-top: 3px solid #000 !important;
  border-right: 3px solid #000 !important;
  border-bottom: 1px solid #000 !important;
  border-left: none !important;
  background: #fff !important;
  padding: 0 !important;
  font-weight: normal !important;
  text-align: left !important;
  vertical-align: top !important;
}
.packing-sheet-products-table tfoot tr.packing-footer-split > td.packing-footer-left {
  border-top: 3px solid #000 !important;
  border-left: 3px solid #000 !important;
  border-bottom: 3px solid #000 !important;
  border-right: 1px solid #000 !important; /* same vertical line as header / QTY start */
  padding: 8px 10px !important;
  vertical-align: top !important;
  background: #fff !important;
}
.packing-sheet-products-table tfoot tr.packing-footer-split > td.packing-footer-right {
  border-top: 3px solid #000 !important;
  border-right: 3px solid #000 !important;
  border-bottom: 3px solid #000 !important;
  border-left: none !important;
  padding: 8px 10px !important;
  vertical-align: top !important;
  background: #fff !important;
}
.packing-sheet-products-table thead tr.invoice-export-colhead > th,
.packing-sheet-products-table tbody td {
  border: 1px solid #000 !important;
  background: #fff !important;
  vertical-align: top !important;
  box-shadow: none !important;
}
.packing-sheet-products-table thead tr.invoice-export-colhead > th {
  background: #e9ecef !important;
  font-weight: 700 !important;
  vertical-align: middle !important;
}
.packing-sheet-products-table thead tr.invoice-export-colhead > th:first-child,
.packing-sheet-products-table tbody td:first-child {
  border-left: 3px solid #000 !important;
}
.packing-sheet-products-table thead tr.invoice-export-colhead > th:last-child,
.packing-sheet-products-table tbody td:last-child {
  border-right: 3px solid #000 !important;
}
.packing-sheet-products-table tfoot tr.border-less-row > td {
  border: none !important;
  background: transparent !important;
}
.packing-sheet-products-table tfoot tr.border-less-row:first-child > td {
  border-top: 3px solid #000 !important;
}
/* Header meta typography */
.packing-sheet-products-table .invoice-export-repeat-meta .meta-left p,
.packing-sheet-products-table .invoice-export-repeat-meta .meta-left-pad p {
  font-weight: 700 !important;
}
.packing-sheet-products-table .invoice-export-repeat-meta .meta-right p:not(.meta-inv-no):not(.meta-inv-date) {
  font-weight: 400 !important;
}
.packing-sheet-products-table .invoice-export-repeat-meta .meta-right p:not(.meta-inv-no):not(.meta-inv-date) > .packing-meta-value {
  font-weight: 400 !important;
}
.packing-sheet-products-table .invoice-export-repeat-meta .meta-right strong {
  font-weight: 700 !important;
}
.packing-sheet-products-table .invoice-export-repeat-meta .meta-inv-no,
.packing-sheet-products-table .invoice-export-repeat-meta .meta-inv-date {
  font-weight: 700 !important;
}

@media print {
  .container,
  .container p,
  .container h1,
  .container h3,
  .container address,
  .container .table th,
  .container .table td {
    font-family: Arial, Helvetica, sans-serif !important;
  }

  /* Page 1: no title underline; invoice meta left; BUYER/stuffing right */
  .packing-sheet-page1-wrap > header h1 {
    text-align: center !important;
    text-decoration: none !important;
    margin: 0 0 0.35rem !important;
    line-height: 1.15 !important;
  }
  .packing-sheet-page1 {
    margin-bottom: 0 !important;
  }
  .packing-sheet-page1 .row.box-space {
    margin-top: 0.25rem !important;
  }
  .packing-sheet-page1 hr {
    margin-top: 0.15rem !important;
    margin-bottom: 0.15rem !important;
  }
  .packing-sheet-page1 #buyer,
  .packing-sheet-page1 #buyer.packing-sheet-page1-meta {
    text-align: left !important;
    width: 100% !important;
    max-width: 100% !important;
    float: none !important;
    padding-left: 0 !important;
    padding-right: 10px !important;
  }
  .packing-sheet-page1 #buyer h1,
  .packing-sheet-page1 #buyer h3,
  .packing-sheet-page1 #buyer p,
  .packing-sheet-page1 #buyer .packing-meta-line {
    text-align: left !important;
    font-weight: 400 !important;
  }
  .packing-sheet-page1 #buyer strong {
    font-weight: 700 !important;
  }
  .packing-sheet-page1 #buyer .packing-meta-value {
    font-weight: 400 !important;
  }
  .packing-sheet-page1 #buyer h1 {
    font-size: 1.45rem !important;
    margin: 0 0 0.15rem !important;
    line-height: 1.2 !important;
    font-weight: 700 !important;
  }
  .packing-sheet-page1 #buyer h1 strong,
  .packing-sheet-page1 #buyer h1 .packing-meta-value {
    font-size: 1.45rem !important;
    font-weight: 700 !important;
  }
  .packing-sheet-page1 #buyer h3 {
    font-size: 14px !important;
    margin: 0 0 0.15rem !important;
    line-height: 1.2 !important;
    font-weight: 700 !important;
  }
  .packing-sheet-page1 #buyer h3 strong,
  .packing-sheet-page1 #buyer h3 .packing-meta-value {
    font-size: 14px !important;
    font-weight: 700 !important;
  }
  .packing-sheet-page1 #buyer .packing-meta-line {
    margin: 0 0 0.12rem !important;
    line-height: 1.3 !important;
    white-space: nowrap !important;
    font-size: 13px !important;
  }
  .packing-sheet-page1 #buyer .packing-meta-line .packing-meta-value {
    font-size: 12px !important;
  }
  .packing-sheet-page1 #buyer .packing-meta-line.mt-4 {
    margin-top: 1.1rem !important;
  }
  .packing-sheet-page1 #supplier.pull-right,
  .packing-sheet-page1 #supplier {
    text-align: right !important;
    width: 100% !important;
    float: none !important;
  }
  .packing-sheet-page1 #supplier address,
  .packing-sheet-page1 #supplier h3,
  .packing-sheet-page1 #supplier p {
    text-align: right !important;
  }
  .packing-sheet-page1 .mt-2 {
    margin-top: 0.2rem !important;
  }
  .packing-sheet-page1 .packing-page1-decl {
    margin-bottom: 0 !important;
  }
  .packing-sheet-page1 .invoice-sig-gap {
    padding-top: 22px !important;
  }
  .packing-sheet-page1 p {
    line-height: 1.2 !important;
  }

  /* Chrome-safe repeating header/footer + product grid */
  .packing-sheet-products-table thead {
    display: table-header-group !important;
  }
  .packing-sheet-products-table tfoot {
    display: table-footer-group !important;
  }
  .container.packing-sheet-page2 {
    border: none !important;
  }
  .invoice-export-repeat-title {
    text-align: center;
    margin: 0 0 10px !important;
  }
  .invoice-export-repeat-title .h-title {
    font-size: 1.35rem;
    font-weight: 700;
    margin: 0 !important;
    line-height: 1.15 !important;
  }
  .invoice-export-repeat-meta {
    display: table !important;
    width: 100% !important;
    height: 100% !important;
    table-layout: fixed !important;
    border-collapse: collapse !important;
    border-spacing: 0 !important;
  }
  .invoice-export-repeat-meta .meta-left,
  .invoice-export-repeat-meta .meta-right {
    display: table-cell !important;
    vertical-align: top !important;
    width: 50% !important;
    float: none !important;
    padding: 0 !important;
  }
  .invoice-export-repeat-meta .meta-pad {
    padding: 40px 14px 14px !important;
    box-sizing: border-box !important;
  }
  .invoice-export-repeat-meta .meta-right .meta-pad {
    padding-right: 8px !important;
  }
  .invoice-export-repeat-meta .meta-v-line {
    display: table-cell !important;
    width: 1px !important;
    max-width: 1px !important;
    padding: 0 !important;
    margin: 0 !important;
    background: transparent !important;
    border: none !important;
    border-left: 1px solid #000 !important;
    font-size: 0 !important;
    line-height: 0 !important;
    vertical-align: top !important;
  }
  .invoice-export-repeat-meta .meta-right {
    text-align: left !important;
    margin-left: 0 !important;
  }
  .invoice-export-repeat-meta img {
    width: 72px !important;
    height: auto !important;
    margin-bottom: 2px;
  }
  .invoice-export-repeat-meta p {
    margin: 0 !important;
    font-size: 11.5px !important;
    line-height: 1.2 !important;
    text-align: left !important;
    white-space: nowrap !important;
  }
  .invoice-export-repeat-meta .meta-left p,
  .invoice-export-repeat-meta .meta-left-pad p {
    font-weight: 700 !important;
  }
  .invoice-export-repeat-meta .meta-right p:not(.meta-inv-no):not(.meta-inv-date) {
    font-weight: 400 !important;
  }
  .invoice-export-repeat-meta .meta-right p:not(.meta-inv-no):not(.meta-inv-date) > .packing-meta-value {
    font-weight: 400 !important;
    font-size: 11px !important;
  }
  .invoice-export-repeat-meta .meta-right strong {
    font-weight: 700 !important;
  }
  .packing-sheet-products-table thead .invoice-export-repeat-meta .meta-inv-no,
  .packing-sheet-products-table thead .invoice-export-repeat-meta p.meta-inv-no {
    font-size: 1.2rem !important;
    font-weight: 700 !important;
    margin: 0 0 2px !important;
    line-height: 1.15 !important;
  }
  .packing-sheet-products-table thead .invoice-export-repeat-meta .meta-inv-date,
  .packing-sheet-products-table thead .invoice-export-repeat-meta p.meta-inv-date {
    font-size: 13px !important;
    font-weight: 700 !important;
    margin: 0 0 0.85rem !important;
    line-height: 1.2 !important;
  }

  .packing-sheet-products-table,
  .packing-sheet-products-table.table,
  .packing-sheet-products-table.table-bordered {
    border-collapse: collapse !important;
    border-spacing: 0 !important;
    width: 100% !important;
  }
  .packing-sheet-products-table.table-striped > tbody > tr > * {
    box-shadow: none !important;
    background-color: #fff !important;
  }
  .packing-sheet-products-table thead tr.invoice-export-title-row > th {
    border: none !important;
    background: #fff !important;
    padding: 0 0 14px !important;
    font-weight: normal !important;
    text-align: center !important;
  }
  .packing-sheet-products-table thead tr.invoice-export-repeat-header > th.packing-meta-left-cell {
    border-top: 3px solid #000 !important;
    border-left: 3px solid #000 !important;
    border-bottom: 1px solid #000 !important;
    border-right: 1px solid #000 !important;
    background: #fff !important;
    font-weight: normal !important;
    text-align: left !important;
    padding: 0 !important;
    vertical-align: top !important;
  }
  .packing-sheet-products-table thead tr.invoice-export-repeat-header > th.packing-meta-right-cell {
    border-top: 3px solid #000 !important;
    border-right: 3px solid #000 !important;
    border-bottom: 1px solid #000 !important;
    border-left: none !important;
    background: #fff !important;
    font-weight: normal !important;
    text-align: left !important;
    padding: 0 !important;
    vertical-align: top !important;
  }
  .packing-sheet-products-table tfoot tr.packing-footer-split > td.packing-footer-left {
    border-top: 3px solid #000 !important;
    border-left: 3px solid #000 !important;
    border-bottom: 3px solid #000 !important;
    border-right: 1px solid #000 !important;
    padding: 8px 10px !important;
    vertical-align: top !important;
    background: #fff !important;
  }
  .packing-sheet-products-table tfoot tr.packing-footer-split > td.packing-footer-right {
    border-top: 3px solid #000 !important;
    border-right: 3px solid #000 !important;
    border-bottom: 3px solid #000 !important;
    border-left: none !important;
    padding: 8px 10px !important;
    vertical-align: top !important;
    background: #fff !important;
  }
  .packing-sheet-products-table thead tr.invoice-export-colhead > th,
  .packing-sheet-products-table tbody td {
    border: 1px solid #000 !important;
    background: #fff !important;
    box-shadow: none !important;
  }
  .packing-sheet-products-table thead tr.invoice-export-colhead > th {
    background: #e9ecef !important;
    font-weight: 700 !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
  .packing-sheet-products-table thead tr.invoice-export-colhead > th:first-child,
  .packing-sheet-products-table tbody td:first-child {
    border-left: 3px solid #000 !important;
  }
  .packing-sheet-products-table thead tr.invoice-export-colhead > th:last-child,
  .packing-sheet-products-table tbody td:last-child {
    border-right: 3px solid #000 !important;
  }
  .packing-sheet-products-table tfoot tr.border-less-row > td {
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
  }
  .packing-sheet-products-table tfoot tr.border-less-row:first-child > td {
    border-top: 3px solid #000 !important;
  }
}

/*
  Firefox-only (Chrome unchanged):
  - Slim repeating header under Firefox ~250px limit
  - border-collapse:separate so product grid lines print on multi-line rows
*/
@supports (-moz-appearance: none) {
  @media print {
    .packing-sheet-products-table,
    .packing-sheet-products-table.table,
    .packing-sheet-products-table.table-bordered {
      border-collapse: separate !important;
      border-spacing: 0 !important;
      width: 100% !important;
    }
    .packing-sheet-products-table thead {
      display: table-header-group !important;
    }
    .packing-sheet-products-table tbody {
      display: table-row-group !important;
    }
    .packing-sheet-products-table tfoot {
      display: table-footer-group !important;
    }
    .packing-sheet-products-table thead tr.invoice-export-title-row > th {
      padding: 0 0 2px !important;
      border: none !important;
      background: #fff !important;
    }
    .invoice-export-repeat-title {
      margin: 0 0 2px !important;
    }
    .invoice-export-repeat-title .h-title {
      font-size: 1.05rem !important;
      line-height: 1.05 !important;
    }
    .packing-sheet-products-table thead img {
      width: 40px !important;
      height: auto !important;
      margin-bottom: 0 !important;
    }
    .packing-sheet-products-table thead .invoice-export-repeat-meta .meta-pad {
      padding: 4px 8px !important;
    }
    .packing-sheet-products-table thead .invoice-export-repeat-meta p {
      font-size: 9.5px !important;
      line-height: 1.1 !important;
    }
    .packing-sheet-products-table thead .invoice-export-repeat-meta .meta-inv-no,
    .packing-sheet-products-table thead .invoice-export-repeat-meta p.meta-inv-no {
      font-size: 0.95rem !important;
      margin: 0 !important;
    }
    .packing-sheet-products-table thead .invoice-export-repeat-meta .meta-inv-date,
    .packing-sheet-products-table thead .invoice-export-repeat-meta p.meta-inv-date {
      font-size: 11px !important;
      margin: 0 0 0.2rem !important;
    }
    .packing-sheet-products-table thead tr.invoice-export-repeat-header > th.packing-meta-left-cell {
      border-top: 3px solid #000 !important;
      border-left: 3px solid #000 !important;
      border-right: 1px solid #000 !important;
      border-bottom: 1px solid #000 !important;
      background: #fff !important;
      padding: 0 !important;
    }
    .packing-sheet-products-table thead tr.invoice-export-repeat-header > th.packing-meta-right-cell {
      border-top: 3px solid #000 !important;
      border-right: 3px solid #000 !important;
      border-left: none !important;
      border-bottom: 1px solid #000 !important;
      background: #fff !important;
      padding: 0 !important;
    }
    .packing-sheet-products-table thead tr.invoice-export-colhead > th,
    .packing-sheet-products-table tbody td {
      border: none !important;
      border-top: 1px solid #000 !important;
      border-left: 1px solid #000 !important;
      box-shadow: none !important;
      background: #fff !important;
      background-clip: padding-box !important;
      -moz-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
      vertical-align: top !important;
    }
    .packing-sheet-products-table thead tr.invoice-export-colhead > th {
      background: #e9ecef !important;
      font-weight: 700 !important;
      font-size: 9px !important;
      padding: 2px 2px !important;
      line-height: 1.1 !important;
    }
    .packing-sheet-products-table thead tr.invoice-export-colhead > th:last-child,
    .packing-sheet-products-table tbody td:last-child {
      border-right: 3px solid #000 !important;
    }
    .packing-sheet-products-table thead tr.invoice-export-colhead > th:first-child,
    .packing-sheet-products-table tbody td:first-child {
      border-left: 3px solid #000 !important;
    }
    .packing-sheet-products-table tbody tr:last-child td {
      border-bottom: 1px solid #000 !important;
    }
    .packing-sheet-products-table tfoot tr.packing-footer-split > td.packing-footer-left {
      border-top: 3px solid #000 !important;
      border-left: 3px solid #000 !important;
      border-right: 1px solid #000 !important;
      border-bottom: 3px solid #000 !important;
      background: #fff !important;
    }
    .packing-sheet-products-table tfoot tr.packing-footer-split > td.packing-footer-right {
      border-top: 3px solid #000 !important;
      border-right: 3px solid #000 !important;
      border-left: none !important;
      border-bottom: 3px solid #000 !important;
      background: #fff !important;
    }
    .packing-sheet-products-table tfoot tr.border-less-row > td {
      border: none !important;
      background: transparent !important;
    }
    .packing-sheet-products-table tfoot tr.border-less-row:first-child > td {
      border-top: 3px solid #000 !important;
    }
    .packing-sheet-products-table tfoot .invoice-sig-gap {
      padding-top: 14px !important;
    }
    .packing-sheet-products-table tfoot h3 {
      font-size: 11px !important;
      margin: 0 0 2px !important;
    }
    .packing-sheet-products-table tfoot p {
      font-size: 9.5px !important;
      line-height: 1.15 !important;
    }
  }
}
</style>

@php
$buyerRefNo = strtoupper(trim((string) ($invoice->buyerorderno ?? '')));
$showUkWastePackaging = strpos($buyerRefNo, 'UK-') === 0;
@endphp

<body>
  <div class="packing-sheet-page1-wrap">
  <header>
    <h1 class="text-center">Packing Sheet</h1>  
  </header>
  <script type="text/php">
        if ( isset($pdf) ) {
            $x = 72;
            $y = 18;
            $text = "{PAGE_NUM} of {PAGE_COUNT}";
            $font = $fontMetrics->get_font("helvetica", "bold");
            $size = 6;
            $color = array(255,0,0);
            $word_space = 0.0;  //  default
            $char_space = 0.0;  //  default
            $angle = 0.0;   //  default
            $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
        }
    </script>
  
  <div class="container packing-sheet-page1">

    <!-- Header row -->
    <div class="row box-space">   
        <div class="col-6">
          @if(isset($companyDetails))
          @php
          $imageName = $companyDetails->logoUrl;
      @endphp
      
      @if(array_key_exists($imageName, $fileMap))
          <img width="450px"  src="{{ $fileMap[$imageName] }}" alt="Logo Image" style="width:100px;">
      @else
          <p>Image not found</p>
      @endif
            <address class="mt-2">
              <p style="font-weight: 700;">{{$companyDetails->c_name}}</p>
              <p style="font-weight: 700;">{{$companyDetails->address1}}</p>
              <p style="font-weight: 700;">{{$companyDetails->address2}}, {{$companyDetails->city}} - {{$companyDetails->postcode}}, India</p>
              <p>Website: www.globalvisioncompany.com</p>
              <p>Tel # {{$companyDetails->phone1}} / {{$companyDetails->phone2}}</p>
              <p>E-mail: {{$companyDetails->email}}</p>
              <p>PAN # {{$companyDetails->pan}}</p>
              <p>IEC #  {{$companyDetails->iec}}</p>
              <p>RBI Code # {{$companyDetails->rbi}}</p>
              <p>GSTIN: {{$companyDetails->gstin}}</p>
              <p>LUT Ref. No. & Date: {{$companyDetails->lut}}</p>
            </address> 
            @endif
        </div>
        <div class="col-6">
            <div id="buyer" class="packing-sheet-page1-meta">
              @if(isset($invoice))
              <h1 class="meta-inv-no" style="font-weight:700!important;font-size:1.45rem!important;margin:0 0 0.15rem!important;">Invoice No. {{$invoice->invoiceno}}</h1>
              <h3 class="meta-inv-date" style="font-weight:700!important;font-size:14px!important;margin:0 0 0.15rem!important;">Invoice Date: {{date('d-M-Y',strtotime($invoice->date))}}</h3>
              <p class="packing-meta-line mt-4"><strong>Buyer Ref. No: </strong><span class="packing-meta-value">{{$invoice->buyerorderno}}</span></p>
              <p class="packing-meta-line"><strong>Container No: </strong><span class="packing-meta-value">{{$invoice->containerno}}</span></p>
              <p class="packing-meta-line"><strong>Vehicle No: </strong><span class="packing-meta-value">{{$invoice->vehicleno}}</span></p>
              <p class="packing-meta-line"><strong>Total Box: </strong><span class="packing-meta-value">{{$invoice->totalbox}}</span></p>
              <p class="packing-meta-line"><strong>Kind of Pkgs: </strong><span class="packing-meta-value">{{$invoice->pkgs}}</span></p>
              @endif
            </div>
        </div>
    </div>

    <!-- Consignee second row -->
    <hr><div class="row">
        <div class="col-6">
              @if(isset($invoice))
                <address>
                <h3 style="text-decoration: underline;">CONSIGNEE</h3>
                <p>{{$invoice->consignee->c_name}}</p>
                <p>{{$invoice->consignee->address1}},</p>
                <p>{{$invoice->consignee->address2}},</p>
                <p>{{$invoice->consignee->city}} - {{$invoice->consignee->postcode}}</p>
                <p>State Name: {{$invoice->consignee->state}}</p>
              </address>
              @endif
        </div>
        <div class="col-6">
            <div class="pull-right" id="supplier">
              @if(isset($invoice))
                <address>
                <h3 style="text-decoration: underline; font-size: 18px;">BUYER</h3>
                <p>{{$invoice->buyer->c_name}}</p>
                <p>{{$invoice->buyer->address1}},</p>
                <p>{{$invoice->buyer->address2}},</p>
                <p>{{$invoice->buyer->city}} - {{$invoice->buyer->postcode}}</p>
                <p>State Name: {{$invoice->buyer->state}}</p>
              </address>
              @endif
            </div>
        </div>
    </div>

    <!-- Certificate third row -->
    <hr><div class="row box-space">
       @if(isset($certificate))
          <div class="col-2">
            <img style="width: 90px; height: auto;" src="{{ asset('uploads/vriksh-logo.png') }}" alt="Vriksh Logo" />
          </div>
          <div class="col-4">
              <address>
                <p style="font-weight: 400;">{{$certificate->description}}</p>
              </address>
          </div>
          <div class="col-6">
            <div class="pull-right" id="supplier">
              <h3 style="text-decoration: underline;">PLACE OF STUFFING</h3>
              <p>"GLOBAL FACTORY"</p>
              <p>Plot #1216/2, Mahapura Road,</p>
              <p>Jaipur-Mumbai Highway</p>
              <p>Bhankrota, Jaipur</p>
            </div>  
          </div>  
        @endif
    </div>

    <!-- Table Start for Terms of Delivery -->
    <hr>
    <h3 style="text-decoration: underline;">Terms of Delivery</h3>
    <table class="table table-bordered" width="100%" cellspacing="0">
      <tbody>
        <tr>
          <td>
            <strong>FOB:</strong> {{$invoice->fob}}
          </td>
          <td>
            <strong>Payment Term:</strong> {{$invoice->payterms}}
          </td>
          <td>
            <strong>Shipment:</strong> {{$invoice->shipmentby}}
          </td>
        </tr>
        <tr>
          <td>
            <strong>Pre-Carriage by:</strong> {{$invoice->carriage}}
          </td>
          <td>
            <strong>Place of receipt by Precarrier:</strong> {{$invoice->receipt}}
          </td>
          <td>
            <strong>Shipment Under:</strong> {{$invoice->shipment}}
          </td>
        </tr>
        <tr>
          <td>
            <strong>Port of Loading:</strong> {{$invoice->postloading}}
          </td>
          <td>
            <strong>Port of Discharge:</strong> {{$invoice->discharge}}
          </td>
          <td>
            <strong>Final Destination:</strong> {{$invoice->destination}}
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Product details Table of First page-->
    <div class="row">
        <div class="col">
          <table class="table table-striped table-bordered" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>Description of Goods</th>
                <th>Total QTY</th>
                <th>Total Box</th>
                <th>Total Net Wt. (kg)</th>
                <th>Total Gross Wt. (kg)</th>
              </tr>
            </thead>
             
            <tbody>
              <tr>
                <td style="max-width: 500px;">{{$invoice->desgoods}}</td>
                <td>{{$invoice->totalquantity}}</td>
                <td>{{$invoice->totalbox}}</td>
                <td>{{$invoice->totalwt}}</td>
                <td>{{$invoice->totalgrosswt}}</td>
              </tr>
            </tbody>
          </table>
        </div>  
    </div>

    <!-- Footer Declaration fourth row -->
    <div class="row">
        <div class="col-6">
          @if(isset($companyDetails))
          @if($companyDetails->show_gsp == 1)
          <p>REX Registration # {{$companyDetails->gsp}}</p>
          @endif
          @endif
        </div>
    </div><hr>
    @if($showUkWastePackaging)
    <div class="row">
      As a part of UK's Waste Packaging regulations the total weight of packaging material against this shipment is (Gross weight - Net weight) =   <?php echo $invoice->totalgrosswt - $invoice->totalwt ?> Kg
    </div><hr>
    @endif
    <p style="text-align: justify; font-size: 12px; margin-right: 60px;margin-bottom: 1rem;">{{$invoice->additional_info}}</p>
    <!-- Declaration $ signature -->
    <div class="row packing-page1-decl" id="box-space"  style="margin-bottom: 1rem;">
      <div class="col-8">
        <h3>DECLARATION</h3>
        <p style="text-align: justify; font-size: 12px; margin-right: 60px;">{{$invoice->declaration}}</p>
      </div>
      <div class="col-4">
        <h3 class="sig-name">For Global Vision Direct (P) Ltd</h3>
        <div class="invoice-sig-gap" style="padding-top: 45px;">___________________________________</div>
        <div><p>Authorised Signatory</p></div>
      </div>
    </div>
  </div>
  </div>


  <!-- page break -->
  <div class="page-break"></div>


  <!-- Product pages: compact float header in thead repeats in Chrome -->
  <div class="container packing-sheet-page2">

    <div class="row box-space">
        <div class="col">
            <table class="table table-bordered packing-sheet-products-table" width="100%" cellspacing="0">
              <thead>
                <tr class="invoice-export-title-row">
                  <th colspan="9">
                    <div class="invoice-export-repeat-title">
                      <p class="h-title">Packing Sheet</p>
                    </div>
                  </th>
                </tr>
                {{-- colspan 4 = #..HSN, colspan 5 = QTY..Gross — divider lines up with QTY start --}}
                <tr class="invoice-export-repeat-header">
                  <th colspan="4" class="packing-meta-left-cell">
                    <div class="invoice-export-repeat-meta">
                      <div class="meta-left" style="display:block;width:100%;">
                        <div class="meta-pad meta-left-pad">
                        @if(isset($companyDetails))
                          <img src="{{ url('uploads/' . $companyDetails->logoUrl) }}" alt="Logo Image" width="72" height="auto">
                          <p style="font-weight:700!important;">{{$companyDetails->c_name ?? ''}}</p>
                          <p style="font-weight:700!important;">{{$companyDetails->address1}}</p>
                          <p style="font-weight:700!important;">{{$companyDetails->address2}}, {{$companyDetails->city}} - {{$companyDetails->postcode}}, India</p>
                          <p style="font-weight:700!important;">Tel # {{$companyDetails->phone1}} / {{$companyDetails->phone2}}</p>
                          <p style="font-weight:700!important;">E-mail: {{$companyDetails->email}}</p>
                        @endif
                        </div>
                      </div>
                    </div>
                  </th>
                  <th colspan="5" class="packing-meta-right-cell">
                    <div class="invoice-export-repeat-meta">
                      <div class="meta-right" style="display:block;width:100%;">
                        <div class="meta-pad">
                        @if(isset($invoice))
                        <p class="meta-inv-no" style="font-weight:700!important;font-size:1.2rem!important;margin:0 0 2px!important;">Invoice No. {{$invoice->invoiceno}}</p>
                        <p class="meta-inv-date" style="font-weight:700!important;font-size:13px!important;margin:0 0 0.85rem!important;">Invoice Date: {{date('d-M-Y',strtotime($invoice->date))}}</p>
                        <p><strong>Buyer Order No: </strong><span class="packing-meta-value">{{$invoice->buyerorderno}}</span></p>
                        <p><strong>Container No: </strong><span class="packing-meta-value">{{$invoice->containerno}}</span></p>
                        <p><strong>Vehicle No: </strong><span class="packing-meta-value">{{$invoice->vehicleno}}</span></p>
                        @endif
                        </div>
                      </div>
                    </div>
                  </th>
                </tr>
                <tr class="invoice-export-colhead">
                  <th>#</th>
                  <th>Product Description</th>
                  <th>EAN</th>
                  <th>HSN</th>
                  <th>QTY</th>
                  <th style="min-width: 70px;">Box</th>
                  <th>Pc/Box</th>
                  <th>Net Wt. (kg)</th>
                  <th>Gross Wt. (kg)</th>
                </tr>
              </thead>
      
              <tbody>
                @if(isset($invoiceTable)) @foreach($invoiceTable as $key => $invoiceTable)
                <tr>
                  <td>{{++$key}}</td>
                  <td>{{$invoiceTable->product->code}} - {{$invoiceTable->product->name}}<br>
                    <span style="font-style: italic; white-space: pre-line;">{{$invoiceTable->descriptionBox}}</span>
                  </td>
                  <td>{{$invoiceTable->product->EAN}}</td>
                  <td>{{$invoiceTable->product->HSN}}</td>
                  <td>{{$invoiceTable->quantity}}</td>
                  @if($invoiceTable->box == $invoiceTable->endbox)
                  <td>{{$invoiceTable->box}}</td>
                  @else
                  <td>{{$invoiceTable->box}}-{{$invoiceTable->endbox}}</td>
                  @endif
                  <td>{{$invoiceTable->qtybox}} Pc/Box</td>
                  <td>{{$invoiceTable->subtotalnetwt}}</td>
                  <td>{{$invoiceTable->subtotalgrosswt}}</td>
                </tr>
                @endforeach @endif
                <tr>
                  <td colspan="4"></td>
                  <td><strong>{{$invoice->totalquantity}}</strong></td>
                  <td><strong>{{$invoice->totalbox}}</strong></td>
                  <td></td>
                  <td><strong>{{$invoice->totalwt}}</strong></td>
                  <td><strong>{{$invoice->totalgrosswt}}</strong></td>
                </tr>
              </tbody>
              <tfoot>
                  <tr class="border-less-row">
                      <td colspan="9">
                        <div class="mt-2">
                          @if(isset($companyDetails))
                          @if($companyDetails->show_gsp == 1)
                          <p>REX Registration # {{$companyDetails->gsp}}</p>
                          @endif
                          @endif
                        </div><hr>
                      </td>
                  </tr>
                  @if($showUkWastePackaging)
                  <tr class="border-less-row">
                    <td colspan="9">
                      <div class="row">
                        As a part of UK's Waste Packaging regulations the total weight of packaging material against this shipment is (Gross weight - Net weight) =   <?php echo $invoice->totalgrosswt - $invoice->totalwt ?> Kg
                      </div><hr>
                    </td>
                  </tr>
                  @endif
                  <tr class="packing-footer-split">
                      <td colspan="4" class="packing-footer-left">
                          <div>
                          <h3>DECLARATION</h3>
                          <p style="text-align: justify; font-size: 12px; margin-right: 20px;">We declare that this invoice shows the actual price of the goods and that all particulars are true and correct. The FOB value declared is fair and same is equivalent to PMV of the goods. We declare that goods are non antique and are not art treasure. We further declare that neither red sanders wood nor any prohibited wood has been used in the manufacturing of above items.
                          </p>
                          </div>
                      </td>
                      <td colspan="5" class="packing-footer-right">
                         <div>
                          <h3 class="sig-name">For Global Vision Direct (P) Ltd</h3>
                          <div class="invoice-sig-gap" style="padding-top: 45px;">___________________________________</div>
                          <div><p>Authorised Signatory</p></div>
                        </div>
                      </td>
                  </tr>
              </tfoot>
            </table>
        </div>  
    </div>

  </div>


<!-- Script start for Print Invoice-->
@if(isset($print) && $print==1)
    <script type="text/javascript">
      document.ready = window.print();
    </script>
@endif
<!-- Script end -->
</body>
