@extends('layouts.app')

@section('content')
<style>
  .fields_center{
    text-align: center !important;
    vertical-align: middle !important;
  }
</style>
    <div class="row mx-3 my-2">
      <h2>Pricing List</h2>
      <div class="col">
        <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalImportExcel">Import Excel</a>
		<form method="POST" action="{{ url('/pricing/exportBuyingCost')}}" id="expcsvBuyingCost" style="float:right;">
			@csrf
			<input name="ids" type="hidden" id="expidsBuyingCost" value="0" />
			<a class="btn btn-info float-end" style="color: #fff; margin-left: 5px;" href="javascript:void(0);" onclick="$('#expcsvBuyingCost').submit();">Buying Cost</a>&nbsp;
		</form>
		<form method="POST" action="{{ url('/pricing/exportcsv')}}" id="expcsv" style="float:right;">
			@csrf
			<input name="ids" type="hidden" id="expids" value="0" />
			<select name="buyer_id2" id="export_buyer_id2" class="form-control d-inline-block" style="width: auto; margin-left: 5px;">
				<option value="">Default row</option>
				@foreach($destinationBuyers ?? [] as $destinationBuyer)
					<option value="{{ $destinationBuyer->id }}">{{ $destinationBuyer->c_name }}</option>
				@endforeach
			</select>
			<a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="javascript:void(0);" onclick="$('#expcsv').submit();">Export</a>&nbsp;
		</form>
        
        <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/pricing/create')}}">Add Pricing</a>
      </div>
    </div>
      
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>#</th>
				        <th style="display:none;">id</th>
                <th class="fields_center">Product</th>
                <th class="fields_center">Buyer1</th>
                <th class="fields_center">Reference</th>
                <th class="fields_center">Valid From</th>
                <th class="fields_center">Valid Till</th>
                <th class="fields_center">Final Cost (INR)</th>
                <th class="fields_center">Buyer2</th>
                <th class="fields_center">FOB India Cost</th>
                <th class="fields_center">Final Delivered Cost</th>
                <th class="fields_center" style="min-width: 75px; text-align: center;" >Options</th>
                <th class="fields_center" style="min-width: 75px; text-align: center;" >Action</th>
              </tr>
            </thead>
            <tbody>
            	@if(isset($pricingTables)) @foreach($pricingTables as $key => $pricing)
              <?php  
              $pricingClone = \App\pricingTable::where('product_id', $pricing->product_id)
                ->where('productType', $pricing->productType)
                ->get();
              $count = count($pricingClone);
              ?>
              <tr >
                <td class="fields_center">{{$loop->iteration}}</td>
				        <td style="display:none;">{{$pricing->id ?? ''}}</td>
                @if($pricing->productType == 1)
                <td class="fields_center">{{$pricing->product->code ?? ''}} - {{$pricing->product->name ?? ''}}</td>
                @else
                <td class="fields_center">{{$pricing->tempProduct->code ?? ''}} - {{$pricing->tempProduct->name ?? ''}}</td>
                @endif
                <td class="fields_center">{{(isset($pricing->tempBuyer->c_name))?$pricing->tempBuyer->c_name:''}}</td>
                <td class="fields_center">{{$pricing->remarks}}</td>
                <td class="fields_center">{{date('d-M-Y',strtotime($pricing->startDate))}}</td>
                <td class="fields_center">{{date('d-M-Y',strtotime($pricing->endDate))}}</td>
                <td class="fields_center">{{$pricing->finalCost}}</td>
                <td class="fields_center">
              <?php
                foreach($pricingClone as $KeyName => $pricingCloneRow){
                 echo $pricingCloneRow->tempBuyer2->c_name ?? '';
                 if ($KeyName != $count - 1) {
                    echo "<hr>";
                }
                  }
                ?>
                </td>
                <td class="fields_center">
                  <?php
                foreach($pricingClone as $KeyFob => $pricingCloneRow){
                 echo $pricingCloneRow->currency.' '.$pricingCloneRow->fobINCost ?? '';
                 if ($KeyFob != $count - 1) {
                    echo "<hr>";
                }
                 }
                  ?>
                  </td>
                <td class="fields_center">
                  <?php
                foreach($pricingClone as $KeyDelCost => $pricingCloneRow){
                 echo $pricingCloneRow->currency.' '.$pricingCloneRow->newDelCost ?? '';
                 if ($KeyDelCost != $count - 1) {
                    echo "<hr>";
                }
                 }
                  ?>
                  </td>
               <td class="fields_center">
                <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" style="font-size: 25px;cursor: pointer;">
                  <i class="fa fa-edit" onclick="updatePricing('{{$pricing->id}}')"></i>
                </span>
               
                <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Duplicate" style="color:blue;font-size: 25px;cursor: pointer;">
                  <i class="fa fa-clone" onclick="duplicate('{{$pricing->id}}')"></i>
                 </span>
               </td>
               <td>
                <?php
                foreach($pricingClone as $KeyId => $pricingCloneRow){ ?>
              <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Download CSV">
                <button class="btn btn-success" onclick="exportEachCsv('{{$pricingCloneRow->id}}')"><i class="fa fa-file-download"></i></button>
              </span>

                 <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                  <button class="btn btn-danger" data-bs-toggle="modal" onclick="deleteModal('{{$pricingCloneRow->id}}')">
                    <i class="fa fa-trash"></i>
                  </button>
                </span> 
                
               <?php 
                  if ($KeyId != $count - 1) {
                      echo "<hr>";
                  }
              }  ?>
              </td>
              </tr>
              @endforeach @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- MODAL FOR DELETE -->
    <div class="modal fade" id="deletePricing" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Delete Confirmation</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
          </div>
          <form id="deletePricingForm" method="POST" action="">
          @csrf  
            <div class="modal-body">
              <p>Are You sure you want to Delete this?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
              <button type="submit" id="deletePricingForm" class="btn btn-danger">Yes</button>
            </div>
          </form>  
        </div>
      </div>
    </div>

    <!-- MODAL FOR IMPORT EXCEL -->
    <div class="modal fade" id="modalImportExcel" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title"> Upload Excel File</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" id="modalImportExcelForm" action="{{ url('/pricing/importCSV') }}" enctype="multipart/form-data">
          @csrf
            <div class="modal-body pb-0">
              <input type="file" name="importCSV" id="importCSV" />
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
@endsection

