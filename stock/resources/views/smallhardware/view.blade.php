@extends('layouts.app')

@section('content')

  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Update Packaging</h2>
    </div>

    <form method="POST" action="{{ url('/packaging/view/'.$packaging->id) }}">
    	@csrf

     <!-- Form Starts -->
    	<div class="form-group">
        <div class=" col-4">
			<label class="control-label">{{ __('Employee Name') }}</label>
			<select style="border: 1px" class="selectpicker" data-live-search="true" name="employee_id" id="selectEmployee" required >
                <option selected >Select Employee</option>
                @if(isset($employees)) @foreach($employees as $key => $employee)
                  <option value="{{$employee->id}}" {{($packaging->employee_id == $employee->id)?'selected':''}}>
                  {{$employee->given_name}}
                  </option>
                @endforeach @endif
            </select>
        </div>

        <div class=" col-4 mt-3">
          <label class="control-label">{{ __('Type') }}</label>
          <select name="type" class="selectpicker">
			<option selected value="">Select Type</option>
			<option {{($packaging->type == 'Corner')?'selected':''}} value="Corner">Corner</option>
			<option {{($packaging->type == 'L')?'selected':''}} value="L">L</option>
		  </select>
        </div>
		
		<div class=" col-4 mt-3">
          <label class="control-label">{{ __('Quantity') }}</label>
          <input type="text" class="form-control" name="quantity" step="any" value="{{$packaging->quantity}}" />
        </div>
		
		<div class="col-4 mt-3">
		  <label class="control-label">{{ __('Invoice No.') }}</label>
		  <select type="text" class="selectpicker" data-live-search="true" name="invoices[]" required="required" multiple>
		  <?php
			$invoices = explode(',',$packaging->invoices);
		  ?>
			<option value="" disabled>Select Invoice</option>
			  @if(isset($invoice)) @foreach($invoice as $key => $invoice)
				<option value="{{$invoice->invoiceno}}"  {{(in_array($invoice->invoiceno, $invoices))?'selected':''}}>
				{{$invoice->invoiceno}}
				</option>
			  @endforeach @endif
		  </select>
		</div>

        <div class=" col-4">
          <button type="submit" class="btn btn-primary mt-3">Add Packaging</button>
        </div>

      </div>
    </form>
  </div>

@endsection