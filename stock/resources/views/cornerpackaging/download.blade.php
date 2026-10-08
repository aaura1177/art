@extends('layouts.modal')

<body>

  <!-- next page -->
  <header>
      <h1 class="text-center">Hardware Accessories - {{$cornerpackaging->month}}</h1>
  </header>
  <div class="container">
	<div class="row box-space">
        <div class="col-6">
          <h3 style="text-decoration: underline;">BILL TO</h3><br/>
          @if(isset($companyDetails))
            <img src="{{ url('uploads/' . $companyDetails->logoUrl) }}" alt="Logo Image">
            <p style="font-size: 24px;"><strong>{{ $companyDetails->c_name }}</strong></p>
            <address>
              <p style="font-weight: 700;">{{$companyDetails->address1}}</p>
              <p style="font-weight: 700;">{{$companyDetails->address2}}, {{$companyDetails->city}} - {{$companyDetails->postcode}}, India</p>
              <p>State Name: {{$companyDetails->state}}</p>
              <p>Company PAN: {{$companyDetails->pan}}</p>
              <p>GSTIN/UIN: {{$companyDetails->gstin}}</p>
              <p>E-mail: po@artisanfurniture.net</p>
            </address> 
            @endif
        </div>
		
		<div class="col-6">
            <div class="pull-right" id="supplier">
              @if(isset($supplier))
				<h1>PO No. {{$cornerpackaging->po_no}}</h1>
                <p>PO Date: {{date('d-M-Y',strtotime($date))}}</p><br>
               <address>
                <h3 class="box-space" style="text-decoration: underline;">SUPPLIER</h3>
                <p>{{$supplier->c_name}}</p>
                <p>{{$supplier->address1}}</p>
                <p>{{$supplier->address2}}</p>
                <p>{{$supplier->city}} - {{$supplier->postcode}}</p>
                <p>State Name: {{$supplier->state}}</p>
                <p>GSTIN/UIN: <span style="font-weight: 600; font-size: 16px;" id="gstin">{{$supplier->gstin}}</span></p>
                <p>Phone: {{$supplier->phone1}}</p>
                <p>E-mail: {{$supplier->email}}</p>
              </address>
			  @endif
            </div>
        </div>
        
    </div>

    <div class="row box-space">
      <div class="col-6">
          <address>
            <table width="100%" cellspacing="0">
              <tbody>
                <tr>
                  <td><h3 style="text-decoration: underline;">DISPATCH TO</h3></td>
                </tr>
                <tr>
                  <td><p>Global Vision Direct (P) Ltd</p></td>
                </tr>
                <tr>
                  <td><p>Plot# 1216/2, Mahapura Road,</p></td>
                </tr>
                <tr>
                  <td><p>Jaipur - Mumbai National Highway,</p></td>
                </tr>
                <tr>
                  <td><p>Bhankrota, Jaipur, Rajasthan - 08</p></td>
                </tr>
                <tr>
                  <td><p>GSTIN/UIN: {{$companyDetails->gstin}}</p></td>
                </tr>
              </tbody>
            </table>
          </address>
      </div>
      
    </div>
    <!-- Header second row -->
    <div class="row mt-3">
        <div class="col">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0" >
				<thead>
                <tr>
                  <th>#</th>
                  <th>Employee</th>
                  <th>Corners</th>
                  <th>L</th>
                  <th>Amount</th>
                  <th>Signature</th>
                </tr>
              </thead>
			@php
			$totalCornerQtyValue = 0;
			$totalLQtyValue = 0;
			$totalAmountValue = 0;
			$k = 0;
			@endphp
			@if(isset($cornerpackagingDetail)) @foreach($cornerpackagingDetail as $key => $packaging)
				@php
				$k++;
				// print_r($packaging->employee);die;
				$totalCornerQtyValue 	= $totalCornerQtyValue+$packaging->corner_quantity;
				$totalLQtyValue 		= $totalLQtyValue+$packaging->l_quantity;
				$totalAmountValue 		= $totalAmountValue+$packaging->amount;
				@endphp
              

              <tbody>
                <tr>
					<td>{{$k}}</td>
					<td>{{$packaging->employee->given_name}}</td>
					<td>{{$packaging->corner_quantity}}</td>
					<td>{{$packaging->l_quantity}}</td>
					<td>{{$packaging->amount}}</td>
					<td  style="padding:29px"></td>
                </tr>
              </tbody>
			  @endforeach @endif
			  <tbody>
			  <tr><td colspan = "6"></td></tr>
			  <tr><td colspan = "6"></td></tr>
			  <tr>
				<td></td>
				<td></td>
				<td><b>{{$totalCornerQtyValue}}</b></td>
				<td><b>{{$totalLQtyValue}}</b></td>
				<td><b>{{$totalAmountValue}}</b></td>
				<td></td>
			  </tbody>
			 </tr>
            </table>
        </div>
    </div>
</div>

<!-- Script start for Print Invoice-->
@if(isset($print) && $print==1)
    <script type="text/javascript">
      document.ready = window.print();
    </script>
@endif
<!-- Script end -->