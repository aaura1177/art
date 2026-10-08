@extends('layouts.app')

@section('content')
<style type="text/css">
  .ck-editor__editable_inline {
    min-height: 300px;
 }
</style>
    <div class="row mx-3 my-2">
      <h2>Credit Notes</h2>

      @hasrole('admin')
      <div class="col">
        <a class="btn btn-primary float-end" style="color: #fff;" href="{{ url('/invoice/credit_note_add')}}">Add Credit Note</a>
      </div>
      @endhasrole

    </div>

    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>Credit Note No.</th>
                <th>Inv No.</th>
                <th>Total Quantity</th>
                <th>Total Amount</th>
                <th>Created At</th>
                <th style="min-width:120px;">Options</th>
              </tr>
            </thead>
            <tbody>
              @if(isset($credit_notes)) @foreach($credit_notes as $key => $credit_note)
              <tr>
                <td>{{$credit_note->num}}</td>
                <td>{{$credit_note->invoice->invoiceno}}</td>
                <td>{{$credit_note->total_quantity}}</td>
                <td>Rs. {{$credit_note->total_amount}}</td>
                <td>Rs. {{$credit_note->created_at}}</td>
                <td>
                    
                    @hasrole('admin')
                    {{-- <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                      <a href="{{ URL::to('/invoice/credit_note_edit/'.$credit_note->id)}}" class="btn btn-info" >
                        <i class="fa fa-edit"></i>
                      </a>
                    </span> --}}
                    @endhasrole
                    
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                       <a class="btn btn-primary" style="color: #fff;" href="{{ URL::to('/invoice/credit_note_modal/'.$credit_note->id)}}" target="_blank"><i class="fa fa-eye"></i></a>
                    </span>
                
                    @hasrole('admin')
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                      <button class="btn btn-danger" data-bs-toggle="modal" onclick="deleteModal('{{$credit_note->id}}')">
                        <i class="fa fa-trash"></i>
                      </button>
                    </span>
                    @endhasrole
                    
                  </td>

                  
              </tr>
              @endforeach @endif
            </tbody>
          </table>
        </div>
      </div>
    </div>


    <!-- MODAL FOR DELETE -->
    <div class="modal fade" id="deleteInvoice" role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Delete Confirmation</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="deleteInvoiceForm" method="POST" action="">
          @csrf
            <div class="modal-body">
              <p>Are You sure you want to Delete this?</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
              <button type="submit" id="deleteInvoiceForm" class="btn btn-danger">Yes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

@endsection

@section('footer')


<!-- Script start Invoice delete -->
<script type="text/javascript">

  function deleteModal(id){
    $('#deleteInvoice').modal('show');
    $('#deleteInvoiceForm').attr('action', "{{ url('/invoice/credit_note_delete') }}" + '/' +id);
  }

  function updateinvoice(id){
     location.href = "{{ url('/invoice/credit_note_edit') }}" + '/' +id;
  }

 

  
  function deleteRow(ref) {
    $(ref).parent().parent().remove();

  }

  

  $(function(){
	  var table = $("#dataTable").DataTable();
		
		
  });

</script>
<!-- Script End -->

<script>
  ClassicEditor
      .create( document.querySelector( '#editor' ) )
      .catch( error => {
          console.error( error );
      } );
</script>
@endsection
