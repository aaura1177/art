@extends('layouts.app')

@section('content')

<div class="row mx-3 my-2">
    <h2>Inventory Report for SKU {{$products->code}} - {{$products->name}}</h2>
</div>

@hasrole('admin')
  <div class="col">
<a class="btn btn-primary float-end" style="color:#fff; margin-left:5px;"
   href="#"
   data-bs-toggle="modal"
   data-bs-target="#batchReportModal">
  Stock Report
</a>
</div>
@endhasrole

<!-- Filter Form -->
<form action="{{ route('products.detail_report', $products->id) }}" method="GET">
    <div class="row mb-3">
        <div class="col-md-3">
            <label for="fromFilter">From Date:</label>
            <input type="date" id="fromFilter" name="from" value="{{ request('from') }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label for="toFilter">To Date:</label>
            <input type="date" id="toFilter" name="to" value="{{ request('to') }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label for="typeFilter">Select Type</label>
            <select id="typeFilter" name="type" class="form-control">
                <option value="">All</option>
              
                    <option value="1" {{ request('type') == 1 ? 'selected' : '' }}>
                        Recieve
                    </option>
                    <option value="2" {{ request('type') == 2 ? 'selected' : '' }}>
                       Out
                    </option>
              
            </select>
        </div>
        <div class="col-md-4">
            <label>&nbsp;</label><br>
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </div>
</form>

<div class="card mb-3">
    <div class="card-body">
      
        <div class="table-responsive">
            <table class="table" id="inventorydataTables" width="100%" cellspacing="0">
              
                <thead>
               
                    <tr>
                        <th>Date</th>
                        <th>Opening Balance</th>
                        <th>In</th>
                        <th>Out</th>
                        <th>Closing Balance</th>
                        
                        <th>Supplier Invoice No.</th>
                        <th>Internal Invoice No.</th>
                        <th>Batch No.</th>
                        <th>Supplier Name</th>
                        <th>Reference</th>
                    </tr>
                    
                </thead>
                <!-- Table Body -->
                <tbody>
                    @if(isset($logs))
                    @php 
                        $totalIn = 0;
                        $totalOut = 0;
                        $closingBalance = 0;
                    @endphp
                        @foreach($logs as $log)
                            @php 
                                if ($log->type == 1) {
                                    $totalIn += $log->quantity;
                                }
                                if ($log->type == 2) {
                                    $totalOut += $log->quantity;
                                }
                                // Always update the closing balance with the latest one
                                
                            @endphp
                            <tr>
                                <td>{{ date('d F Y', strtotime($log->created_at)) }}</td>
                                <td>{{ $log->opening_balance }}</td>
                                <td>{{ $log->type == 1 ? $log->quantity : 0}}</td>
                                <td>{{ $log->type == 2 ? $log->quantity : 0}}</td>
                                <td>{{ $log->remaining_stock }}</td>
                                <td>{{ $log->voucher_no }}</td>
                                <td>{{ $log->supplier_inv_no }}</td>
                                <td>{{ $log->batch_no ?? "No batch" }}</td>
                                <td>{{ $log->supplier_name ?? "" }}</td>
                                <td>{{ $log->ref_no }}</td>
                            </tr>
                        @endforeach
                        @php
                        $closingBalance =$totalIn-$totalOut;
                        @endphp
                    @endif
                </tbody>

<!-- Table Footer for Totals -->
                <tfoot>
                    <tr>
                        <th colspan="2">Totals</th>
                        <th>{{ $totalIn }}</th> <!-- Total In -->
                        <th>{{ $totalOut }}</th> <!-- Total Out -->
                        <th>{{ $closingBalance }}</th> <!-- Latest Closing Balance -->
                        <th colspan="5"></th>
                    </tr>
                </tfoot>

               
            </table>
        </div>
    </div>
</div>


<!-- Triggers unchanged -->

<div class="modal fade" id="batchReportModal" tabindex="-1" role="dialog" aria-labelledby="batchReportLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <form method="post" action="{{route('products.stockExport.report.prodcut')}}">
        @csrf
       <input type="hidden" name="product_id" value="{{$products->id}}" id="">
        <div class="modal-header">
          <h5 class="modal-title" id="batchReportLabel">Batch Report Filters</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <!-- Toggles -->
<div class="form-row">
  <div class="form-group col-md-4">
    <div class="custom-control custom-checkbox">
      <input type="checkbox" class="custom-control-input" id="toggle_date">
      <label class="custom-control-label" for="toggle_date">By Date Range</label>
    </div>
  </div>
  <div class="form-group col-md-4">
    <div class="custom-control custom-checkbox">
      <input type="checkbox" class="custom-control-input" id="toggle_invoice">
      <label class="custom-control-label" for="toggle_invoice">By Invoice No.</label>
    </div>
  </div>
  <div class="form-group col-md-4">
    <div class="custom-control custom-checkbox">
      <input type="checkbox" class="custom-control-input" id="toggle_batch">
      <label class="custom-control-label" for="toggle_batch">By Batch No.</label>
    </div>
  </div>
</div>

