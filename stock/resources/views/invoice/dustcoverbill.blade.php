@extends('layouts.modal')

<body>

  <!-- next page -->
  <header>
      <h1 class="text-center">Dust Cover Bill</h1>
  </header>
  <div class="container">
	

    <form method="POST" action="{{ url('/invoice/dustcoverbillfinal') }}">
	  @csrf
		<div class="modal-body">
			<div class="row">
				<div class="col-6">
					<input name="po_no" type="hidden" class="form-control" value="{{$purchaseOrderNo}}" />
				</div>
				<div class="col-6">
					<input name="date" type="hidden" class="form-control" value="{{date('d-M-Y',strtotime($date))}}" />
				</div>
			</div>
			<div class="row">
				<input id="iifhb" name="invoice_id" type="hidden" value="{{implode(',',$invoice_ids)}}">
				<input id="supplier" name="supplier" type="hidden" value="{{$supplier_id}}">
			</div>
		</div>
		<div class="modal-footer">
		  <button type="submit" onclick="return submitHardwareBill()" class="btn btn-success">Create PO</button>
		</div>
	  </form>
    <!-- Header second row -->
    <div class="row mt-3">
        <div class="col">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
			@php
			$grandValue = 0;
			$grandTotalValue = 0;
			@endphp
			@if(isset($invoice)) @foreach($invoice as $key => $invoice)
				@php
				$totalValue = 0; 
				$k = 0;
				@endphp
              <thead>
                <tr class="border-less-row">
                  
                  <th colspan="6">{{$invoice->buyerorderno}}</th>
                </tr>
                <tr>
                  <th>#</th>
                  <th>Product Description</th>
                  <th>QTY</th>
                  <th>Dust Cover Size</th>
                  <th>Dust Cover Price</th>
                  <th>Total</th>
                </tr>
              </thead>

              <tbody>
                @if(isset($invoiceTable[$invoice->id])) @foreach($invoiceTable[$invoice->id] as $key => $invt)
					@if(isset($pricing[$invt->product_id]))
						@php
						$showProd =	0;
                        $value = $pricing[$invt->product_id]->dust_cover_price * $invt->quantity;
                        $totalValue = $totalValue + $value; 
						@endphp
						@if(!empty($pricing[$invt->product_id]))
							<tr>
								<td>{{++$k}}</td>
								<td>{{$invt->product->code}} - {{$invt->product->name}}<br>
									<span style="font-style: italic; white-space: pre-line;">{{$invt->descriptionBox}}</span>
								</td>
								<td>{{$invt->quantity}}</td>
								<td>{{$pricing[$invt->product_id]->dust_cover_size}}</td>
								<td>{{$pricing[$invt->product_id]->dust_cover_price}}</td>
								<td>{{$value}}</td>
							</tr>
						@else
							@if(!$showProd)
								@php
								$showProd =	1;
								@endphp
							<tr>
								<td>{{++$k}}</td>
								<td>{{$invt->product->code}} - {{$invt->product->name}}<br>
									<span style="font-style: italic; white-space: pre-line;">{{$invt->descriptionBox}}</span>
								</td>
								<td>{{$invt->quantity}}</td>
								<td>0</td>
								<td>0</td>
								<td>0</td>
							</tr>
							@endif
						@endif
					@endif			
                @endforeach @endif
                <tr>
                  <td colspan="3"></td>
                  <td colspan="2"><strong>Total</strong></td>
                  <td><strong>{{$totalValue}}</strong></td>
				  <?php
					$grandValue	=	$grandValue + $totalValue;
				  ?>
                </tr>
				<tr>
					<td colspan="6"></td>	
				</tr>
				<tr>
					<td colspan="6"></td>
				</tr>
				<tr>
					<td colspan="6"></td>	
				</tr>
              </tbody>
			  @endforeach @endif
			  <tbody>
				
				<tr>
					<td colspan="3"></td>
					<td colspan="2"><strong>Total</strong></td>
					<td><strong>{{$grandValue}}</strong></td>
				</tr>
				@if($supplier->gst)
					@php
					$gstpercent = $supplier->gstpercent/2;
					$grandTotalValue = $grandValue + ($grandValue*$supplier->gstpercent)/100;
					@endphp
				<tr>
					<td colspan="3"></td>
					<td colspan="2"><strong>CGST</strong></td>
					<td><strong>{{($grandValue*$gstpercent)/100}}</strong></td>
				</tr>
				<tr>
					<td colspan="3"></td>
					<td colspan="2"><strong>SGST</strong></td>
					<td><strong>{{($grandValue*$gstpercent)/100}}</strong></td>
				</tr>
				@endif
				
				@if($supplier->tds)
					@php
					$grandTotalValue = $grandTotalValue - ($grandValue*$supplier->tdspercent)/100;
					@endphp
				<tr>
					<td colspan="3"></td>
					<td colspan="2"><strong>TDS</strong></td>
					<td><strong>{{($grandValue*$supplier->tdspercent)/100}}</strong></td>
				</tr>
				@endif
				<tr>
					<td colspan="3"></td>
					<td colspan="2"><strong>Grand Total</strong></td>
					<td><strong>{{$grandTotalValue}}</strong></td>
				</tr>
			  </tbody>
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