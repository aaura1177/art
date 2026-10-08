@extends('layouts.app')


@section('content')

<div class="row mx-3 my-2">
  <h2>Carton List</h2>
  
  @hasrole('admin')
  <div class="col">
      <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/product/create')}}">Add Product</a>
  </div>
  @endhasrole
  
  @hasrole('factory')
  <div class="col">
    <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/product/families')}}">Families</a>
    <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalPrint">Print</a>
  </div>
  @endhasrole
</div>



<div class="card mb-3">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered" id="productdataTables" width="100%" cellspacing="0">
        <thead>
          <tr>
              <th>Code</th>
              <th>Name</th>
            <th>Box 1 Quantity</th>
            <th>Box 2 Quantity</th>
            @hasrole('admin')
            <th>Options</th>
            @endhasrole
          </tr>
        </thead>
        <tbody>
          @if(isset($consumables)) 
            @foreach($consumables as $key => $consumable)
              <tr>
                  <td>{{ $consumable->product->code ?? 'N/A' }}</td>
                  <td>{{ $consumable->product->name ?? 'N/A' }}</td>
                <td>{{ $consumable->box_1_qty ?? 0 }}</td>
                <td>{{ $consumable->box_2_qty ?? 0 }}</td>
                @hasrole('admin')
                <td>
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="See Detail Report">
                    <a class="btn btn-info" href="{{ route('carton.detail_report', $consumable->id) }}">
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
        buttons: [
            {
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
        "columnDefs": [
            { "orderable": false, "targets": 0 } // Disable sorting on the first column (index 0)
        ]
    });

    // Filter by Product Code Dropdown
    $('#filterProductCode').on('change', function() {
        table.column(0).search(this.value).draw(); // Filters on the first column (product code)
    });

    // Enable tooltips
    $(function () {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });

    // Fetch image functionality
    function fetchImage(obj, product) {
        if ($(obj).html() == "") {
          var img = "{{ Storage::disk('s3')->url('stock/allproducts/') }}" + product + "/" + product + "-1.jpg";
          $(obj).html('<a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#viewImage" onclick="updateSrc(\'' + product + '\')"><img class="img-thumbnail img-fluid product-img-100" src="' + img + '" alt="No Image" /></a>');
        }
    }
});
</script>
@endsection

