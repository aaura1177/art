@extends('layouts.app')


@section('content')

<div class="row mx-3 my-2">
  <h2>Products List</h2>

  @hasrole('admin')
  <div class="col">
     <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/product/create')}}">Add Product</a>
    <!-- <a class="btn btn-primary float-end" style="color:#fff; margin-left:5px;"
      href="#"
      data-bs-toggle="modal"
      data-bs-target="#batchReportModal">
      Stock Report
    </a> -->

    <!-- <a class="btn btn-primary float-end" style="color:#fff; margin-left:5px;"
      href="#"
      data-bs-toggle="modal"
      data-bs-target="#batchdetailReportModal">
      Batch Report
    </a> -->

     <a class="btn btn-primary float-end" style="color:#fff; margin-left:5px;"
      href="#"
      data-bs-toggle="modal"
      data-bs-target="#batchdetailReportStockModal">
      Batch Stock Report
    </a>


    <!-- <a class="btn btn-primary float-end" style="color:#fff; margin-left:5px;"
      href="/batch/export">
      Batch
    </a> -->
  </div>


  @endhasrole

  @hasrole('factory')
  <div class="col">
    <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/product/families')}}">Families</a>
    <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalPrint">Print</a>
  </div>
  @endhasrole
</div>

<!-- Filter Form -->
<form method="GET" action="{{ route('products.inventory') }}" class="mb-3">
  <div class="row">
    <div class="col-md-3">
      <select name="category" class="form-control">
        <option value="">Select Category</option>
        @foreach($cat as $category)
        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <select name="subcategory" class="form-control">
        <option value="">Select Subcategory</option>
        @foreach($subcat as $subcategory)
        <option value="{{ $subcategory->id }}" {{ request('subcategory') == $subcategory->id ? 'selected' : '' }}>{{ $subcategory->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-md-3">
      <select id="filterProductCode" class="form-control">
        <option value="">Select Product Code</option>
        @foreach($products as $product)
        <option value="{{ $product->code }}">{{ $product->code }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-md-2">
      <button type="submit" class="btn btn-primary">Filter</button>
    </div>
  </div>
</form>

<div class="card mb-3">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered" id="productdataTables" width="100%" cellspacing="0">
        <thead>
          <tr>
            <th>Code</th>
            <th>Image</th>
            <th>Name</th>
            <th>Category</th>
            <th>Sub-Category</th>
            <th>EAN</th>
            <th>Quantity</th>
            @hasrole('admin|auditor')
            <th>Options</th>
            @endhasrole
          </tr>
        </thead>
        <tbody>
          @if(isset($products))
          @foreach($products as $key => $product)
          <tr>
            <td>{{ $product->code }}</td>
            <td onmouseover="fetchImage(this,'{{ $product->code }}')"></td>
            <td>{{ $product->name }}</td>
            <td>{{ $product->category->name }}</td>
            <td>{{ $product->subCategory->name }}</td>
            <td>{{ $product->EAN }}</td>
            <td>{{ $product->quantity }}</td>
            @hasrole('admin|auditor')
            <td>
              <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="See Detail Report">
                <a class="btn btn-info" href="{{ route('products.detail_report', $product->id) }}">
                  <i class="fa fa-file"></i>
                </a>
              </span>
            </td>
            @endhasrole
          </tr>
          @endforeach
          @endif
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Triggers unchanged -->

<div class="modal fade" id="batchReportModal" tabindex="-1" role="dialog" aria-labelledby="batchReportLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <form method="post" action="{{route('products.stockExport.report')}}">
        @csrf
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



<!-- Modal: Batch Detail Report -->
<div class="modal fade" id="batchdetailReportModal" tabindex="-1" role="dialog" aria-labelledby="batchdetailReportLabel" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="batchdetailReportLabel">Batch Detail Report</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form action="{{route('products.batchExport.report')}}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label for="batch_no" class="form-label">Select Batch</label>
            <select class="form-control kt-select2" id="batch_no" name="batch_no" required>
              <option value="all">All</option>
              @foreach($batches as $b)
              <option value="{{ $b->batch_no }}">{{ $b->batch_no }}</option>
              @endforeach
            </select>
            <small class="form-text text-muted">Choose “All” to export all batches, or pick a single batch.</small>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="la la-download me-1"></i> Download Excel
          </button>
        </div>
      </form>

    </div>
  </div>
</div>



<!-- Modal: Batch Detail Report -->
<div class="modal fade" id="batchdetailReportStockModal" tabindex="-1" role="dialog" aria-labelledby="batchdetailReportStockModal" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="batchdetailReportLabel">Batch Stock Report</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form action="{{ route('products.batchExport.stock.reprot') }}" method="POST">
        @csrf

        <div class="modal-body">
          <div class="form-group">
            <label for="batch_no" class="form-label">Select Batch</label>
            <select class="form-control selectpicker" data-live-search="true" id="batch_no" name="batch_no" required>
              <option value="all">All</option>
              @foreach($batches as $b)
              <option value="{{ $b->batch_no }}">{{ $b->batch_no }}</option>
              @endforeach
            </select>
            <small class="form-text text-muted">
              Choose “All” to export all batches, or pick a single batch.
            </small>
          </div>

          <div class="form-group">
            <label for="from_date" class="form-label">From Date</label>
            <input type="date" id="from_date" name="from_date" class="form-control" required>
          </div>

          <div class="form-group">
            <label for="to_date" class="form-label">To Date</label>
            <input type="date" id="to_date" name="to_date" class="form-control" required>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="la la-download me-1"></i> Download Excel
          </button>
        </div>
      </form>

    </div>
  </div>
</div>


@endsection


@section('footer')

<!-- Buttons CSS -->
<link href="{{ asset('ui-vendor/datatables/buttons/buttons.dataTables.min.css') }}" rel="stylesheet">

<!-- Buttons JS -->
<script src="{{ asset('ui-vendor/datatables/buttons/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('ui-vendor/datatables/buttons/buttons.html5.min.js') }}"></script>
<script src="{{ asset('ui-vendor/datatables/buttons/jszip.min.js') }}"></script>

<script type="text/javascript">
  $(document).ready(function() {
    // Initialize DataTable with export buttons
    var table = $('#productdataTables').DataTable({
      dom: 'Bfrtip', // Enables buttons and positions them before the table
      buttons: [{
          extend: 'csv',
          text: 'Export CSV',
          className: 'btn btn-secondary'
        },
        {
          extend: 'excel',
          text: 'Export Excel',
          className: 'btn btn-success'
        }
      ],
      "ordering": true, // Enables ordering globally
      "order": [], // Disables initial sorting on any column
      "columnDefs": [{
          "orderable": false,
          "targets": 0
        } // Disable sorting on the first column (index 0)
      ]
    });

    // Filter by Product Code Dropdown
    $('#filterProductCode').on('change', function() {
      table.column(0).search(this.value).draw(); // Filters on the first column (product code)
    });

    // Enable tooltips
    $(function() {
      $('[data-bs-toggle="tooltip"]').tooltip();
    });

    // Fetch image functionality
    function fetchImage(obj, product) {
      if ($(obj).html() == "") {
        var img = "{{ asset('uploads/allproducts/') }}/" + product + "/" + product + "-1.jpg";
        $(obj).html('<a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#viewImage" onclick="updateSrc(\'' + product + '\')"><img class="img-thumbnail img-fluid product-img-100" src="' + img + '" alt="No Image" /></a>');
      }
    }
  });
</script>

<!-- JS to toggle fields/required/disabled -->
<script>
  (function() {
    const toggles = {
      date: document.getElementById('toggle_date'),
      inv: document.getElementById('toggle_invoice'),
      batch: document.getElementById('toggle_batch')
    };

    const blocks = {
      date: document.getElementById('filter_date_range'),
      inv: document.getElementById('filter_invoice_no'),
      batch: document.getElementById('filter_batch_no')
    };

    const inputs = {
      from: document.getElementById('from_date'),
      to: document.getElementById('to_date'),
      inv: document.getElementById('invoice_no'),
      batch: document.getElementById('batch_no')
    };

    function enable(el, on) {
      el.required = on;
      el.disabled = !on;
      if (!on) el.value = ''; // clear when turning off
    }

    function sync() {
      const useDate = toggles.date.checked;
      const useInv = toggles.inv.checked;
      const useBat = toggles.batch.checked;

      blocks.date.classList.toggle('d-none', !useDate);
      blocks.inv.classList.toggle('d-none', !useInv);
      blocks.batch.classList.toggle('d-none', !useBat);

      enable(inputs.from, useDate);
      enable(inputs.to, useDate);
      enable(inputs.inv, useInv);
      enable(inputs.batch, useBat);
    }

    Object.values(toggles).forEach(t => t.addEventListener('change', sync));
    sync(); // init
  })();
</script>

{{-- selectpicker is used for batch dropdown live search; do not init select2 here --}}

@endsection