@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Products List</h2>
      
      
      <div class="col">
          <!-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal"  href="{{ url('/product/exportcsv')}}">Download CSV</a> -->
          <div class="btn-group float-end" role="group" style="color: #fff; margin-left: 5px;">
            <button id="btnGroupDrop1" type="button" class="btn btn-secondary btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              Downloads
            </button>
            <div class="dropdown-menu">
              <a  class="dropdown-item " data-bs-toggle="modal" data-bs-target="#modalDateFilter" href="javascript:void(0);">Date wise</a>
              <a  class="dropdown-item "  href="{{url('/product/erp_diff_manager_export')}}">Download Discrepancies Sheet</a>
            </div>
          </div>
          {{-- <div class="btn-group float-end" role="group" style="color: #fff; margin-left: 5px;">
            <button id="btnGroupDrop1" type="button" class="btn btn-secondary btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              Reason Filter
            </button>
            <div class="dropdown-menu">
                <a class="dropdown-item " href="{{ url('/product/erp_reason_manager/returns')}}">Returns</a>
                <a class="dropdown-item "  href="{{ url('/product/erp_reason_manager/cancellation')}}">Cancellation</a>
                <a class="dropdown-item "  href="{{ url('/product/erp_reason_manager/mis-shipment')}}">Mis-shipment</a>
            </div>
          </div> --}}
            {{-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalDateFilter">Date Wise</a> --}}
            <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalAddImportExcel">Import Via Excel</a>
            <a class="btn btn-danger float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/product/erp_negetive_manager')}}">Errors</a>
            <a class="btn btn-danger float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/product/erp_diff_manager/all')}}">Discrepancies</a>
            <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/erp-manager')}}">View all</a> 
            <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalAdd2ImportExcel">Inbound excel sheet</a>
            <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalLessImportExcel">Outbound excel sheet</a>
            {{-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalLessImportExcel">Import Less Excel</a> --}}
            
      </div>
      
	  
	  
    </div>
    @if(isset($erp_diff))
    <div class="row">
          <a class="btn btn-danger" style="color: #fff; margin-left: 15px;" href="{{ url('/product/erp_diff_manager/red')}}">Red</a>
          <a class="btn btn-success" style="color: #fff; margin-left: 5px;" href="{{ url('/product/erp_diff_manager/green')}}">Green</a>
    </div>
    @endif
    <div class="card mb-3">
    <?php
$country = \Request::session()->get('country');

$names = [
    'us' => 'Fairview',
    'eu' => 'Magdeburg',
    'canada' => 'Canada',
    'california' => 'California'
];

$name = $names[$country] ?? 'IP-03'; // Default value if country not found
?>
      <div class="card-body">
      Last Sheet ({{$name}}) uploaded is - <b>{{ $last_sheet_ip->name ?? '' }}</b><br>
        Last Sheet (Wayfair) uploaded is - <b>{{ $last_sheet_w->name ?? '' }}</b><br>
        Last Container No. - <b>{{ $last_container }}</b><br>
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>Sku</th>
                <th>Warehouse Quantity</th>
                <th>India Quantity</th>
                <th>Discrepancy</th>
                <th>Options</th>
                
              </tr>
            </thead>
            <tbody>
            	@if(isset($products)) @foreach($products as $key => $product)
                <?php
                  $bc = '#fff';
                  $bcf = '#fff';
                  if($product->warehouse_quantity != null){
                    if($product->quantity > $product->warehouse_quantity){
                      $bc = '#ED99A1';
                    }
                    if($product->quantity < $product->warehouse_quantity){
                      $bc = '#B7E1CD';
                    }
                  }
                  if($product->quantity < 0){
                    $bcf = '#ED9951';
                  }
                ?>
              <tr>
                <td>{{$product->sku}}</td>

                <td id="ukquant_{{$product->id}}">{{$product->warehouse_quantity ?? 0}}</td>
                <td style="background-color:{{$bc}}" id="quant_{{$product->id}}">{{$product->quantity}}</td>
                <td id="diff_{{$product->id}}">@if($product->warehouse_quantity != null) {{$product->warehouse_quantity - $product->quantity}} @endif</td>
                <td>
               
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
              			<button class="btn btn-info" onclick="updateProduct('{{$product->id}}','{{$product->sku}}','{{$product->warehouse_quantity}}')">
              				<i class="fa fa-edit"></i>
              			</button>
                  </span>
      
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Warehouse History">
                    <a class="btn btn-info" href="{{url('/view-erp-history-manager/'.$product->id)}}" >
              				<i class="fa fa-eye"></i>
              			</a>
                  </span>
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="India History">
                    <a class="btn btn-warning" href="{{url('/view-erp-history/'.$product->id)}}" >
              				<i class="fa fa-eye"></i>
              			</a>
                  </span>
                  
                </td>
               
              </tr>
              @endforeach @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL FOR DATE -->
    <div class="modal fade" id="modalDateFilter" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Enter Date</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="{{ url('/product/datewiseerpmanager') }}">
          @csrf
            <div class="modal-body">
                <div class="row">
                  <div class="col-6">
                      <input class="form-control" type="date" name="fsd" id="fsd" required value="" />
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
    <!-- MODAL FOR IMPORT EXCEL -->
    <div class="modal fade" id="modalAddImportExcel" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title"> Upload Excel File</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" id="modalImportExcelForm" action="{{ url('/product/importAddErpCSVManager') }}" enctype="multipart/form-data">
          @csrf
            <div class="modal-body pb-0">
              <input type="file" name="importAddCSV" id="importAddCSV" />
              <p class="mt-3">Make sure excel has sku and quantity fields</p>
              <p class="mt-4 mb-0">Are You sure you want to Upload this?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
              <button type="sumit" id="modalImportExcelForm" onclick="return validate()" class="btn btn-success">Yes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- MODAL FOR IMPORT EXCEL -->
    <div class="modal fade" id="modalAdd2ImportExcel" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title"> Upload Excel File (Add)</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" id="modalImportExcelForm" action="{{ url('/product/importAdd2ErpCSVManager') }}" enctype="multipart/form-data">
          @csrf
            <div class="modal-body pb-0">
              <input type="file" name="importAddCSV" id="importAddCSV" />
              <p class="mt-3">Make sure excel has sku and quantity fields</p>
              <p class="mt-4 mb-0">Are You sure you want to Upload this?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
              <button type="sumit" id="modalImportExcelForm" onclick="return validate()" class="btn btn-success">Yes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- MODAL FOR IMPORT EXCEL -->
    <div class="modal fade" id="modalLessImportExcel" role="dialog">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title"> Upload Excel File (Less)</h4>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="modalImportExcelForm" action="{{ url('/product/importLessErpCSVManager') }}" enctype="multipart/form-data">
            @csrf
              <div class="modal-body pb-0">
                <input type="file" name="importLessCSV" id="importLessCSV" />
                <p class="mt-3">Make sure excel has sku and quantity fields</p>
                <p class="mt-4 mb-0">Are You sure you want to Upload this?</p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
                <button type="sumit" id="modalImportExcelForm" onclick="return validate()" class="btn btn-success">Yes</button>
              </div>
            </form>
          </div>
        </div>
      </div>

    <!-- MODAL FOR IMPORT EXCEL -->
    <div class="modal fade" id="updateModal" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Update <span id="skushow"></span></h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" id="updateSkuForm" action="javascript:void(0);" enctype="multipart/form-data">
          @csrf
            <div class="modal-body pb-0">
              <input class="form-control" type="hidden" name="product_id" id="product_id" />
              <label>Quantity</label>
              <input class="form-control" type="number" name="sku_quant" id="sku_quant" />
              <label>Reason</label>
              <select name="reason" class="form-control" id="reason_modal">
                <option value="">Select Reason</option>
                <option value="Returns">Returns</option>
                <option value="Cancellation">Cancellation</option>
                <option value="Mis-shipment">Mis-shipment</option>
              </select>
              <label>Remarks</label>
              <textarea class="form-control" name="remarks" id="remarks_modal"></textarea>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
              <button type="sumit" id="modalImportExcelForm"  class="btn btn-success">Submit</button>
            </div>
          </form>
        </div>
      </div>
    </div>

	<!-- MODAL FOR Print -->
    <div class="modal fade" id="modalPrint" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title"> Print</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" id="modalPrintForm" action="{{ url('/product/printList') }}" enctype="multipart/form-data">
          @csrf
            <div class="modal-body pb-0">
				<label>Select Code type</label>
				<select class="selectpicker" name="code" required>
					<option value="">Select Code type</option>
					<option value="All">All</option>
					<option value="IN">IN</option>
					<option value="BO">BO</option>
					<option value="AH">AH</option>
					<option value="ASB">ASB</option>
				</select>
            </div>
            <div class="modal-footer">
              <button type="submit" id="modalImportExcelForm" onclick="return printpdf()" class="btn btn-success">Print</button>
              <button type="submit" id="DownloadCSV" onclick="return downloadcsv()" class="btn btn-success">Download</button>
            </div>
          </form>
        </div>
      </div>
    </div>
	
	
	
	<!-- MODAL FOR Image View -->
    <div class="modal fade" id="viewImage" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title"></h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
			<div class="modal-body pb-0">
				<img style="max-width:100%" id="bigProImage" alt="No Image" src="" />
			</div>
        </div>
      </div>
    </div>
@endsection

@section('footer')
<script type="text/javascript">

  
  $(function () {
    $('[data-bs-toggle="tooltip"]').tooltip()

    $('#updateSkuForm').submit(function(){
      $.ajax({
      'url': "{{ url('/update-erp-sku-manager') }}",
      'method': 'POST',
      'data' : $('#updateSkuForm').serialize()

        }).done(function(data){
            $('#ukquant_'+data.id).html(data.quantity);
            $('#diff_'+data.id).html(data.diff_quant);
            $('#updateModal').modal('hide');
          });
      return false;
    });
  });

  function deleteModal(id)
    {
      $('#deleteProduct').modal('show');
      $('#deleteProductForm').attr('action', "{{ url('/product/delete') }}" + '/' +id);
    } 
 
  function updateProduct(id, sku, quantity)
    {
      $('#updateModal').modal('show');
      $('#skushow').html(sku);
      $('#sku_quant').val(quantity);
      $('#product_id').val(id);
      $('#remark_modal').val('');
      $('#reason_modal').prop('selectedIndex',0);
    } 
	
	function downloadcsv(){
	  $("#modalPrintForm").attr("action","{{ url('/product/exportCodeWiseExcel') }}");
	  return true;
	}
	
	function printpdf(){
	  $("#modalPrintForm").attr("action","{{ url('/product/printList') }}");
	  return true;
	}
  
  function fetchImage(obj,product){
	  if($(obj).html() == ""){
		var img = "{{ asset('uploads/allproducts/') }}/"+product+"/"+product+"-1.jpg";
		$(obj).html('<a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#viewImage" onclick="updateSrc(\''+product+'\')"><img class="img-thumbnail img-fluid product-img-100" src="'+img+'" alt="No Image" /></a>');
	  }
  }
  
  function updateSrc(product){
	  var img = "{{ asset('uploads/allproducts/') }}/"+product+"/"+product+"-1.jpg";
	  $("#bigProImage").attr('src',img);
	  $("#viewImage").find("h4").html(product);
  }
    
</script>
@endsection
