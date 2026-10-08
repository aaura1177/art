@extends('layouts.app')

@section('content')

<div class="row mx-3 my-2">
    <h2>Inventory Report for  - {{$products->product->name}}  </h2>
</div>

<!-- Filter Form -->
<form action="{{ route('consumable.detail_report', $products->id) }}" method="GET">
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
                        <th>Product </th>
                        <th>Date</th>
                        <th>Box</th>
                        <th>Opening Balance</th>
                        <th>In</th>
                        <th>Out</th>
                        <th>Closing Balance</th>
                        <th>Supplier Invoice No.</th>
                        <th>Supplier Name</th>
                        <th>Reference</th>
                        <th>Remark</th>
                    </tr>
                    
                </thead>
                <!-- Table Body -->
                <tbody>
                    @if(isset($logs))
                    @php 
                        $totalIn = 0;
                        $totalIn1 = 0;
                        $totalIn2 = 0;
                        $totalOut = 0;
                        $totalOut1 = 0;
                        $totalOut2 = 0;
                        $closingBalance = 0;
                        $closingBalance1 = 0;
                        $closingBalance2 = 0;
                    @endphp
                        @foreach($logs as $log)
                            @php 
                                if ($log->type == 1) {
                                    $totalIn1 += $log->quantity;
                                    $totalIn2 +=  $log->quantity2;
                                    $totalIn += $log->quantity + $log->quantity2;
                                }
                                if ($log->type == 2) {
                                    $totalOut1 += $log->quantity;
                                    $totalOut2 +=  $log->quantity2;
                                    $totalOut += $log->quantity + $log->quantity2;
                                }
                                
                            @endphp
                            <tr>
                                <td>{{ $log->product->code ?? 'N/A' }}</td>
                                <td>{{ date('d F Y', strtotime($log->created_at)) }}</td>
                                <td>Box 1  <br> Box 2</td>
                                <td>{{ $log->opening_balance }} <br> {{ $log->opening_balance2 }}</td>
                                <td>{{ $log->type == 1 ? $log->quantity : 0}}  <br> {{ $log->type == 1 ? $log->quantity2 : 0}}</td>
                                <td>{{ $log->type == 2 ? $log->quantity : 0}}  <br> {{ $log->type == 2 ? $log->quantity2 : 0}}</td>
                                <td>{{ $log->remaining_stock }} <br> {{ $log->remaining_stock2 }}</td>
                                <td>{{ $log->voucher_no }}</td>
                                <td>{{ $log->supplier_name ?? "" }}</td>
                                <td>{{ $log->ref_no }}</td>
                                <td>{{ $log->remark }}</td>
                            </tr>
                        @endforeach
                        @php
                        $closingBalance1 =$totalIn1-$totalOut1;
                        $closingBalance2 =$totalIn2-$totalOut2;
                        $closingBalance =$totalIn-$totalOut;
                        @endphp
                    @endif
                </tbody>

<!-- Table Footer for Totals -->
                <tfoot>
                    <tr>
                        <th colspan="3">Box 1</th>
                        <th>{{ $totalIn1 }}</th> <!-- Total In -->
                        <th>{{ $totalOut1 }}</th> <!-- Total Out -->
                        <th>{{ $closingBalance1 }}</th> <!-- Latest Closing Balance -->
                        <th colspan="5"></th>
                    </tr>
                    <tr>
                        <th colspan="3">Box 2</th>
                        <th>{{ $totalIn2 }}</th> <!-- Total In -->
                        <th>{{ $totalOut2 }}</th> <!-- Total Out -->
                        <th>{{ $closingBalance2 }}</th> <!-- Latest Closing Balance -->
                        <th colspan="5"></th>
                    </tr>
                    <tr>
                        <th colspan="3">Totals</th>
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

@endsection
