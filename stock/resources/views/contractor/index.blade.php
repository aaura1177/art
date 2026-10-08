@extends('layouts.app')

@section('content')

      <div class="row mx-3 my-2">
        <h2>Contractor List</h2>
        <div class="col">
			<a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="#" data-bs-target="#myContractorBill" data-bs-toggle="modal">Contractor Bill Download</a>
			<a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/contractor/create')}}">Add contractor</a>
		</div>
      </div>
      
      <div class="card mb-3">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Contractor Name</th>
                  <th>Contact Person</th>
                  <th>Phone</th>
                  <th>Options</th>
                </tr>
              </thead>
              <tbody>
              	@if(isset($contractor)) @foreach($contractor as $key => $cont)
                <tr>
                  <td>{{++$key}}</td>
                  <td>{{$cont->c_name}}</td>
                  <td>{{$cont->name}}</td>
                  <td>{{$cont->phone1}}</td>
                  <td>
                     <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                        <button class="btn btn-info" onclick="updatecontractor('{{$cont->id}}')">
            							<i class="fa fa-edit"></i>
            						</button>
                      </span>

                      @php
                      $contractorRelationCount = $cont->allocation->count();
                      @endphp
                      @if($contractorRelationCount == 0)
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                        <button class="btn btn-danger" data-bs-toggle="modal" onclick="deleteModal('{{$cont->id}}')">
                          <i class="fa fa-trash"></i>
                        </button>
                      </span>
                      @endif
                  </td>
                </tr>
                @endforeach @endif
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- MODAL FOR DELETE -->
      <div class="modal fade" id="deleteContractor" role="dialog">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">Delete Confirmation</h4>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
            </div>
            <form id="deleteContractorForm" method="POST" action="">
            @csrf  
              <div class="modal-body">
                <p>Are You sure you want to Delete this?</p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
                <button type="submit" id="deleteContractorForm" class="btn btn-danger">Yes</button>
              </div>
            </form>  
          </div>
        </div>
      </div>
	  
	  <!-- MODAL FOR Contractor Bill -->
    <div class="modal fade" id="myContractorBill" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Contractor Bill Generation</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
          </div>
          <form method="POST" action="{{ url('/contractor/downloadBill') }}">
          @csrf
            <div class="modal-body">
                <div class="row">
						
                   <div class="col-6">
						<label>Select Month</label>
                      <select class="selectpicker" name="month" required>
						<option value="" disabled>Select Month</option>
						<option value="<?php echo date('m-Y',strtotime("-2 months")); ?>"><?php echo date('F-Y',strtotime("-2 months")); ?></option>
						<option value="<?php echo date('m-Y',strtotime("last day of previous month")); ?>"><?php echo date('F-Y',strtotime("last day of previous month")); ?></option>
						<option value="<?php echo date('m-Y'); ?>"><?php echo date('F-Y'); ?></option>
					  </select>
                  </div>
                  <div class="col-6">
						<label>Select Contractors</label>
                      <select class="selectpicker" data-live-search="true" name="contractor" required>
						<option value="" disabled>Select Contractors</option>
						  @if(isset($contractor)) @foreach($contractor as $key => $cont)
							<option value="{{$cont->id}}">
							{{$cont->name}}
							</option>
						  @endforeach @endif
					  </select>
                  </div>
                </div>
				
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-success">Download</button>
            </div>
          </form>
        </div>
      </div>
    </div>
@endsection

@section('footer')


<!-- Script start for Delete/View button -->
<script type="text/javascript">
  
  $(function (){
    $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function deleteModal(id){
    $('#deleteContractor').modal('show');
    $('#deleteContractorForm').attr('action', "{{ url('/contractor/delete') }}" + '/' +id);
  } 

  function updatecontractor(id){
    location.href = "{{ url('/contractor/view') }}" + '/' +id;
  } 

</script>
<!-- Script End -->

@endsection
