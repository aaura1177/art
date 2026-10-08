@extends('layouts.app')

@section('content')


    <div class="row mx-3 my-2">
      <h2>Inventory Report</h2>
      
      @hasrole('admin')
      <div class="col">
          <!-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal"  href="{{ url('/product/exportcsv')}}">Download CSV</a> -->
          <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal" href="{{ url('/product/exportIR') }}" onclick="updateForm(this)">Download CSV</a>
         <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal" href="{{ url('/product/inventory_report_pdf') }}" onclick="updateForm(this)">Download PDF</a>
         <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#myInModal" >In Report</a>
         <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#myOutModal" >Out Report</a>
         <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#myProductModal" >Product Report</a>
         <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#myDateWiseModal" >Date Wise Report</a>
      </div>
      @endhasrole
	  
	  @hasrole('factory')
      <div class="col">
          <!-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal"  href="{{ url('/product/exportcsv')}}">Download CSV</a> -->
          <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal" href="{{ url('/product/exportIR') }}" onclick="updateForm(this)">Download CSV</a>
         <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal" href="{{ url('/product/inventory_report_pdf') }}" onclick="updateForm(this)">Download PDF</a>
      </div>
      @endhasrole
	  
    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <form method="GET" action="" class="mb-4">
          <div class="flex items-center">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search"
                class="form-input rounded-lg border p-2 me-2"
            />
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="{{ url('/product/inventory_report') }}" class="btn btn-warning"> Reset </a>
        </div>
      </form>

        <div class="table-responsive">
          <table class="table table-bordered" id="dataTabless" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>Date</th>
                <th>SKU # (Code)</th>
                <th>Product description</th>
                <th>Opening Balance</th>
                <th>Closing Balance</th>
                <th>Type</th>
                <th>Voucher No.</th>
                <th>Supplier Invoice No.</th>
                <th width="130">Reference<br><span style="font-size:11px">Supplier ref no. for IN.<br> Container no. for OUT.</span></th>
           
              </tr>
            </thead>
            <tbody>
            	@if(isset($logs)) @foreach($logs as $key => $log)
              <tr>
                <td>{{date('d F Y',strtotime($log->created_at))}}</td>
                <td>{{$log->product->code}}</td>
                <td>{{$log->product->name}}</td>
                <td>{{$log->opening_balance}}</td>
                <td>{{$log->remaining_stock}}</td>
                <td>{{($log->type == 1?"RECEIVE":"OUT")}} {{$log->quantity}} piece(s)</td>
                <td>{{$log->voucher_no}}</td>
                <td>{{$log->supplier_inv_no}}</td>
                <td>{{$log->ref_no}}</td>
                
              </tr>
              @endforeach @endif
            </tbody>
          </table>

          <div class="mt-4">
            @if(isset($logs))
            {{ $logs->links() }}
            @endif
        </div>

        </div>
      </div>
    </div>

   <!-- MODAL FOR DATE -->
    <div class="modal fade" id="mydateModal" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Enter Date</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
          </div>
          <form id="date_filter" method="POST" action="{{ url('/product/exportIR') }}">
          @csrf
            <div class="modal-body">
                <div class="row">
                  <div class="col-6">
                      <input min="2020-10-15" class="form-control" type="date" name="from" id="fsd" required value="" />
                  </div>
                  <div class="col-6">
                      <input min="2020-10-15" class="form-control" type="date" name="to" id="fed" required value="" />
                  </div>
                </div>
            </div>
            <div class="modal-footer">
              <button type="submit" onclick="return validate()" class="btn btn-success">Download</button>
            </div>
          </form>
        </div>
      </div>
    </div>
	
	<!-- MODAL myInModal -->
    <div class="modal fade" id="myInModal" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Enter Date</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
          </div>
          <form method="POST" action="{{ url('/product/downloadInReport') }}">
          @csrf
            <div class="modal-body">
				<div class="row">
					<div class="col-12">
						{{-- <select type="text" class="selectpicker" data-live-search="true" name="product_id" id="selectProduct" required >
							<option selected>Select Products</option>
							
							@if(isset($products)) @foreach($products as $key => $product)
							  <option value="{{$product->id}}">
							  {{$product->code}} - {{$product->name}}
							  </option>
							@endforeach @endif
						  </select> --}}

              <select id="selectProduct" name="product_id" style="width: 100%;"  class="form-control" required>
                <option value="">Select Product</option>
            </select>

					</div>
				</div>
                <div class="row mt-3">
                  <div class="col-6">
                      <input class="form-control" type="date" name="from" id="fsd" required value="" />
                  </div>
                  <div class="col-6">
                      <input class="form-control" type="date" name="to" id="fed" required value="" />
                  </div>
                </div>
            </div>
            <div class="modal-footer">
              <button type="submit" onclick="return validate()" class="btn btn-success">Download</button>
            </div>
          </form>
        </div>
      </div>
    </div>
	
	<!-- MODAL myOutModal -->
    <div class="modal fade" id="myOutModal" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Enter Date</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
          </div>
          <form method="POST" action="{{ url('/product/downloadOutReport') }}">
          @csrf
            <div class="modal-body">
				<div class="row">
					<div class="col-6">
						<select type="text" class="selectpicker" data-live-search="true" name="product_id" id="selectProduct" required >
							<option selected>Select Products</option>
							
							
							@if(isset($products)) @foreach($products as $key => $product)
							  <option value="{{$product->id}}">
							  {{$product->code}} - {{$product->name}}
							  </option>
							@endforeach @endif
						  </select>
					</div>
				</div>
                <div class="row mt-3">
                  <div class="col-6">
                      <input class="form-control" type="date" name="from" id="fsd" required value="" />
                  </div>
                  <div class="col-6">
                      <input class="form-control" type="date" name="to" id="fed" required value="" />
                  </div>
                </div>
            </div>
            <div class="modal-footer">
              <button type="submit" onclick="return validate()" class="btn btn-success">Download</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- MODAL myDateWiseModal -->
    <div class="modal fade" id="myDateWiseModal" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Enter Date</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
          </div>
          <form method="POST" action="{{ url('/product/downloadDateWiseReport') }}">
          @csrf
            <div class="modal-body">
                <div class="row mt-3">
                  <div class="col-6">
                      <input class="form-control" type="date" name="from" id="fsd" required value="" />
                  </div>
                  <div class="col-6">
                      <input class="form-control" type="date" name="to" id="fed" required value="" />
                  </div>
                </div>
            </div>
            <div class="modal-footer">
              <button type="submit" onclick="return validate()" class="btn btn-success">Download</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- MODAL myProductModal -->
    <div class="modal fade" id="myProductModal" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Select Product</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
          </div>
          <form method="POST" action="{{ url('/product/downloadProductAnnualReport') }}">
          @csrf
            <div class="modal-body">
				<div class="row">
					<div class="col-6">
						<select type="text" class="selectpicker" data-live-search="true" name="product_id" id="selectProduct" required >
							<option selected>Select Products</option>
							
							
							@if(isset($products)) @foreach($products as $key => $product)
							  <option value="{{$product->id}}">
							  {{$product->code}} - {{$product->name}}
							  </option>
							@endforeach @endif
						  </select>
					</div>
				</div>

        <div class="row mt-3">
          <div class="col-6">
              <input class="form-control" type="date" name="from" id="fsd" required value="" />
          </div>
          <div class="col-6">
              <input class="form-control" type="date" name="to" id="fed" required value="" />
          </div>
        </div>
                
            </div>
            <div class="modal-footer">
              <button type="submit" onclick="return validate()" class="btn btn-success">Download</button>
            </div>
          </form>
        </div>
      </div>
    </div>
