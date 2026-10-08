@extends('layouts.app')

@section('content')

      <!-- first row -->
      <div class="row mx-3 my-2">
        <h2>Shipping List</h2>
        <div class="col">
          <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/shipping/create')}}">Add Shipping</a>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th colspan=4></th>
                  <th colspan=7 style='text-align:center'>India</th>
                  <th colspan=7 style='text-align:center'>UK</th>
                  <th></th>                  
                </tr>
                <tr>
                  <th style="display:none;"></th>
                  <th>Month</th>
                  <th>Shipment Number</th>
                  <th>Container</th>
                  <th>Shipping Line</th>
                  <th style="background:#B7E1CD;">Indian Agent</th>
                  <th style="background:#B7E1CD;">THC (INR)</th>
                  <th style="background:#B7E1CD;">B/L (INR)</th>
                  <th style="background:#B7E1CD;">Incidental Charges (INR)</th>
                  <th style="background:#B7E1CD;">Type</th>
                  <th style="background:#B7E1CD;">Transit Time</th>
                  <th style="background:#B7E1CD;">Total (INR)</th>
                  <th style="background:#FCE8B2;">UK Agent</th>
                  <th style="background:#FCE8B2;">Ocean Freight($)</th>
                  <th style="background:#FCE8B2;">Conversion Rate</th>
                  <th style="background:#FCE8B2;">THC (UK)</th>
                  <th style="background:#FCE8B2;">Handling Charges UK(£)</th>
                  <th style="background:#FCE8B2;">Other Charges UK (£)</th>
                  <th style="background:#FCE8B2;">UK Total (£)</th>
                  <th>Options</th>
                </tr>
              </thead>
              <tbody>
              	@if(isset($shipping)) @foreach($shipping as $key => $shipping)
                <tr @if($shipping->reference == '' && $shipping->container == '') style="font-weight: bold;" @endif>
                  <td style="display:none;">{{date('m',strtotime($shipping->month))}}</td>
                  <td>{{$shipping->month}}</td>
                  <td>{{$shipping->reference}}</td>
                  <td>{{$shipping->container}}</td>   
                  <td>{{$shipping->shippingLines->name}}</td>
                  <td style="background:#B7E1CD;">{{$shipping->shippingLines->agent_name}}</td> 
                  <td style="background:#B7E1CD;">{{$shipping->thc}}</td>
                  <td style="background:#B7E1CD;">{{$shipping->bl}}</td>   
                  <td style="background:#B7E1CD;">{{$shipping->incidental_charges}}</td>                   
                  <td style="background:#B7E1CD;">{{$shipping->type}}</td>   
                  <td style="background:#B7E1CD;">{{$shipping->transit_time}}</td>
                  <td style="background:#B7E1CD;">{{$shipping->total_india}}</td>
                  <td style="background:#FCE8B2;">{{$shipping->shippingLines->agent_uk}}</td>
                  <td style="background:#FCE8B2;">{{$shipping->ocean_freight}}</td> 
                  <td style="background:#FCE8B2;">{{$shipping->conversion_rate}}</td> 
                  <td style="background:#FCE8B2;">{{$shipping->thc_uk}}</td>
                  <td style="background:#FCE8B2;">{{$shipping->handling_charges}}</td>   
                  <td style="background:#FCE8B2;">{{$shipping->other}}</td>                     
                  <td style="background:#FCE8B2;">{{$shipping->total_uk}}</td>               
                  <td>
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                        <button class="btn btn-info" onclick="updateCourier('{{$shipping->id}}')">
            							<i class="fa fa-edit"></i>
            						</button>
                      </span>
					  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                        <button class="btn btn-danger" onclick="deleteModal('{{$shipping->id}}')">
            							<i class="fa fa-trash"></i>
            						</button>
                      </span>
                  </td>
                </tr>
                @endforeach @endif
              </tbody>
            </table>
          </div>
        </div>
      </div>
	  <!-- MODAL FOR DELETE -->
    <div class="modal fade" id="deleteShipping" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Delete Confirmation</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
          </div>
          <form id="deleteShippingForm" method="POST" action="">
          @csrf  
            <div class="modal-body">
              <p>Are You sure you want to Delete this?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-bs-dismiss="modal" autofocus="">No</button>
              <button type="submit" id="deleteShippingForm" class="btn btn-danger">Yes</button>
            </div>
          </form>  
        </div>
      </div>
    </div>
@endsection

@section('footer')


<!-- Scripts Start For Shipping Delete/View -->
<script type="text/javascript">

  $(function () {
  $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function updateCourier(id){

     location.href = "{{ url('/shipping/view') }}" + '/' +id;
  }
  
  function deleteModal(id)
    {
      $('#deleteShipping').modal('show');
      $('#deleteShippingForm').attr('action', "{{ url('/shipping/delete') }}" + '/' +id);
    } 

</script>
<!-- Scripts End -->

@endsection
