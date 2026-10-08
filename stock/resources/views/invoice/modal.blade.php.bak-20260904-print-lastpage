@extends('layouts.modal')

<body>
<style>
@if(strlen($invoice->declaration) < 200)
  p{
    font-size: 15px !important;
  }
@else
p{
    font-size: 13px !important;
  }
@endif
</style>
@include('layouts.partials.modal-print-styles', ['printFontSize' => 14])
<style>
#buyer {
  text-align: right;
}

/* Export page-1 invoice meta — match print sample: Label: value inline, left aligned */
.invoice-meta-block {
  text-align: left !important;
  padding-left: 0 !important;
  padding-right: 4px;
  margin-left: -1.6rem;
  width: calc(100% + 1.6rem);
  max-width: none;
  box-sizing: border-box;
}
.invoice-meta-block .invoice-meta-heading h1 {
  text-align: left !important;
  margin: 0 0 0.15rem !important;
  line-height: 1.2 !important;
  font-size: 1.45rem !important;
  font-weight: 700 !important;
}
.invoice-meta-block .invoice-meta-heading h1 strong,
.invoice-meta-block .invoice-meta-heading h1 .packing-meta-value {
  font-size: 1.45rem !important;
  font-weight: 700 !important;
}
.invoice-meta-block .invoice-meta-heading h3 {
  text-align: left !important;
  margin: 0 0 0.15rem !important;
  line-height: 1.2 !important;
  font-size: 14px !important;
  font-weight: 700 !important;
}
.invoice-meta-block .invoice-meta-heading h3 strong,
.invoice-meta-block .invoice-meta-heading h3 .packing-meta-value {
  font-size: 14px !important;
  font-weight: 700 !important;
}
.invoice-meta-block .invoice-meta-heading {
  margin-bottom: 1.1rem !important;
}
.invoice-meta-block .invoice-meta-line {
  margin: 0 0 0.12rem !important;
  padding: 0 8px 0 0 !important;
  text-align: left !important;
  line-height: 1.3 !important;
  white-space: nowrap !important;
  font-size: 13px !important;
  font-weight: 400 !important;
}
.invoice-meta-block .invoice-meta-line strong {
  font-weight: 700 !important;
}
.invoice-meta-block .invoice-meta-line .packing-meta-value {
  font-weight: 400 !important;
}
/* Solid divider — Chrome print often hides BS5 <hr> (opacity:.25) */
.invoice-print-rule {
  display: block !important;
  width: 100% !important;
  height: 0 !important;
  margin: 0.45rem 0 !important;
  padding: 0 !important;
  border: 0 !important;
  border-top: 1px solid #000 !important;
  opacity: 1 !important;
  background: none !important;
}
.vriksh-cert-text {
  text-align: left !important;
  margin: 0 !important;
  padding-left: 0 !important;
  margin-left: -0.35rem !important;
  font-weight: 400 !important;
  line-height: 1.25 !important;
}

