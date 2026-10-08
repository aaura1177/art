<!DOCTYPE html>
<html lang="en">

  <head>
		<style>
			.table th {
				font-size: 11px !important;
			}
			.table td {
				font-size: 10px !important;
			}
		</style>
	</head>

<body>
	
    <div style="text-align:center;" >
		<img src="{{ realpath(base_path().'/../uploads/' . $companyDetails->logoUrl) }}" alt="Logo Image" />
		<h3 class="text-center">Inventory Report</h3>
		<h5 style="font-size:11px;" class="text-center">{{ date('d F Y',strtotime($from)) }} - {{ date('d F Y',strtotime($to)) }}</h5>
    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table border='1' class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>Code</th>
                <th width="80">Name</th>
                <th width="40">Quantity</th>
                <th width="50">Voucher No.</th>
                <th width="40">Reference</th>
                <th>Type</th>
				<th>Opening Balance</th>
				<th>Closing Quantity</th>
				<th>Date</th>
              </tr>
            </thead>
            <tbody>
            	@if(isset($logs)) @foreach($logs as $key => $log)
              <tr>
                <td>{{$log->product->code}}</td>
                <td>{{$log->product->name}}</td>
                <td>{{$log->quantity}}</td>
                <td>{{$log->voucher_no}}</td>
                <td>{{$log->ref_no}}</td>
                <td>{{($log->type == 1?"IN":"OUT")}}</td>
                <td>{{$log->opening_balance}}</td>
                <td>{{$log->remaining_stock}}</td>
				<td>{{date('d F Y',strtotime($log->created_at))}}</td>
              </tr>
              @endforeach @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>

</body>
</html>