@endsection

@section('footer')


<script type="text/javascript">
	function validate(){
		var fsd = $('#fsd').val();
		var fed = $('#fed').val();
		if(fsd > fed){
			alert('Start Date should be less than End Date');
			return false;
		}
		return true;
	  }
	function downloadcsv(){
	  $("#modalPrintForm").attr("action","{{ url('/product/exportCodeWiseExcel') }}");
	  return true;
	}
	
	function updateForm(obj){
		url = $(obj).attr('href');
		$("#date_filter").attr('action',url);
	}

  $(function(){

   var table = $("#dataTable").DataTable();
   $('#dataTable_filter').append( '<br><label>Search by date: <input id="date-search" class="form-control" type="text" placeholder="Ex - 12 October 2022" data-index="" /></label>');
		$( table.table().container() ).on( 'keyup', '#date-search', function () {
			table
				.column(0)
				.search( this.value )
				.draw();
		} );
  });
   
   
   
</script>

<script>
  $(document).ready(function() {
      $('#selectProduct').select2({
          placeholder: 'Search for a product',
          minimumInputLength: 2,
          ajax: {
              url: '{{ route("products.search") }}', // Route to your controller method
              dataType: 'json',
              delay: 250,
              data: function (params) {
                  return {
                      q: params.term // search term
                  };
              },
              processResults: function (data) {
                  return {
                      results: data.map(function(product) {
                          return {
                              id: product.id,
                              text: product.code + ' - ' + product.name
                          };
                      })
                  };
              },
              cache: true
          }
      });
  });
  </script>


@endsection