@section('footer')


<!-- Scripts Starts For Buyer Delete/View -->
<script type="text/javascript">
  
  $(function () {
    $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function deleteModal(id){
    $('#deletePricing').modal('show');
    $('#deletePricingForm').attr('action', "{{ url('/pricing/delete') }}" + '/' +id);
  } 
 
  function updatePricing(id){
     location.href = "{{ url('/pricing/view') }}" + '/' +id;
  }

  function duplicate(id){
    location.href = "{{ url('/pricing/duplicate') }}" + '/' +id;
  }

  function exportEachCsv(id){
    location.href = "{{ url('/pricing/exportEachCSV') }}" + '/' +id;
  }

  function validate(){
      if($('#importCSV').val() == ''){
        alert('Please select a Excel file.');
        return false;
      }
  }
	$(function(){
		var table = $("#dataTable").DataTable();
		
		function syncPricingExportIds(rows) {
			var ids = "";
			rows.forEach(function(item) {
				if (item[1]) {
					ids += item[1] + ",";
				}
			});
			$("#expids").val(ids);
			$("#expidsBuyingCost").val(ids);
		}
		syncPricingExportIds(table.rows().data().toArray());
		table.on('search.dt', function() {
			syncPricingExportIds(table.rows( { filter : 'applied'} ).data().toArray());
		});
  });
  
</script>
<!-- Scripts End -->
@endsection
