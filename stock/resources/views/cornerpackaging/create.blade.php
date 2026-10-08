@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Add Packaging by Staff records</h2>
    </div>

    <form method="POST" action="{{ url('/cornerpackaging/store') }}">
    	@csrf

      <!-- Form Starts -->
    	<div class="form-group">
        <div class=" col-4">
			<label class="control-label">{{ __('Month') }}</label>
			<select style="border: 1px" class="selectpicker" data-live-search="true" name="month" id="selectMonth" required >
                <option selected >Select Month</option>
                  <option value="{{date('F Y')}}">
					  {{date('F Y')}}
                  </option>
				  <option value="{{date('F Y',strtotime('last month'))}}">
					  {{date('F Y',strtotime('last month'))}}
                  </option>
            </select>
        </div>
		
		
		
		<div class="col-4 mt-3">
		  <label class="control-label">{{ __('Invoice No.') }}</label>
		  <select type="text" class="selectpicker" data-live-search="true" name="invoices[]" required="required" multiple id="selectInvoice">
			<option value="" selected disabled>Select Invoice</option>
			  @if(isset($invoice)) @foreach($invoice as $key => $inv)
			  <optgroup label="{{$key}}">
				@foreach($inv as $k => $in)
					<option value="{{$in->invoiceno}}">
					{{$in->invoiceno}}
					</option>
				@endforeach
				</optgroup>
			  @endforeach @endif
		  </select>
		</div>
		
		<!-- Packaging Details-->
          <div class="row mt-5 my-3">                                       
              <div class="col-6">
                <h5>Packaging Details</h5>
              </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                  <tr id="mytable">
                    <th scope="col" style="min-width: 300px;">Employee</th>
                    <th scope="col" style="min-width: 100px;">Corner Quantity</th>
                    <th scope="col" style="min-width: 100px;">L Quantity</th>
                    <th scope="col" style="min-width: 150px;">Amount</th>
                  </tr>
                </thead>
                <tbody id="cornerPackaging">
                </tbody>
            </table>
          </div>
		  <div class="row mt-2">
              <div class="col">
                <input type="button" id="addCornerPackaging" class="btn btn-primary" value="Add Employee data" />
              </div>
          </div>  

        <div class=" col-4">
          <button type="submit" class="btn btn-primary mt-3">Add Packaging</button>
        </div>

      </div>
    </form>
  </div>
<script>
$(document).ready(function() {

	$.ajax({
      'url': "{{ url('/cornerpackaging/empdata') }}",
      'method': 'GET'
	}).done(function(data) {
      if (data) {
		employees = data.employees;
      }
    });
	
	$('#selectMonth').change(function(){
		m = $(this).val();
		$('optgroup[label="'+m+'"] option').each(function(){
			$(this).attr('selected',"");
		});
	});
});
var employeeRows = 0;
$("#addCornerPackaging").click(function() {
  var options = '<option selected disabled>-- SELECT Employee --</option>';
  
    employeeRows += 1;

    $.each(employees, function(index, value) {
        options += '<option value="' + value.id + '">' + value.given_name + '</option>';
    });
	

  var $block = "";
      $block += '<tr>';
      $block += '<td id="pr">'
      $block += '<select type="text" class="selectpicker" data-live-search="true" data-len="' + employeeRows + '" name="cp['+employeeRows+'][employee_id]" required="required">';
      $block += options + '</select></td>';
      $block += '<td id="cornerquan'+employeeRows+'"><input type="number" min="0" class="form-control quantity" onchange="changeAmount(this);" data-len="' + employeeRows + '" name="cp['+employeeRows+'][corner_quantity]" value="1" /></td>'
      $block += '<td id="lquan'+employeeRows+'"><input type="number" min="0" class="form-control quantity" onchange="changeAmount(this);" data-len="' + employeeRows + '" name="cp['+employeeRows+'][l_quantity]" value="1" /></td>'
      $block += '<td id="amount'+employeeRows+'"><input type="number" class="form-control amount" name="cp['+employeeRows+'][amount]" value="0.00" readonly/></td>'
	  $block += '<td><button type="button" class="close" onclick="deleterowInvoice(this);" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></td>';
      $block += '</tr>';
      $("#cornerPackaging").append($block);
      $('.selectpicker').selectpicker();
});

function changeAmount(obj){
	var len = $(obj).data('len');
	var qty = parseInt($("#cornerquan"+len+" input").val()) + parseInt($("#lquan"+len+" input").val());
	var amount = parseInt(qty) * 0.50;
	$('#amount'+len+' input').val(amount);
}

function deleterowInvoice(ref) {
  $(ref).parents("tr").remove();
}
</script>
@endsection
