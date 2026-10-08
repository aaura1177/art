@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Stock Age</h2>
      
      @hasrole('admin')
      <!--<div class="col">
         <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal" href="{{ url('/product/valuationPdfView')}}" onclick="updateForm(this)">Download PDF</a>
         <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal" href="{{ url('/product/exportValuationExcel')}}" onclick="updateForm(this)">Download CSV</a>
      </div>-->
      @endhasrole
	  
	  @hasrole('factory')
      <!--<div class="col">
         <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal" href="{{ url('/product/valuationPdfView')}}">Download PDF</a>
         <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal" href="{{ url('/product/exportValuationExcel')}}">Download CSV</a>
      </div>-->
      @endhasrole
    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Quantity</th>
				<th>Age</th>
              </tr>
            </thead>
            <tbody>
            	@if(isset($products)) @foreach($products as $key => $product)
              <tr>
				
                <td>{{$product->code}}</td>
                <td>{{$product->name}}</td>
                <td>{{$product->quantity}}</td>
				

        <td>
					<?php
					$remaining =	$product->quantity;
					$ins		=	[];
					$rates		=	[];
					$last 			=	0;
					foreach($product->pbTable as $inn){
						$ins[$inn->id]['qty']		=	$inn->receiveqty;
						$ins[$inn->id]['date']		=	$inn->created_at;
            $date1 = date_create(date('Y-m-d'));
            $date2 = date_create(date('Y-m-d',strtotime($inn->created_at)));
            $diff = date_diff($date1,$date2);
            $diffdays = $diff->format("%a days");
            if($diffdays <31){
              $ins[$inn->id]['diff']		=	'0-30 days';
            }
            if($diffdays < 61 && $diffdays > 30){
              $ins[$inn->id]['diff']		=	'30-60 days';
            }
            if($diffdays < 91 && $diffdays > 60){
              $ins[$inn->id]['diff']		=	'61-90 days';
            }
            if($diffdays > 90){
              $ins[$inn->id]['diff']		=	'Above 90 days';
            }
					}
					krsort($ins);
					$qty = 0;
					$amount = 0;
					$remq = $product->quantity;
					foreach($ins as $in){
						$qty = $qty+$in['qty'];
						if($qty > $product->quantity){
              if($remq){
							  echo $remq .' - '. $in['diff'] .'<br>';
              }
							break;
						}else{
							$remq = $remq - $in['qty'];
							echo $in['qty'] .' - '. $in['diff'] .'<br>';
						}
					}
				?>
				</td>
              </tr>
              @endforeach @endif
            </tbody>
          </table>
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
          <form id="date_filter" method="POST" action="{{ url('/product/exportValuationExcel') }}">
          @csrf
            <div class="modal-body">
                <div class="row">
                  <div class="col-6">
                      <input min="2020-10-15" class="form-control" type="date" name="from" id="fsd" value="" />
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

  
  $(function () {
	$('[data-bs-toggle="tooltip"]').tooltip()
  });

  function deleteModal(id)
    {
      $('#deleteProduct').modal('show');
      $('#deleteProductForm').attr('action', "{{ url('/product/delete') }}" + '/' +id);
    } 
 
  function updateProduct(id)
    {
      location.href = "{{ url('/product/view') }}" + '/' +id;
    }
	
	function downloadcsv(){
	  $("#modalPrintForm").attr("action","{{ url('/product/exportCodeWiseExcel') }}");
	  return true;
	}
	
	
	function updateForm(obj){
		url = $(obj).attr('href');
		$("#date_filter").attr('action',url);
	}
  
    
</script>
@endsection