<!-- Date range -->
<div class="form-row d-none" id="filter_date_range">
  <div class="form-group col-md-6">
    <label>From date</label>
    <input type="date" class="form-control" name="from_date" id="from_date" disabled>
  </div>
  <div class="form-group col-md-6">
    <label>To date</label>
    <input type="date" class="form-control" name="to_date" id="to_date" disabled>
  </div>
</div>

<!-- Invoice no -->
<div class="form-group d-none" id="filter_invoice_no">
  <label>Invoice No.</label>
  <input type="text" class="form-control" name="invoice_no" id="invoice_no" placeholder="e.g. INV-000123" disabled>
</div>

<!-- Batch no -->
<div class="form-group d-none" id="filter_batch_no">
  <label>Batch No.</label>
  <input type="text" class="form-control" name="batch_no" id="batch_no" placeholder="e.g. BATCH-42" disabled>
</div>

<small class="text-muted d-block">
  Leave all toggles off to see <strong>All</strong> records (no extra filters).
</small>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Apply</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@section('footer')

<link href="{{ asset('ui-vendor/datatables/buttons/buttons.dataTables.min.css') }}" rel="stylesheet">
<script src="{{ asset('ui-vendor/datatables/buttons/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('ui-vendor/datatables/buttons/buttons.html5.min.js') }}"></script>
<script src="{{ asset('ui-vendor/datatables/buttons/buttons.print.min.js') }}"></script>
<script src="{{ asset('ui-vendor/datatables/buttons/jszip.min.js') }}"></script>

<script type="text/javascript">
    $(document).ready(function() {
        var fromDate = $('#fromFilter').val();
        var toDate = $('#toFilter').val();
        
        // Get the total in, total out, and closing balance from the table footer
        var totalIn = '{{ $totalIn }}';
        var totalOut = '{{ $totalOut }}';
        var closingBalance = '{{ $closingBalance }}';

        $('#inventorydataTables').DataTable({
            "order": [], // This disables initial sorting on any column
        "columnDefs": [
            { "orderable": false, "targets": 0 } // Disable sorting on the first column (index 0)
        ],
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'copy',
                    title: function() {
                        return 'Inventory Report From ' + fromDate + ' To ' + toDate;
                    },
                    footer: true
                },
                {
                    extend: 'csv',
                    title: function() {
                        return 'Inventory Report From ' + fromDate + ' To ' + toDate;
                    },
                    footer: true
                },
                {
                    extend: 'excel',
                    title: function() {
                        return 'Inventory Report From ' + fromDate + ' To ' + toDate;
                    },
                    footer: true
                },
                {
                    extend: 'pdf',
                    title: function() {
                        return 'Inventory Report From ' + fromDate + ' To ' + toDate;
                    },
                    footer: true,
                    customize: function (doc) {
                        // Adding the totals in the footer of the PDF document
                        doc.content[1].table.body.push(
                            [
                                {text: 'Totals', colSpan: 2, alignment: 'right'}, {}, 
                                {text: totalIn.toString()}, 
                                {text: totalOut.toString()}, 
                                {text: closingBalance.toString()},
                                '', '', '', '', ''
                            ]
                        );
                    }
                },
                {
                    extend: 'print',
                    title: function() {
                        return 'Inventory Report From ' + fromDate + ' To ' + toDate;
                    },
                    footer: true,
                    customize: function (win) {
                        // Adding totals in the print view footer
                        $(win.document.body).find('tfoot').append(
                            '<tr>' +
                                '<th colspan="2">Totals</th>' +
                                '<th>' + totalIn + '</th>' +
                                '<th>' + totalOut + '</th>' +
                                '<th>' + closingBalance + '</th>' +
                                '<th colspan="5"></th>' +
                            '</tr>'
                        );
                    }
                }
            ]
        });
    });
</script>


<!-- JS to toggle fields/required/disabled -->
<script>
  (function() {
    const toggles = {
      date:   document.getElementById('toggle_date'),
      inv:    document.getElementById('toggle_invoice'),
      batch:  document.getElementById('toggle_batch')
    };

    const blocks = {
      date:  document.getElementById('filter_date_range'),
      inv:   document.getElementById('filter_invoice_no'),
      batch: document.getElementById('filter_batch_no')
    };

    const inputs = {
      from:  document.getElementById('from_date'),
      to:    document.getElementById('to_date'),
      inv:   document.getElementById('invoice_no'),
      batch: document.getElementById('batch_no')
    };

    function enable(el, on) {
      el.required = on;
      el.disabled = !on;
      if (!on) el.value = ''; // clear when turning off
    }

    function sync() {
      const useDate = toggles.date.checked;
      const useInv  = toggles.inv.checked;
      const useBat  = toggles.batch.checked;

      blocks.date.classList.toggle('d-none', !useDate);
      blocks.inv.classList.toggle('d-none', !useInv);
      blocks.batch.classList.toggle('d-none', !useBat);

      enable(inputs.from, useDate);
      enable(inputs.to,   useDate);
      enable(inputs.inv,  useInv);
      enable(inputs.batch,useBat);
    }

    Object.values(toggles).forEach(t => t.addEventListener('change', sync));
    sync(); // init
  })();
</script>


@endsection