/* Screen + print: float header (Chrome repeats this; Bootstrap grid in thead does not) */
.invoice-export-repeat-title {
  text-align: center;
  margin: 0 0 10px;
}
.invoice-export-repeat-title .h-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
}
.invoice-export-repeat-title .h-sub {
  font-size: 15px;
  font-weight: 700;
  margin: 2px 0 0;
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
/* Uniform grid: internal 1px #000; outer frame 3px #000 */
.invoice-export-products-table {
  border-collapse: collapse !important;
}
.invoice-export-products-table.table-bordered th,
.invoice-export-products-table.table-bordered td,
.invoice-export-products-table thead th,
.invoice-export-products-table tbody td,
.invoice-export-products-table tfoot td {
  border: 1px solid #000 !important;
  border-color: #000 !important;
}
.invoice-export-products-table th.amt-col-sep,
.invoice-export-products-table td.amt-col-sep {
  border-right: 1px solid #000 !important;
}
/* Page 2+: title outside bordered box; box starts at company/meta */
.container.invoice-export-page2 {
  border: none !important;
}
.invoice-export-products-table thead tr.invoice-export-title-row > th {
  border: none !important;
  background: #fff !important;
  padding: 0 0 14px !important;
  font-weight: normal !important;
}
.invoice-export-products-table thead tr.invoice-export-repeat-header > th.packing-meta-left-cell {
  border-top: 3px solid #000 !important;
  border-left: 3px solid #000 !important;
  border-bottom: 1px solid #000 !important;
  border-right: 1px solid #000 !important;
  background: #fff !important;
  padding: 0 !important;
  font-weight: normal !important;
  text-align: left !important;
  vertical-align: top !important;
}
.invoice-export-products-table thead tr.invoice-export-repeat-header > th.packing-meta-right-cell {
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
.invoice-export-products-table tfoot tr.packing-footer-split > td.packing-footer-left {
  border-top: 3px solid #000 !important;
  border-left: 3px solid #000 !important;
  border-bottom: 3px solid #000 !important;
  border-right: 1px solid #000 !important;
  padding: 8px 10px !important;
  vertical-align: top !important;
  background: #fff !important;
}
.invoice-export-products-table tfoot tr.packing-footer-split > td.packing-footer-right {
  border-top: 3px solid #000 !important;
  border-right: 3px solid #000 !important;
  border-bottom: 3px solid #000 !important;
  border-left: none !important;
  padding: 8px 10px !important;
  vertical-align: top !important;
  background: #fff !important;
}
.invoice-export-products-table thead tr.invoice-export-colhead > th {
  border: 1px solid #000 !important;
  border-top: 1px solid #000 !important;
  border-bottom: 1px solid #000 !important;
  background: #e9ecef !important;
}
.invoice-export-products-table thead tr.invoice-export-colhead > th:first-child,
.invoice-export-products-table tbody td:first-child,
.invoice-export-products-table tfoot td:first-child {
  border-left: 3px solid #000 !important;
}
.invoice-export-products-table thead tr.invoice-export-colhead > th:last-child,
.invoice-export-products-table tbody td:last-child,
.invoice-export-products-table tfoot td:last-child {
  border-right: 3px solid #000 !important;
}
.invoice-export-products-table tfoot tr:last-child td {
  border-bottom: 3px solid #000 !important;
}
.invoice-export-repeat-meta .packing-meta-value {
  font-weight: 400;
}
.invoice-export-repeat-meta .meta-inv-no .packing-meta-value,
.invoice-export-repeat-meta .meta-inv-date .packing-meta-value {
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

  /* Page 1: reclaim whitespace only — no break-inside:avoid (causes blank pages in Chrome) */
  .invoice-export-page1-wrap > header h1 {
    margin: 0 0 0.1rem !important;
    line-height: 1.15 !important;
  }
  .invoice-export-page1-wrap > header h3 {
    margin: 0 0 0.2rem !important;
    line-height: 1.2 !important;
  }
  .invoice-export-page1 {
    margin-bottom: 0 !important;
  }
  .invoice-export-page1 .row.box-space {
    margin-top: 0.25rem !important;
  }
  .invoice-export-page1 .invoice-irn-block {
    margin-top: 0.15rem !important;
  }
  /* Force visible separators in print (override BS5 hr opacity) */
  .invoice-export-page1 hr,
  .invoice-export-page1 .invoice-print-rule {
    margin-top: 0.4rem !important;
    margin-bottom: 0.4rem !important;
    border: 0 !important;
    border-top: 1px solid #000 !important;
    height: 0 !important;
    background: none !important;
    opacity: 1 !important;
    color: #000 !important;
    display: block !important;
    width: 100% !important;
  }
  /* Stop Buyer Ref / BUYER address clipping on the right border */
  .invoice-export-page1 {
    overflow: visible !important;
    padding-right: 6px !important;
    padding-left: 6px !important;
  }
  .invoice-export-page1 > .row > .col-6 {
    padding-right: 14px !important;
    padding-left: 8px !important;
    max-width: 50% !important;
    overflow: visible !important;
  }
  .invoice-export-page1 #supplier {
    text-align: right !important;
    padding-right: 6px !important;
    box-sizing: border-box !important;
    max-width: 100% !important;
  }
  .invoice-export-page1 #supplier p,
  .invoice-export-page1 #supplier h3 {
    padding-right: 2px !important;
  }
  .invoice-export-page1 .mt-4 {
    margin-top: 0.35rem !important;
  }
  .invoice-export-page1 .mt-2 {
    margin-top: 0.2rem !important;
  }
  .invoice-export-page1 .invoice-page1-decl {
    margin-bottom: 0 !important;
  }
  .invoice-export-page1 .invoice-sig-gap {
    padding-top: 22px !important;
  }
  .invoice-export-page1 p {
    line-height: 1.2 !important;
  }

  /* Shift whole invoice meta block left so Buyer Ref stays one line, uncut */
  .invoice-export-page1 > .row.box-space > .col-6:last-child {
    padding-left: 0 !important;
    padding-right: 18px !important;
    position: relative !important;
    left: -12px !important;
    max-width: calc(50% + 12px) !important;
  }
  .invoice-export-page1 #buyer.invoice-meta-block,
  .invoice-export-page1 .invoice-meta-block {
    float: none !important;
    text-align: left !important;
    margin-left: -1.6rem !important;
    width: calc(100% + 1.6rem) !important;
    max-width: none !important;
    padding-right: 4px !important;
    box-sizing: border-box !important;
  }
  .invoice-export-page1 .invoice-meta-heading {
    margin-bottom: 1rem !important;
  }
  .invoice-export-page1 .invoice-meta-heading h1 {
    font-size: 1.35rem !important;
    font-weight: 700 !important;
  }
  .invoice-export-page1 .invoice-meta-heading h1 strong,
  .invoice-export-page1 .invoice-meta-heading h1 .packing-meta-value {
    font-size: 1.35rem !important;
    font-weight: 700 !important;
  }
  .invoice-export-page1 .invoice-meta-heading h3 {
    font-size: 13px !important;
    font-weight: 700 !important;
  }
  .invoice-export-page1 .invoice-meta-heading h3 strong,
  .invoice-export-page1 .invoice-meta-heading h3 .packing-meta-value {
    font-size: 13px !important;
    font-weight: 700 !important;
  }
  .invoice-export-page1 .invoice-meta-line {
    white-space: nowrap !important;
    text-align: left !important;
    font-size: 12px !important;
    font-weight: 400 !important;
    overflow: visible !important;
  }
  .invoice-export-page1 .invoice-meta-line strong {
    font-weight: 700 !important;
  }
  .invoice-export-page1 .invoice-meta-line .packing-meta-value {
    font-weight: 400 !important;
  }
  .invoice-export-page1 .vriksh-cert-text {
    margin-left: -0.5rem !important;
    font-size: 12px !important;
  }

  /* Page 1 overflow only when e-Invoice QR is present — modest shrink */
  .invoice-export-page1-wrap.has-einvoice-qr > header h1 {
    font-size: 1.4rem !important;
  }
  .invoice-export-page1-wrap.has-einvoice-qr > header h3 {
    font-size: 13px !important;
    line-height: 1.15 !important;
  }
  .invoice-export-page1.has-einvoice-qr .invoice-einvoice-row {
    margin-bottom: 0 !important;
  }
  .invoice-export-page1.has-einvoice-qr .invoice-irn-block {
    margin-top: 0 !important;
  }
  .invoice-export-page1.has-einvoice-qr .invoice-irn-block p {
    font-size: 12px !important;
    line-height: 1.15 !important;
  }
  .invoice-export-page1.has-einvoice-qr .invoice-qr-img {
    width: 95px !important;
    height: auto !important;
  }
  .invoice-export-page1.has-einvoice-qr p {
    font-size: 13px !important;
  }
  .invoice-export-page1.has-einvoice-qr h1 {
    font-size: 1.35rem !important;
  }
  .invoice-export-page1.has-einvoice-qr h3 {
    font-size: 14px !important;
  }
  .invoice-export-page1.has-einvoice-qr .table th,
  .invoice-export-page1.has-einvoice-qr .table td {
    font-size: 12px !important;
    padding: 1px 2px !important;
  }
  .invoice-export-page1.has-einvoice-qr .invoice-sig-gap {
    padding-top: 16px !important;
  }
  .invoice-export-page1.has-einvoice-qr .invoice-page1-decl p {
    font-size: 11px !important;
  }

  /* Chrome-safe repeating header/footer (simple markup only — no Bootstrap grid in thead) */
  .invoice-export-products-table thead {
    display: table-header-group !important;
  }
  .invoice-export-products-table tfoot {
    display: table-footer-group !important;
  }
  /* Slim repeating header so Chrome can print it on every continuation page */
  .invoice-export-products-table thead img {
    width: 56px !important;
    height: auto !important;
    margin-bottom: 1px !important;
  }
  .invoice-export-products-table thead .invoice-export-repeat-meta {
    height: auto !important;
  }
  .invoice-export-products-table thead .invoice-export-repeat-meta .meta-pad {
    padding: 8px 10px 8px !important;
  }
  .invoice-export-products-table thead .invoice-export-repeat-meta p {
    font-size: 10.5px !important;
    line-height: 1.15 !important;
  }
  .invoice-export-products-table thead .invoice-export-repeat-meta .meta-inv-no,
  .invoice-export-products-table thead .invoice-export-repeat-meta .meta-inv-no strong {
    font-size: 1.05rem !important;
  }
  .invoice-export-products-table thead .invoice-export-repeat-meta .meta-inv-date,
  .invoice-export-products-table thead .invoice-export-repeat-meta .meta-inv-date strong {
    font-size: 12px !important;
    margin: 0 0 0.35rem !important;
  }
  .invoice-export-products-table tfoot .invoice-sig-gap {
    padding-top: 22px !important;
  }
  .invoice-export-products-table tfoot h3 {
    font-size: 13px !important;
    margin: 0 0 4px !important;
  }
  .invoice-export-products-table tfoot p {
    font-size: 10.5px !important;
    line-height: 1.2 !important;
  }
  .container.invoice-export-page2 {
    border: none !important;
  }
  .invoice-export-products-table thead tr.invoice-export-title-row > th {
    border: none !important;
    background: #fff !important;
    padding: 0 0 6px !important;
    font-weight: normal !important;
    text-align: center !important;
  }
  .invoice-export-products-table {
    border-collapse: collapse !important;
  }
  .invoice-export-products-table.table-bordered th,
  .invoice-export-products-table.table-bordered td,
  .invoice-export-products-table thead th,
  .invoice-export-products-table tbody td,
  .invoice-export-products-table tfoot td {
    border: 1px solid #000 !important;
    border-color: #000 !important;
  }
  .invoice-export-products-table thead tr.invoice-export-repeat-header > th.packing-meta-left-cell {
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
  .invoice-export-products-table thead tr.invoice-export-repeat-header > th.packing-meta-right-cell {
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
  .invoice-export-products-table tfoot tr.packing-footer-split > td.packing-footer-left {
    border-top: 3px solid #000 !important;
    border-left: 3px solid #000 !important;
    border-bottom: 3px solid #000 !important;
    border-right: 1px solid #000 !important;
    padding: 8px 10px !important;
    vertical-align: top !important;
    background: #fff !important;
  }
  .invoice-export-products-table tfoot tr.packing-footer-split > td.packing-footer-right {
    border-top: 3px solid #000 !important;
    border-right: 3px solid #000 !important;
    border-bottom: 3px solid #000 !important;
    border-left: none !important;
    padding: 8px 10px !important;
    vertical-align: top !important;
    background: #fff !important;
  }
  .invoice-export-products-table thead tr.invoice-export-colhead > th {
    border: 1px solid #000 !important;
    border-top: 1px solid #000 !important;
    border-bottom: 1px solid #000 !important;
    background: #e9ecef !important;
  }
  .invoice-export-products-table thead tr.invoice-export-colhead > th:first-child,
  .invoice-export-products-table tbody td:first-child,
  .invoice-export-products-table tfoot td:first-child {
    border-left: 3px solid #000 !important;
  }
  .invoice-export-products-table thead tr.invoice-export-colhead > th:last-child,
  .invoice-export-products-table tbody td:last-child,
  .invoice-export-products-table tfoot td:last-child {
    border-right: 3px solid #000 !important;
  }
  .invoice-export-products-table tfoot tr:last-child td {
    border-bottom: 3px solid #000 !important;
  }
  .invoice-export-repeat-title {
    text-align: center;
    margin: 0 0 4px !important;
  }
  .invoice-export-repeat-title .h-title {
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 !important;
    line-height: 1.1 !important;
  }
  .invoice-export-repeat-title .h-sub {
    font-size: 12px;
    font-weight: 700;
    margin: 2px 0 0 !important;
    line-height: 1.15 !important;
  }
  .invoice-export-repeat-meta {
    display: block !important;
    width: 100% !important;
    height: auto !important;
    table-layout: auto !important;
    border-collapse: collapse !important;
    border-spacing: 0 !important;
  }
  .invoice-export-repeat-meta .meta-left,
  .invoice-export-repeat-meta .meta-right {
    display: block !important;
    vertical-align: top !important;
    width: 100% !important;
    float: none !important;
    padding: 0 !important;
  }
  .invoice-export-repeat-meta .meta-pad {
    padding: 8px 10px 8px !important;
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
  .invoice-export-products-table thead tr.invoice-export-repeat-header > th {
    padding: 0 !important;
  }
  .invoice-export-repeat-meta img {
    width: 72px !important;
    height: auto !important;
    margin-bottom: 2px;
  }
  .invoice-export-repeat-meta p {
    margin: 0 !important;
    line-height: 1.2 !important;
    font-size: 11.5px !important;
    text-align: left !important;
    white-space: nowrap !important;
    font-weight: 400 !important;
  }
  .invoice-export-repeat-meta strong {
    font-weight: 700 !important;
  }
  .invoice-export-repeat-meta .packing-meta-value {
    font-weight: 400 !important;
    font-size: 11px !important;
  }
  .invoice-export-repeat-meta .meta-inv-no {
    font-size: 1.2rem !important;
    font-weight: 700 !important;
    margin: 0 0 2px !important;
    line-height: 1.15 !important;
  }
  .invoice-export-repeat-meta .meta-inv-no strong,
  .invoice-export-repeat-meta .meta-inv-no .packing-meta-value {
    font-weight: 700 !important;
    font-size: 1.2rem !important;
  }
  .invoice-export-repeat-meta .meta-inv-date {
    font-size: 13px !important;
    font-weight: 700 !important;
    margin: 0 0 0.85rem !important;
  }
  .invoice-export-repeat-meta .meta-inv-date strong,
  .invoice-export-repeat-meta .meta-inv-date .packing-meta-value {
    font-weight: 700 !important;
    font-size: 13px !important;
  }
  .invoice-export-products-table th.amt-col-sep,
  .invoice-export-products-table td.amt-col-sep {
    border-right: 1px solid #000 !important;
  }
}

/*
  Firefox-only print fixes (does not apply in Chrome).
  - Slim thead under Firefox ~250px repeat limit
  - Use border-collapse:separate so multi-line product rows keep a full grid
    (Chrome keeps collapse — separate breaks Chrome thead repeat)
*/
@supports (-moz-appearance: none) {
  @media print {
    .invoice-export-products-table,
    .invoice-export-products-table.table,
    .invoice-export-products-table.table-bordered {
      border-collapse: separate !important;
      border-spacing: 0 !important;
      width: 100% !important;
    }
    .invoice-export-products-table thead {
      display: table-header-group !important;
    }
    .invoice-export-products-table tbody {
      display: table-row-group !important;
    }
    .invoice-export-products-table tfoot {
      display: table-footer-group !important;
    }
    .invoice-export-products-table thead tr.invoice-export-title-row > th {
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
    .invoice-export-repeat-title .h-sub {
      font-size: 11px !important;
      line-height: 1.1 !important;
      margin: 0 !important;
    }
    .invoice-export-products-table thead img {
      width: 40px !important;
      height: auto !important;
      margin-bottom: 0 !important;
    }
    .invoice-export-products-table thead .invoice-export-repeat-meta .meta-pad {
      padding: 4px 8px !important;
    }
    .invoice-export-products-table thead .invoice-export-repeat-meta p {
      font-size: 9.5px !important;
      line-height: 1.1 !important;
    }
    .invoice-export-products-table thead .invoice-export-repeat-meta .meta-inv-no,
    .invoice-export-products-table thead .invoice-export-repeat-meta .meta-inv-no strong {
      font-size: 0.95rem !important;
      margin: 0 !important;
    }
    .invoice-export-products-table thead .invoice-export-repeat-meta .meta-inv-date,
    .invoice-export-products-table thead .invoice-export-repeat-meta .meta-inv-date strong {
      font-size: 11px !important;
      margin: 0 0 0.2rem !important;
    }

    .invoice-export-products-table thead tr.invoice-export-colhead > th,
    .invoice-export-products-table tbody td {
      border: none !important;
      border-top: 1px solid #000 !important;
      border-left: 1px solid #000 !important;
      box-shadow: none !important;
      background-clip: padding-box !important;
      -moz-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    .invoice-export-products-table thead tr.invoice-export-colhead > th {
      font-size: 9px !important;
      padding: 2px 2px !important;
      line-height: 1.1 !important;
      background: #e9ecef !important;
      font-weight: 700 !important;
    }
    .invoice-export-products-table thead tr.invoice-export-colhead > th:last-child,
    .invoice-export-products-table tbody td:last-child {
      border-right: 1px solid #000 !important;
    }
    .invoice-export-products-table tbody tr:last-child td {
      border-bottom: 1px solid #000 !important;
    }
    .invoice-export-products-table thead tr.invoice-export-colhead > th:first-child,
    .invoice-export-products-table tbody td:first-child {
      border-left: 3px solid #000 !important;
    }
    .invoice-export-products-table thead tr.invoice-export-colhead > th:last-child,
    .invoice-export-products-table tbody td:last-child {
      border-right: 3px solid #000 !important;
    }
    .invoice-export-products-table thead tr.invoice-export-repeat-header > th.packing-meta-left-cell {
      border-top: 3px solid #000 !important;
      border-left: 3px solid #000 !important;
      border-right: 1px solid #000 !important;
      border-bottom: 1px solid #000 !important;
      background: #fff !important;
    }
    .invoice-export-products-table thead tr.invoice-export-repeat-header > th.packing-meta-right-cell {
      border-top: 3px solid #000 !important;
      border-right: 3px solid #000 !important;
      border-left: none !important;
      border-bottom: 1px solid #000 !important;
      background: #fff !important;
    }
    .invoice-export-products-table tfoot tr.packing-footer-split > td.packing-footer-left {
      border-top: 3px solid #000 !important;
      border-left: 3px solid #000 !important;
      border-right: 1px solid #000 !important;
      border-bottom: 3px solid #000 !important;
      background: #fff !important;
    }
    .invoice-export-products-table tfoot tr.packing-footer-split > td.packing-footer-right {
      border-top: 3px solid #000 !important;
      border-right: 3px solid #000 !important;
      border-left: none !important;
      border-bottom: 3px solid #000 !important;
      background: #fff !important;
    }
    .invoice-export-products-table th.amt-col-sep,
    .invoice-export-products-table td.amt-col-sep {
      border-right: 1px solid #000 !important;
    }
    .invoice-export-products-table tfoot .invoice-sig-gap {
      padding-top: 14px !important;
    }
    .invoice-export-products-table tfoot h3 {
      font-size: 11px !important;
      margin: 0 0 2px !important;
    }
    .invoice-export-products-table tfoot p {
      font-size: 9.5px !important;
      line-height: 1.15 !important;
    }

    .invoice-export-page1 > .row.box-space > .col-6:last-child {
      position: static !important;
      left: auto !important;
      margin-left: -14px !important;
      max-width: calc(50% + 14px) !important;
      padding-left: 0 !important;
      padding-right: 22px !important;
    }
    .invoice-export-page1 #buyer.invoice-meta-block,
    .invoice-export-page1 .invoice-meta-block {
      margin-left: -2rem !important;
      width: calc(100% + 2rem) !important;
      padding-right: 2px !important;
      overflow: visible !important;
    }
    .invoice-export-page1 .invoice-meta-line {
      font-size: 11.5px !important;
      white-space: nowrap !important;
      overflow: visible !important;
    }
  }
}
</style>
  <!-- Start Invoice Export Modal-->
@php
$showBatch = $showBatch ?? false;
$packingBatchByProductId = $packingBatchByProductId ?? [];
$currency = 'GBP';
if($invoice->currency == "$"){
	$currency = 'USD';
}
if($invoice->currency == "₹"){
	$currency = 'INR';
}
if($invoice->currency == "€"){
  $currency = 'EURO';
}
if($invoice->currency == "C$"){
    $currency = 'Canadian Doller';
    
    }if($invoice->currency == "A$"){
    $currency = 'Australian Doller';
 }


@endphp
  @if($invoice->invoicetype == '0')
  @php $hasEinvoiceQr = isset($invoice->einvoice_qr) && !empty($invoice->einvoice_qr); @endphp
  <div class="invoice-export-page1-wrap{{ $hasEinvoiceQr ? ' has-einvoice-qr' : '' }}">
  <header>
    <h1 class="text-center">Export Invoice</h1>
    @if($invoice->exportstatus == '0')
    <h3 class="text-center">Supply Meant for Export on payment of IGST</h3>
    @else
    <h3 class="text-center">Supply Meant for Export Under letter of Undertaking without payment of Integrated Tax (IGST)</h3>
    @endif
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

  <div class="container invoice-export-page1{{ $hasEinvoiceQr ? ' has-einvoice-qr' : '' }}">

    <!-- Header row -->
      <div class="row invoice-einvoice-row" id="clogo">
        <div class="col-8">
          <div class="invoice-irn-block" style="margin-top:40px;">
          @if(isset($invoice->irn) && !empty($invoice->irn))<p><strong>IRN: </strong>{{$invoice->irn}}</p>
              @endif

          @if(isset($invoice->ack_no) && !empty($invoice->ack_no))<p><strong>Ack No.: </strong>{{$invoice->ack_no}}</p>
              @endif

          @if(isset($invoice->ack_date) && !empty($invoice->ack_date))<p><strong>Ack Date: </strong>{{date('d-M-y',strtotime($invoice->ack_date))}}</p>
              @endif
          </div>
        </div>
        @if($hasEinvoiceQr)
          <div class="col-4">
            <div style="text-align:center;">e-Invoice<br>
            <img class="invoice-qr-img" src="{{url('uploads/einvoice/')}}/{{$invoice->einvoice_qr}}" style="width:130px;" />
            </div>
          </div>
        @endif
      </div>
      <div class="row box-space" id="clogo">
        <div class="col-6">
          @if(isset($companyDetails))
            <img width="250px" src="{{ url('uploads/' . $companyDetails->logoUrl) }}" alt="Logo Image" style="width:100px;" />
            <address class="mt-2">
              <p style="font-weight: 700;">{{$companyDetails->c_name}}</p>
              <p style="font-weight: 700;">{{$companyDetails->address1}}</p>
              <p style="font-weight: 700;">{{$companyDetails->address2}},<br> {{$companyDetails->city}} - {{$companyDetails->postcode}}, India</p>
              <p>Website: {{$companyDetails->website}}</p>
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
            <div id="buyer" class="invoice-meta-block">
              @if(isset($invoice))
              <div class="invoice-meta-heading">
                <h1><strong>Invoice No.</strong> <span class="packing-meta-value">{{$invoice->invoiceno}}</span></h1>
                <h3><strong>Invoice Date:</strong> <span class="packing-meta-value">{{date('d-M-Y',strtotime($invoice->date))}}</span></h3>
              </div>
              <p class="invoice-meta-line"><strong>Buyer Ref. No: </strong><span class="packing-meta-value">{{$invoice->buyerorderno}}</span></p>
              <p class="invoice-meta-line"><strong>Container No: </strong><span class="packing-meta-value">{{$invoice->containerno}}</span></p>
              <p class="invoice-meta-line"><strong>Vehicle No: </strong><span class="packing-meta-value">{{$invoice->vehicleno}}</span></p>
              <p class="invoice-meta-line"><strong>Total Box: </strong><span class="packing-meta-value">{{$invoice->totalbox}}</span></p>
              <p class="invoice-meta-line"><strong>Kind of Pkgs: </strong><span class="packing-meta-value">{{$invoice->pkgs}}</span></p>
              @if(isset($invoice->ewaybillno) && $invoice->ewaybillno !== '')
              <p class="invoice-meta-line"><strong>E-Way Bill No: </strong><span class="packing-meta-value">{{$invoice->ewaybillno}}</span></p>
              @endif
              @if(isset($invoice->lr_rr_no) && $invoice->lr_rr_no !== '')
              <p class="invoice-meta-line"><strong>Bill of Lading/LR-RR No.: </strong><span class="packing-meta-value">{{$invoice->lr_rr_no}}</span></p>
              @endif
              @endif
            </div>
        </div>
    </div>

    <!-- Consignee second row -->
    <div class="invoice-print-rule" role="separator"></div>
    <div class="row box-space">
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
            <img style="width: 75px; height: auto;" src="{{ asset('uploads/vriksh-logo.png') }}" alt="Vriksh Logo" />
          </div>
          <div class="col-4">
              <address>
                <p class="vriksh-cert-text">{{$certificate->description}}</p>
              </address>
          </div>
          <div class="col-6">
            <div class="pull-right" id="supplier" >
              <h3 style="text-decoration: underline;">PLACE OF STUFFING</h3>
              <p>"GLOBAL FACTORY", Plot #1216/2, Mahapura Road,</p>
              <p>Jaipur-Mumbai Highway, Bhankrota, Jaipur</p>
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
            <strong>Price:</strong> {{$invoice->fob}}
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
    <div class="row box-space">
        <div class="col">
          <table class="table table-striped table-bordered" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>Description of Goods</th>
                <th>QTY</th>
                <th>Total Amount ({{$currency}}({{$invoice->currency}}))</th>
                <th>Total Amount (₹)</th>
                @if($invoice->buyer->state == 'Rajasthan')
                <th>GST Amount (₹)</th>
                @else
                <th>IGST Amount (₹)</th>
                @endif
              </tr>
            </thead>

            <tbody>
              <tr>
                <td style="width:400px; max-width: 400px;">{{$invoice->desgoods}}</td>
                <td>{{$invoice->totalquantity}}</td>
                <td>{{$invoice->totalamount - $invoice->shipping_charges}}</td>
                <td>{{$invoice->rateamount - ($invoice->conrate*$invoice->shipping_charges)}}<br><br>Conversion Rate:<br>1 {{$currency}}({{$invoice->currency}}) = ₹{{$invoice->conrate}}
                </td>
                <td>{{$invoice->totalgst}}</td>
              </tr>
            @if($invoice->shipping_charges != 0 && $invoice->shipping_charges != '')
              <tr>
                <td colspan="2" class="text-end">SAC Code 996521: Ocean Freight ({{$invoice->currency}})</td>
                <td>{{$invoice->shipping_charges}} <br> {{$invoice->additional_info}}</td>
                <td>{{$invoice->shipping_charges*$invoice->conrate}}</td>
                <td>Exemption</td>
              </tr>
			  <tr>
                <td colspan="2" class="text-end"><strong>Total Invoice Value({{$invoice->currency}})</strong></td>
                <td><strong>{{$invoice->totalamount}}</strong></td>
                <td>{{$invoice->totalamount*$invoice->conrate}}</td>
                <td>{{$invoice->totalgst}}</td>
              </tr>
            @endif
            @if($invoice->packing_charges != 0 && $invoice->packing_charges != '')
              <tr>
                <td colspan="2" class="text-end"><strong>Additional Packing Charges ({{$currency}}({{$invoice->currency}}))</strong></td>
                <td>{{$invoice->packing_charges}}</td>
                <td></td>
                <td></td>
              </tr>
              @endif
            @if($invoice->discount != 0 && $invoice->discount != '')
              <tr>
                <td colspan="2" class="text-end"><strong>Discount ({{$currency}}({{$invoice->currency}}))</strong></td>
                <td>{{$invoice->discount}}</td>
                <td></td>
                <td></td>
              </tr>
            @endif
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
    <p style="text-align: justify; font-size: 11px; margin-right: 60px;">{{$invoice->additional_info}}</p>
    <!-- Declaration $ signature -->
    <div class="row box-space invoice-page1-decl" style="margin-bottom: 1rem;">
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


  <!-- Product pages: title outside bordered box; meta+cols repeat in thead -->
  <div class="container invoice-export-page2">

    <div class="row box-space">
        <div class="col">
            <table class="table table-bordered invoice-export-products-table" width="100%" cellspacing="0">
              <thead>
                <tr class="invoice-export-title-row">
                  <th colspan="{{ $showBatch ? 12 : 11 }}">
                    <div class="invoice-export-repeat-title">
                      <p class="h-title">Export Invoice</p>
                      @if($invoice->exportstatus == '0')
                      <p class="h-sub">Export on payment of IGST</p>
                      @else
                      <p class="h-sub">Export under LUT</p>
                      @endif
                    </div>
                  </th>
                </tr>
                {{-- left through Rate; right from Amount — divider aligns with Amount column --}}
                <tr class="invoice-export-repeat-header">
                  <th colspan="{{ $showBatch ? 6 : 5 }}" class="packing-meta-left-cell">
                    <div class="invoice-export-repeat-meta">
                      <div class="meta-left" style="display:block;width:100%;">
                        <div class="meta-pad">
                        @if(isset($companyDetails))
                          <img src="{{ url('uploads/' . $companyDetails->logoUrl) }}" alt="Logo Image" width="72" height="auto">
                          <p style="font-weight: 700;">{{$companyDetails->c_name}}</p>
                          <p style="font-weight: 700;">{{$companyDetails->address1}}</p>
                          <p style="font-weight: 700;">{{$companyDetails->address2}}, {{$companyDetails->city}} - {{$companyDetails->postcode}}, India</p>
                          <p style="font-weight:700!important;">Tel # {{$companyDetails->phone1}} / {{$companyDetails->phone2}}</p>
                          <p style="font-weight:700!important;">E-mail: {{$companyDetails->email}}</p>
                        @endif
                        </div>
                      </div>
                    </div>
                  </th>
                  <th colspan="6" class="packing-meta-right-cell">
                    <div class="invoice-export-repeat-meta">
                      <div class="meta-right" style="display:block;width:100%;">
                        <div class="meta-pad">
                        @if(isset($invoice))
                        <p class="meta-inv-no" style="font-weight:700!important;">Invoice No. {{$invoice->invoiceno}}</p>
                        <p class="meta-inv-date" style="font-weight:700!important;">Invoice Date: {{date('d-M-Y',strtotime($invoice->date))}}</p>
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
                  @if($showBatch)
                  <th>Batch</th>
                  @endif
                  <!-- <th>EAN</th> -->
                  <th>HSN</th>
                  <th>QTY</th>
                  <th>Rate ({{$currency}}({{$invoice->currency}}))</th>
                  <th class="amt-col-sep">Amount ({{$currency}}({{$invoice->currency}}))</th>
                  <th>Amount (₹)</th>
                  <th>Net Wt. (kg)</th>
                  <th>IGST (%)</th>
                  <th>IGST ({{$invoice->currency}}))</th>
                  <th>IGST (₹)</th>
                </tr>
              </thead>

              <tbody>
                <?php $gstrealtotal = 0; ?>
                <?php
                $hsn = [];
                if(COUNT($invoiceTable) > 0) { ?>
                @if(isset($invoiceTable)) @foreach($invoiceTable as $key => $invoiceTable)
                <tr>
                  <td>{{++$key}}</td>
                  <td>{{$invoiceTable->product->code}} - {{$invoiceTable->product->name}}<br>
                    <span style="font-style: italic; white-space: pre-line;">{{$invoiceTable->descriptionBox}}</span>
                  </td>
                  @if($showBatch)
                  <td style="font-size: 11px; white-space: normal; overflow-wrap: anywhere; word-break: break-word;">
                    @if(!empty($packingBatchByProductId[(int)$invoiceTable->product_id])){{ implode(', ', $packingBatchByProductId[(int)$invoiceTable->product_id]) }}@endif
                  </td>
                  @endif
                  <!-- <td>{{$invoiceTable->product->EAN}}</td> -->
                  <td>{{$invoiceTable->product->HSN}}</td>
                  <td>{{$invoiceTable->quantity}}</td>
                  <td>{{$invoiceTable->rate}}</td>
                  <td class="amt-col-sep">{{$invoiceTable->amount}}</td>
                  <td>{{$invoiceTable->amount * $invoice->conrate}}</td>
                  <td>{{$invoiceTable->subtotalnetwt}}</td>
                  <td>{{$invoiceTable->gstslab}}</td>
                  <td>{{round($invoiceTable->gstamount / $invoice->conrate , 2)}}</td>
                  <td>{{$invoiceTable->gstamount}}</td>
                </tr>
                <?php
                  $gstreal = $invoiceTable->gstamount / $invoice->conrate;
                  $gstrealtotal = $gstrealtotal + $gstreal;
                  if(isset($hsn[$invoiceTable->product->HSN]['value'])){
                    $hsn[$invoiceTable->product->HSN]['value'] = $hsn[$invoiceTable->product->HSN]['value'] + ($invoiceTable->amount * $invoice->conrate);
                    $hsn[$invoiceTable->product->HSN]['gstamount'] = $hsn[$invoiceTable->product->HSN]['gstamount'] + $invoiceTable->gstamount;
                    $hsn[$invoiceTable->product->HSN]['amount'] = $hsn[$invoiceTable->product->HSN]['amount'] + ($invoiceTable->amount * $invoice->conrate) + $invoiceTable->gstamount;
                  }else{
                    $hsn[$invoiceTable->product->HSN]['value'] = ($invoiceTable->amount * $invoice->conrate);
                    $hsn[$invoiceTable->product->HSN]['gstamount'] = $invoiceTable->gstamount;
                    $hsn[$invoiceTable->product->HSN]['amount'] = ($invoiceTable->amount * $invoice->conrate) + $invoiceTable->gstamount;
                  }
                  $hsn[$invoiceTable->product->HSN]['rate'] = $invoiceTable->gstslab;
                  
                  
                ?>
                @endforeach @endif
                <?php } ?>
                <tr>
                  <td colspan="{{ $showBatch ? 4 : 3 }}"></td>
                  <td><strong>{{$invoice->totalquantity}}</strong></td>
                  <td></td>
                  <td class="amt-col-sep"><strong>{{$invoice->totalamount - $invoice->shipping_charges - $invoice->packing_charges + $invoice->discount}}</strong></td>
                  <td><strong>{{($invoice->totalamount - $invoice->shipping_charges - $invoice->packing_charges + $invoice->discount)*$invoice->conrate}}</strong></td>
                  <td><strong>{{$invoice->totalwt}}</strong></td>
                  <td></td>
                  <td><strong>{{round($gstrealtotal,2)}}</strong></td>
                  <td><strong>{{$invoice->totalgst}}</strong></td>
                </tr>
                <tr >
                  <td colspan="{{ $showBatch ? 12 : 11 }}">
                    Total Export value ({{$invoice->currency}}) : <b id="tamount">{{round((($invoice->totalamount - $invoice->shipping_charges - $invoice->packing_charges + $invoice->discount)),2)}}</b><br>
                    Amount(In words) : <b id="amountInWords"></b>
                  </td>
                </tr>
                <tr>
                  <td colspan="{{ $showBatch ? 7 : 6 }}"><b>HSN</b></td>
                  <td colspan="2"><b>Taxable Value</b></td>
                  <td><b>Tax Rate</b></td>
                  <td><b>Tax Amount</b></td>
                  <td><b>Total Amount</b></td>
                </tr>
                <?php
                $totalhsn = 0;
                $totalhsngst = 0;
                ?>
                @foreach($hsn as $h=>$v)
                <tr>
                  <td colspan="{{ $showBatch ? 7 : 6 }}">{{$h}}</td>
                  <td colspan="2">{{$v['value']}}</td>
                  <td>{{$v['rate']}}</td>
                  <td>{{$v['gstamount']}}</td>
                  <td>{{$v['value'] + $v['gstamount']}}</td>
                </tr>
                <?php
                $totalhsn = $totalhsn + $v['value'];
                $totalhsngst = $totalhsngst + $v['gstamount'];
                ?>
                @endforeach
                @if($invoice->shipping_charges > 0)
                <tr>
                  <td colspan="{{ $showBatch ? 7 : 6 }}">996521</td>
                  <td colspan="2">{{($invoice->shipping_charges * $invoice->conrate) }}</td>
                  <td>5</td>
                  <td>{{$invoice->shipping_gst }}</td>
                  <td>{{($invoice->shipping_charges * $invoice->conrate) + $invoice->shipping_gst }}</td>
                </tr>
                <?php
                $totalhsn = $totalhsn + ($invoice->shipping_charges * $invoice->conrate);
                $totalhsngst = $totalhsngst +  $invoice->shipping_gst;
                ?>
                @endif
                <tr>
                  <td colspan="{{ $showBatch ? 7 : 6 }}"><b>Total</b></td>
                  <td colspan="2"><b>{{round($totalhsn,2)}}</b></td>
                  <td></td>
                  <td><b>{{round($totalhsngst,2)}}</b></td>
                  <td><b id="tamount2">{{round($totalhsngst,2) + round($totalhsn,2)}}</b></td>
                </tr>
                <tr>
                  <td colspan="{{ $showBatch ? 12 : 11 }}">
                    Tax Amount (in word) : <b>Indian Rupees </b><b id="amountInWords2"></b>
                  </td>
                </tr>
                <tr>
                  <td colspan="{{ $showBatch ? 12 : 11 }}">
                    Company Pan : {{$companyDetails->pan}}
                  </td>
                </tr>
                {{-- Keep REX/additional_info in tbody (once) so tfoot stays small and thead can repeat in Chrome --}}
                <tr class="border-less-row invoice-export-once-notes">
                  <td colspan="{{ $showBatch ? 12 : 11 }}">
                    <div class="mt-2">
                      @if(isset($companyDetails))
                      @if($companyDetails->show_gsp == 1)
                      <p>REX Registration # {{$companyDetails->gsp}}</p>
                      @endif
                      @endif
                    </div><hr>
                    <p style="text-align: justify; font-size: 12px; margin-right: 60px;">{{$invoice->additional_info}}</p>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                  <tr class="packing-footer-split">
                      <td colspan="{{ $showBatch ? 6 : 5 }}" class="packing-footer-left">
                          <div>
                          <h3>DECLARATION</h3>
                          <p style="text-align: justify; font-size: 12px; margin-right: 20px;">{{$invoice->declaration}}
                          </p>
                          </div>
                      </td>
                      <td colspan="6" class="packing-footer-right">
                         <div>
                          <h3 class="sig-name">For Global Vision Direct (P) Ltd</h3>
                          <div style="padding-top: 28px;" class="invoice-sig-gap">___________________________________</div>
                          <div><p>Authorised Signatory</p></div>
                        </div>
                      </td>
                  </tr>
              </tfoot>
            </table>
        </div>
    </div>
  </div>

  <!-- /Start Export Modal -->



  <!-- Start Local Modal -->
  @else
  <header>
    <h1 class="text-center">Tax Invoice</h1>
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

  <div class="container">

    <!-- Header row (e-Invoice QR) -->
      <div class="row" id="clogo">
        <div class="col-8">
          <div style="margin-top:40px;">
          @if(isset($invoice->irn) && !empty($invoice->irn))<p><strong>IRN: </strong>{{$invoice->irn}}</p>
              @endif

          @if(isset($invoice->ack_no) && !empty($invoice->ack_no))<p><strong>Ack No.: </strong>{{$invoice->ack_no}}</p>
              @endif

          @if(isset($invoice->ack_date) && !empty($invoice->ack_date))<p><strong>Ack Date: </strong>{{date('d-M-y',strtotime($invoice->ack_date))}}</p>
              @endif
          </div>
        </div>
        @if(isset($invoice->einvoice_qr) && !empty($invoice->einvoice_qr))
          <div class="col-4">
            <div style="text-align:center;">e-Invoice<br>
            <img src="{{url('uploads/einvoice/')}}/{{$invoice->einvoice_qr}}" style="width:130px;" />
            </div>
          </div>
        @endif
      </div>

    <!-- first row -->
    <div class="row box-space">
        <div class="col-6">
          @if(isset($companyDetails))
          @php
          $imageName = $companyDetails->logoUrl;
      @endphp

      @if(array_key_exists($imageName, $fileMap))
      <img width="250px" src="{{ $fileMap[$imageName] }}" alt="Logo Image" style="width:100px;">
      @else
          <p>Image not found</p>
      @endif 
                  <address>
              <p style="font-weight: 700;">{{$companyDetails->c_name}}</p>
              <p style="font-weight: 700;">{{$companyDetails->address1}}</p>
              <p style="font-weight: 700;">{{$companyDetails->address2}}, {{$companyDetails->city}} - {{$companyDetails->postcode}}, India</p>
              <p>Website: {{$companyDetails->website}}</p>
              <p>Tel # {{$companyDetails->phone1}} / {{$companyDetails->phone2}}</p>
              <p>E-mail: {{$companyDetails->email}}</p>
              <p>PAN # {{$companyDetails->pan}}</p>
              <p>GSTIN: {{$companyDetails->gstin}}</p>
            </address>
            @endif
        </div>
        <div class="col-6">
            <div class="pull-right" id="buyer">
    @if(isset($invoice))
              <h1>Invoice No. {{$invoice->invoiceno}}</h1>
              <h3>Invoice Date: {{date('d-M-Y',strtotime($invoice->date))}} </h3>
              <p style="padding-top: 2.5rem;"><strong>Buyer Ref. No: </strong>{{$invoice->buyerorderno}}</p>
              <p><strong>Lorry No: </strong>{{$invoice->containerno}}</p>
              <p><strong>Vehicle No: </strong>{{$invoice->vehicleno}}</p>
              <p><strong>Total Box: </strong>{{$invoice->totalbox}}</p>
              <p><strong>Kind of Pkgs: </strong>{{$invoice->pkgs}}</p>
              @if(isset($invoice->ewaybillno))<p><strong>E-Way Bill No: </strong>{{$invoice->ewaybillno}}</p>
              @endif
            </div>
        </div>
    </div>

    <!-- second row -->
    <hr><div class="row box-space">
        <div class="col-6">
              @if(isset($invoice))
                <address>
                <h3 style="text-decoration: underline;">CONSIGNEE</h3>
                <p>{{$invoice->buyer->name}}</p>
                <p>{{$invoice->buyer->address1}},</p>
                <p>{{$invoice->buyer->address2}},</p>
                <p>{{$invoice->buyer->city}} - {{$invoice->buyer->postcode}}</p>
                <p>State Name: {{$invoice->buyer->state}}</p>
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

    <!-- third row -->
    <hr><div class="row box-space">
       @if(isset($certificate))
          <div class="col-2">
          <img style="width: 75px; height: auto;" src="{{ Storage::disk('s3')->url('stock/vriksh-logo.png') }}" alt="Vriksh Logo" />
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
              <p>Plot #1216/2, Mahapira Road,</p>
              <p>Jaipur-Mumbai Highway, Bhankrota, Jaipur</p>
            </div>
          </div>
        @endif
    </div>

    <hr><div class="row box-space">
        <div class="col">
            <h3 style="text-decoration: underline;">DESCRIPTION OF GOODS</h3>
            <p>{{$invoice->desgoods}}</p>
        </div>
    </div>

    <!-- second row -->
    <div class="row box-space">
        <div class="col">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Product Description</th>
                  @if($showBatch)
                  <th>Batch</th>
                  @endif
                  <th>EAN</th>
                  <th>HSN</th>
                  <th>QTY</th>
                  <th>Rate (₹)</th>
                  <th>Amount (₹)</th>
                  <th>GST (%)</th>
                @if($invoice->buyer->state == 'Rajasthan')
                  <th>CGST (₹)</th>
                  <th>SGST (₹)</th>
                @else
                  <th>IGST (₹)</th>
                @endif
                  <th>Sub Total (₹)</th>
                </tr>
              </thead>

              <tbody>
                @if(isset($invoiceTable)) @foreach($invoiceTable as $key => $invoiceTable)
                <tr>
                  <td>{{++$key}}</td>
                 <td>{{$invoiceTable->product->code}} - {{$invoiceTable->product->name}}<br>
                    <span style="font-style: italic;">{{$invoiceTable->descriptionBox}}</span>
                  </td>
                  @if($showBatch)
                  <td style="font-size: 11px; white-space: normal; overflow-wrap: anywhere; word-break: break-word;">
                    @if(!empty($packingBatchByProductId[(int)$invoiceTable->product_id])){{ implode(', ', $packingBatchByProductId[(int)$invoiceTable->product_id]) }}@endif
                  </td>
                  @endif
                  <td>{{$invoiceTable->product->EAN}}</td>
                  <td>{{$invoiceTable->product->HSN}}</td>
                  <td>{{$invoiceTable->quantity}}</td>
                  <td>{{$invoiceTable->rate}}</td>
                  <td>{{$invoiceTable->amount}}</td>
                  <td>{{$invoiceTable->gstslab}}</td>
                @if($invoice->buyer->state == 'Rajasthan')
                  <td>{{($invoiceTable->gstamount)/2}}</td>
                  <td>{{($invoiceTable->gstamount)/2}}</td>
                @else
                  <td>{{$invoiceTable->gstamount}}</td>
                @endif
                <td>{{$invoiceTable->amount + $invoiceTable->gstamount}}</td>
                </tr>
                @endforeach @endif
                <tr>
                  <td colspan="{{ $showBatch ? 5 : 4 }}"></td>
                  <td><strong>{{$invoice->totalquantity}}</strong></td>
                  <td></td>
                  <td><strong>{{$invoice->rateamount}}</strong></td>
                  <td></td>
                @if($invoice->buyer->state == 'Rajasthan')
                  <td><strong>{{($invoice->totalgst)/2}}</strong></td>
                  <td><strong>{{($invoice->totalgst)/2}}</strong></td>
                @else
                  <td><strong>{{$invoice->totalgst}}</strong></td>
                @endif
                 <td><strong>{{round($invoice->totalgst + $invoice->rateamount)}}</strong></td>
                </tr>

              </tbody>
            </table>
        </div>
    </div>
    @endif


   <!-- Declaration $ signature -->
    <div class="row box-space" style="margin-bottom: 1rem;">
        <div class="col-8">
          <h3>DECLARATION</h3>
          <p style="text-align: justify; font-size: 12px; margin-right: 60px;">We declare the above information is true & correct.</p>
        </div>
        <div class="col-4">
          <h3 class="sig-name">For Global Vision Direct (P) Ltd</h3>
          <div style="padding-top: 45px;">___________________________________</div>
          <div><p>Authorised Signatory</p></div>
        </div>
    </div>
  </div>

  @endif
  <!-- /Start Local Modal -->

<!-- Script start for Amount in Words -->
<script src="{{ asset('ui-vendor/jquery/jquery.min.js') }}"></script>
<script type="text/javascript">
  $(document).ready(function(){

    var n = document.getElementById('tamount').textContent;
    n = n.replace(/[^0-9.]/g,'');
    n = parseFloat(n,10);

    var nums = n.toString().split('.')

    var whole = convertNumberToWords(nums[0]);

      if (nums.length == 2) 
      {
          if(nums[1].length == 1){
            nums[1] = parseInt(nums[1] * 10);
          }
          var fraction = convertNumberToWords(nums[1]);
          if(whole == 0)
          {            
              var s=document.getElementById('amountInWords');
              s.innerHTML = fraction + ' Only';
          }
          else
          {
              if(fraction)
              {                
                  var s=document.getElementById('amountInWords');
                  s.innerHTML = whole + 'and ' + fraction + ' Only';
              }
              else
              {
                  var s=document.getElementById('amountInWords');
                  s.innerHTML = whole + ' Only';
              }
          }
              
      }

                  
                      
      else 
      {
        if(whole==0)
         {                  
            var s=document.getElementById('amountInWords');
            s.innerHTML = whole + 'Zero Only';        
         }
        else
         {
            var s=document.getElementById('amountInWords');
            s.innerHTML = whole + 'Only';
         }
      }   


    var n = document.getElementById('tamount2').textContent;
    n = n.replace(/[^0-9.]/g,'');
    n = parseFloat(n,10);

    var nums = n.toString().split('.')

    var whole = convertNumberToWords(nums[0]);

      if (nums.length == 2) 
      {
          if(nums[1].length == 1){
            nums[1] = parseInt(nums[1] * 10);
          }
          var fraction = convertNumberToWords(nums[1]);
          if(whole == 0)
          {            
              var s=document.getElementById('amountInWords2');
              s.innerHTML = fraction + 'Paise Only';
          }
          else
          {
              if(fraction)
              {                
                  var s=document.getElementById('amountInWords2');
                  s.innerHTML = whole + 'Rupees and ' + fraction + 'Paise Only';
              }
              else
              {
                  var s=document.getElementById('amountInWords2');
                  s.innerHTML = whole + 'Rupees Only';
              }
          }
              
      }

                  
                      
      else 
      {
        if(whole==0)
         {                  
            var s=document.getElementById('amountInWords2');
            s.innerHTML = whole + 'Zero Rupee Only';        
         }
        else
         {
            var s=document.getElementById('amountInWords2');
            s.innerHTML = whole + 'Rupees Only';
         }
      }                  
                      
      function convertNumberToWords(amount) {
      
        var words = new Array();
        words[0] = '';
        words[1] = 'One';
        words[2] = 'Two';
        words[3] = 'Three';
        words[4] = 'Four';
        words[5] = 'Five';
        words[6] = 'Six';
        words[7] = 'Seven';
        words[8] = 'Eight';
        words[9] = 'Nine';
        words[10] = 'Ten';
        words[11] = 'Eleven';
        words[12] = 'Twelve';
        words[13] = 'Thirteen';
        words[14] = 'Fourteen';
        words[15] = 'Fifteen';
        words[16] = 'Sixteen';
        words[17] = 'Seventeen';
        words[18] = 'Eighteen';
        words[19] = 'Nineteen';
        words[20] = 'Twenty';
        words[30] = 'Thirty';
        words[40] = 'Forty';
        words[50] = 'Fifty';
        words[60] = 'Sixty';
        words[70] = 'Seventy';
        words[80] = 'Eighty';
        words[90] = 'Ninety';

        var amount = amount.toString();
        var atemp = amount.split(".");
        var number = atemp[0].split(",").join("");
        var n_length = number.length;
        var words_string = "";
            
        if (n_length <= 9) {
            var n_array = new Array(0, 0, 0, 0, 0, 0, 0, 0, 0);
            var received_n_array = new Array();
            for (var i = 0; i < n_length; i++) {
                received_n_array[i] = number.substr(i, 1);
            }
            for (var i = 9 - n_length, j = 0; i < 9; i++, j++) {
                n_array[i] = received_n_array[j];
            }
            for (var i = 0, j = 1; i < 9; i++, j++) {
                if (i == 0 || i == 2 || i == 4 || i == 7) {
                    if (n_array[i] == 1) {
                        n_array[j] = 10 + parseInt(n_array[j]);
                        n_array[i] = 0;
                    }
                }
            }

            value = "";
            for (var i = 0; i < 9; i++) {
                if (i == 0 || i == 2 || i == 4 || i == 7) {
                    value = n_array[i] * 10;
                } else {
                    value = n_array[i];
                }
                if (value != 0) {
                    words_string += words[value] + " ";
                }
                if ((i == 1 && value != 0) || (i == 0 && value != 0 && n_array[i + 1] == 0)) {
                    words_string += "Crores ";
                }
                if ((i == 3 && value != 0) || (i == 2 && value != 0 && n_array[i + 1] == 0)) {
                    words_string += "Lakhs ";
                }
                if ((i == 5 && value != 0) || (i == 4 && value != 0 && n_array[i + 1] == 0)) {
                    words_string += "Thousand ";
                }
                if (i == 6 && value != 0 && (n_array[i + 1] != 0 && n_array[i + 2] != 0)) {
                    words_string += "Hundred ";
                } else if (i == 6 && value != 0) {
                    words_string += "Hundred ";
                }
            }
            words_string = words_string.split("  ").join(" ");
        }
        return words_string;
      }
      b = 1;
      $('#tamount').click(function(){
        var a = document.getElementById('tamount').textContent;
        a = a.replace(/[^0-9.]/g,'');
        a = parseFloat(a,10);
        $(this).toggleClass('roundOff');
        if($(this).hasClass('roundOff')){
          if(b == 1){
            a = Math.round(a);
            $(this).find('.amt').html('<strong>'+a+'</strong>');
          }
          if(b == 2){
            a = Math.ceil(a);
            $(this).find('.amt').html('<strong>'+a+'</strong>');
          }
          if(b == 3){
            a = Math.floor(a);
            $(this).find('.amt').html('<strong>'+a+'</strong>');
            b = 0;
          }
          b++;
        }else{
          
        }
      });
  });

  var gstin = document.getElementById("gstin").textContent;
    if(gstin.length != 0){
      document.getElementById("gstin").innerHTML = gstin;
    }
    else{
      document.getElementById("gstin").innerHTML = "N.A";
      
    }

</script>
<!-- Script end -->
<!-- Script start for Print Invoice-->
@if(isset($print) && $print==1)
    <script type="text/javascript">
      document.ready = window.print();
    </script>
@endif
<!-- Script end -->