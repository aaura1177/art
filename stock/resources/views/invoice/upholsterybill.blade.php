@extends('layouts.app')

@section('content')
<script src="{{ asset('ui-vendor/jquery.floatThead.js') }}"></script>
<script>
$(function(){
	$('.table').each(function(){
		var $table = $(this);
		$(this).floatThead({
			position: 'fixed',
			responsiveContainer: function() { return $table.closest('.table-responsive'); }
		});
	});
	
});
</script>
<style>
th {
  background: white;
}
</style>
<script>
$(function(){
	$("[id^='frate']").change(function(){
		info		=	$(this).attr('id').split("_");
		invoice_id 	= 	info[1];
		product_id 	= 	info[2];
		frate 		=	$(this).val();
		pquant		=	$("#quant_"+invoice_id+"_"+product_id).val();
		$("#total_"+invoice_id+"_"+product_id).val(frate*pquant);
		$("[id^='camount_"+invoice_id+"_"+product_id+"_']").each(function(){
			cid = $(this).attr('id').split("_");
			cid = cid[3];
			quant = $("#cquant_"+invoice_id+"_"+product_id+"_"+cid).val();
			$(this).val(quant*frate);
			submitRecord(invoice_id,product_id,cid,quant,frate*quant,frate);
		});
	});
	
	$("[id^='frate']").each(function(){
		info		=	$(this).attr('id').split("_");
		invoice_id 	= 	info[1];
		product_id 	= 	info[2];
		frate 		=	$(this).val();
		pquant		=	$("#quant_"+invoice_id+"_"+product_id).val();
		$("#total_"+invoice_id+"_"+product_id).val(frate*pquant);
	});
	
	$("[id^='cquant']").change(function(){	
		info		=	$(this).attr('id').split("_");
		invoice_id 	= 	info[1];
		product_id 	= 	info[2];
		cid 		= 	info[3];
		frate 		=	$("#frate_"+invoice_id+"_"+product_id).val();
		quant 		= 	$(this).val();
		$("#camount_"+invoice_id+"_"+product_id+"_"+cid).val(frate*quant);
		total_quant = 0;
		$("[id^='cquant_"+invoice_id+"_"+product_id+"_']").each(function(){
			total_quant = 	parseInt(total_quant) + parseInt($(this).val());
		});
		pquant		=	parseInt($("#quant_"+invoice_id+"_"+product_id).val());
		
		total_quant	=	parseInt(total_quant);
		$("#balance_"+invoice_id+"_"+product_id).val(pquant - total_quant);
		submitRecord(invoice_id,product_id,cid,quant,frate*quant,frate);
	});
	
	$("[id^='cquant']").each(function(){
		info		=	$(this).attr('id').split("_");
		invoice_id 	= 	info[1];
		product_id 	= 	info[2];
		cid 		= 	info[3];
		frate 		=	$("#frate_"+invoice_id+"_"+product_id).val();
		quant 		= 	$(this).val();
		$("#camount_"+invoice_id+"_"+product_id+"_"+cid).val(frate*quant);
		total_quant = 0;
		$("[id^='cquant_"+invoice_id+"_"+product_id+"_']").each(function(){
			total_quant = 	parseInt(total_quant) + parseInt($(this).val());
		});
		pquant		=	parseInt($("#quant_"+invoice_id+"_"+product_id).val());
		
		total_quant	=	parseInt(total_quant);
		$("#balance_"+invoice_id+"_"+product_id).val(pquant - total_quant);
		submitRecord(invoice_id,product_id,cid,quant,frate*quant,frate);
	});
	
	function submitRecord(invoice_id,product_id,contractor_id,quantity,total_amount,upholestry_rate){
		finish	=	$("#finishing_"+invoice_id+"_"+product_id).val();
		token 	=	$("[name^='_token']").val();
		$.ajax({
			'url': "{{ url('/invoice/createUpholsteryBill') }}",
			'method': 'POST',
			'data': {"_token":token,"product_id":product_id,"invoice_id":invoice_id,"contractor_id":contractor_id,"quantity":quantity,"amount":total_amount,"upholestry_rate":upholestry_rate},
			success:function(r){
			
			}
		});
	}
});
</script>
  <div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Upholstery Bill</h2>
    </div>


      <!-- Form Starts -->
      <div class="form-group"> 
	  
		<!-- Product Details-->
		@foreach($products as $inv => $pro)
          <div class="row mt-5 my-3">                                       
              <div class="col-6">
                <h5>{{$inv}}</h5>
              </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover">
                <thead id="thead_{{$inv}}">
                  <tr id="mytable">
                    <th scope="col" style="min-width: 200px;">Product</th>
                    <th scope="col" style="min-width: 70px;">QTY</th>
                    <th scope="col" style="min-width: 120px;">Upholestry Rate</th>
					@foreach($contractors as $c)
						<th scope="col" style="min-width: 120px;">{{$c->name}}</th>
						<th scope="col" style="min-width: 120px;">{{$c->name}} Amount</th>
					@endforeach
					<th scope="col" style="min-width: 120px;">Balance</th>
					<th scope="col" style="min-width: 120px;">Total</th>
                  </tr>
                </thead>
				<tbody>
					@foreach($pro as $key => $p)
					<form id='createContractorBill{{$p->product_id}}' method="POST" action="{{ url('/invoice/createContractorBill') }}">
					@csrf
					<tr>
						<td scope="col">{{$p->product->code}} - {{$p->product->name}}</td>
						<td scope="col"><input id="quant_{{$p->invoice_id}}_{{$p->product_id}}" class="form-control" name="quant[{{$p->invoice_id}}][{{$p->product_id}}]" value="{{$p->quantity}}" readonly /></td>
						<td scope="col"><input id="frate_{{$p->invoice_id}}_{{$p->product_id}}" class="form-control" type="number" name="frate[{{$p->invoice_id}}][{{$p->product_id}}]" /></td>
						@foreach($contractors as $c)
							<?php
							if(isset($cbills[$p->invoice_id][$p->product_id][$c->id])){
								$bill		=	$cbills[$p->invoice_id][$p->product_id][$c->id];
								$quantity	=	$bill->quantity;
								$amount		=	$bill->amount;
								$frate		=	$bill->upholestry_rate;
							}else{
								$quantity	=	0;
								$amount		=	0;
								$frate		=	$p->product->upholstry;
							}
							if($frate == 0){
								$frate		=	$p->product->upholstry;
							}
							?>
							<script>
								$("#frate_"+<?php echo $p->invoice_id; ?>+"_"+<?php echo $p->product_id; ?>).val(<?php echo $frate; ?>);
							</script>
							<td scope="col"><input max="{{$p->quantity}}" id="cquant_{{$p->invoice_id}}_{{$p->product_id}}_{{$c->id}}" class="form-control" type="number" name="cquant[{{$p->invoice_id}}][{{$p->product_id}}][{{$c->id}}]" value="{{($frate)?(($quantity)?$quantity:$p->quantity):$quantity}}" /></td>
							<td scope="col"><input id="camount_{{$p->invoice_id}}_{{$p->product_id}}_{{$c->id}}" class="form-control" type="number" name="camount[{{$p->invoice_id}}][{{$p->product_id}}][{{$c->id}}]" value="{{$amount}}" readonly /></td>
						@endforeach
						<?php
							if(isset($cbills[$p->invoice_id][$p->product_id][0])){
								$bill		=	$cbills[$p->invoice_id][$p->product_id][0];
								$quantity	=	$bill->quantity;
							}else{
								$quantity	=	0;
							}
						?>
						<td scope="col"><input id="balance_{{$p->invoice_id}}_{{$p->product_id}}" class="form-control" type="number" name="balance[{{$p->invoice_id}}][{{$p->product_id}}]" readonly /></td>
						<td scope="col"><input id="total_{{$p->invoice_id}}_{{$p->product_id}}" class="form-control" type="number" name="total[{{$p->invoice_id}}][{{$p->product_id}}]" readonly /></td>
					</tr>
					</form>
					@endforeach
				</tbody>
            </table>
          </div>
		  @endforeach
        </div>
	
</div>

@endsection

@section('footer')