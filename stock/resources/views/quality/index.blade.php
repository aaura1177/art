@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Quality</h2>
      <div class="col">
        <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/quality/create')}}">Add Quality</a>
        <a class="btn btn-success float-end m-1" style="color: #fff;" href="{{ url('/quality/download')}}">Download</a>
      </div>
    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>#</th>
                <th>Product</th>
				<th>Image</th>
                <th>Channel</th>
                <th>Artisan Order No.</th>
                <th>Qty</th>
                <th>Date</th>
                <th>Remarks</th>
                <th>Options</th>
              </tr>
            </thead>
            
            <tbody>
            	@if(isset($quality)) @foreach($quality as $key => $quality)
          	  <tr>
                <td>{{++$key}}</td>
                <td>{{$quality->product->code}} - {{$quality->product->name}}</td>
                <td>
    @if(!empty($quality->imageURL))
        @php
            
            $imgs = explode(',', $quality->imageURL);
        @endphp
        @foreach($imgs as $n => $img)
            @if($img != "")
                @php
                    $s3Url = Storage::disk('s3')->url('stock/quality/' . $img);
                @endphp
                @if($n == 0)
                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#viewImage" onclick="updateSrc('{{ $img }}')">
                        <img width="100" src="{{ $s3Url }}" />
                    </a><br>
                @else
                    <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#viewImage" onclick="updateSrc('{{ $img }}')">Image {{ $n + 1 }}</a><br>
                @endif
            @endif
        @endforeach
    @endif
</td>

                <td>{{$quality->channel->name}}</td>
                <td>{{$quality->supplier_inv_no}}</td>
                <td>{{$quality->quantity}}</td>
                
                <td>{{date('d M, Y',strtotime($quality->date))}}</td>
                <td>{{$quality->remarks}}</td>
                <td>
                  @if($quality->status!=0)
                 
                  @endif
                  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="{{($quality->status!=0)?'Can\'t delete! Reject/Repair under process.':'Delete'}}">
                    <button class="btn btn-danger" data-bs-toggle="modal" onclick="deleteModal('{{$quality->id}}')" {{ $quality->status>0?'disabled':'' }} >
                      <i class="fa fa-trash"></i>
                    </button>
                  </span>
				  
				  <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                    <a class="btn btn-primary" href="{{url('/quality/view')}}/{{$quality->id}}" style="color:#fff;" >
                      <i class="fa fa-edit"></i>
                    </a>
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
    <div class="modal fade" id="deleteRejectRepair" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Delete Confirmation</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
          </div>
          <form id="deleteRejectRepairForm" method="POST" action="">
          @csrf  
            <div class="modal-body">
              <p>Are You sure you want to Delete this?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
              <button type="submit" id="deleteRejectRepairForm" class="btn btn-danger">Yes</button>
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


<!-- Script start for quality complete/delete -->
<script type="text/javascript">
  
  $(function () {
    $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function deleteModal(id){
    $('#deleteRejectRepair').modal('show');
    $('#deleteRejectRepairForm').attr('action', "{{ url('/quality/delete') }}" + '/' +id);
  } 
 
  function markComplete(id){
    location.href = "{{ url('/quality/complete') }}" + '/' +id;
  } 
  
  function fetchImage(obj, product) {
    if ($(obj).html() == "") {
        var imgBase = "{{ rtrim(Storage::disk('s3')->url('stock/quality'), '/') }}";
        var img = imgBase + "/" + product;
        $(obj).html(
            '<a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#viewImage" onclick="updateSrc(\'' + product + '\')">' +
            '<img class="img-thumbnail img-fluid product-img-100" src="' + img + '" alt="No Image" />' +
            '</a>'
        );
    }
}

function updateSrc(product) {
    var imgBase = "{{ rtrim(Storage::disk('s3')->url('stock/quality'), '/') }}";
    var img = imgBase + "/" + product;
    $("#bigProImage").attr('src', img);
    $("#viewImage").find("h4").html(product);
}

    
</script>
<!-- Script end -->

@endsection
