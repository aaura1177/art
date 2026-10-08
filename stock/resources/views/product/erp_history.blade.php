@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>History {{$product->sku}}</h2>
      
      @hasrole('admin')
      {{-- <div class="col">
          <!-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal"  href="{{ url('/product/exportcsv')}}">Download CSV</a> -->
          
          
            <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalAddImportExcel">Import Add Excel</a>
            <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalLessImportExcel">Import Less Excel</a>
            <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/product/export_erp')}}">Download CSV</a>
            
      </div> --}}
      @endhasrole
	  
	  @hasrole('factory')
	  {{-- <div class="col">
    <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/product/families')}}">Families</a>
		<a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalPrint">Print</a>
	  </div> --}}
	  @endhasrole
    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>Sku</th>                
                <th>Sheet Name</th>
                <th>Quantity</th>
                <th>Type</th>
                <th>Remaining Stock</th>
                <th>Logged On</th>
                <th>Reason</th>
                <th>Remark</th>
              </tr>
            </thead>
            <tbody>
            	@if(isset($history)) @foreach($history as $key => $his)
              <tr>
                <td>{{$his->sku}}</td>
                <td >{{$his->sheet->name ?? ''}}</td>
                <td >{{$his->quantity}}</td>
                <td >{{ucwords($his->type)}}</td>
                <td >{{$his->stock}}</td>
                <td >{{date('d M Y H:i:s', strtotime($his->created_at .' + 5 hours 30 minutes'))}}</td>
                <td >{{$his->reason}}</td>
                <td >{{$his->remark}}</td>
                
              </tr>
              @endforeach @endif
            </tbody>
          </table>
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
          <form method="POST" id="modalImportExcelForm" action="{{ url('/product/importAddErpCSV') }}" enctype="multipart/form-data">
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
            <form method="POST" id="modalImportExcelForm" action="{{ url('/product/importLessErpCSV') }}" enctype="multipart/form-data">
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
              <label>Remarks</label>
              <textarea class="form-control" name="remarks"></textarea>
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
@endsection