@php
    $printFontSize = $printFontSize ?? 10;
    $containerWidth = $containerWidth ?? 1180;
@endphp
<style>
    .container {
        width: {{ $containerWidth }}px !important;
    }

    @media screen and (max-width: 1199px), print {
        .container {
            width: 100% !important;
            max-width: 100% !important;
        }

        .table {
            table-layout: auto !important;
            width: 100% !important;
        }

        .table th,
        .table td,
        .table-striped.table th,
        .table-striped.table td {
            white-space: normal !important;
            word-break: normal !important;
            overflow-wrap: normal !important;
            font-size: {{ $printFontSize }}px !important;
            padding: 2px 3px !important;
        }
    }

    @media print {
        .container {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }
    }
</style>
