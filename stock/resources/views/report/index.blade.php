@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Pending Invoices</h2>

    </div>
    
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>#</th>
                <th>Invoice No.</th>
                <th>Buyer Name</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
            	@if(isset($pendinginv)) @foreach($pendinginv as $key => $pendinginv)
              <tr>
                <td>{{++$key}}</td>
                <td>{{$pendinginv->id}}</td>
                <td>{{$pendinginv->buyer->name}}</td>
                <td>{{$pendinginv->date}}</td>
              </tr>
              @endforeach @endif
            </tbody>
          </table>
        </div>
      </div>

        <!-- MODAL FOR DATE -->
        <div class="modal fade" id="mydateModal" role="dialog">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h4 class="modal-title">Enter Date</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>  
              </div>
              <div class="modal-body">
              <form>
                <input type="date" name="fromstartDate" id="fsd" value="" />
                <input type="date" name="fromendDate" id="fed" value="" />
              </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
                <button onclick="productDate()" class="btn btn-danger">Yes</button>
              </div>
            </div>
          </div>
        </div>
    </div>
@endsection

@section('footer')

@endsection