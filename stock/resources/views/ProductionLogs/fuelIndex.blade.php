@extends('layouts.app')

@section('content')

<style>
    .table-container {
      width: 100%;
      overflow-x: auto;
      position: relative;
    }
    table {
      min-width: 1000px; /* Adjust based on your need */
      border-collapse: separate;
      border-spacing: 0;
    }
    th, td {
      white-space: nowrap;
    }
    /* Fix the first two columns */
    .table-fixed th:first-child,
    .table-fixed td:first-child,
    .table-fixed th:nth-child(2),
    .table-fixed td:nth-child(2) {
      position: sticky;
      background-color: #fff;
      z-index: 2;
    }
    .table-fixed th:first-child,
    .table-fixed td:first-child {
      left: 0;
      z-index: 3; /* above second column */
    }
    .table-fixed th:nth-child(2),
    .table-fixed td:nth-child(2) {
      left: 56px; /* width of first column */
    }

    .table-fixed th:first-child,.table-fixed th:nth-child(2)
    {
        background-color: #212529; /* Bootstrap dark color */
        color: white;
    }

    .table-fixed tfoot td:first-child,
    .table-fixed tfoot td:nth-child(2) {
        position: sticky;
        background-color: #212529; /* Bootstrap dark color */
        color: white;
        z-index: 2;
    }
    .table-fixed tfoot td:first-child {
        left: 0;
        z-index: 3; /* above second column */
    }
    .table-fixed tfoot td:nth-child(2) {
        left: 56px; /* width of first column */
    }

  </style>

    <div class="row mx-1 my-2">
        <div class="d-flex justify-content-between align-items-center w-100">
            <h2 class="mb-0">Production Logs</h2>
            <a class="btn btn-info float-end" style="color: #fff;" href="{{ route('production.logs.create')}}"><i class="fa fa-plus"></i> Add New </a> 
        </div>

    </div>

   
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Fuel from Supplier factory to Artisan warehouse </h5>
            <div class="btn-group" role="group" aria-label="Basic mixed styles example">
                <a href="{{ route('production.logs.power-consumption') }}" class="btn btn-success">Electricity Consumption <i class="fa fa-arrow-right"></i></a>
            </div>
        </div>

        <div class="card-body">
                 
            <!-- Filter For Monthly Records -->
            <div class="row align-items-end mb-3 pt-3">
                <div class="col-md-8">
                    <form class="row g-2">

                        <!-- <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Keyword" class="form-control"/>
                        </div> -->

                        <div class="col-md-3">
                            <label for="inputtypelabel" class="form-label">From</label>
                            <div class="input-group">
                                <input type="date" name="date-from" id="date-from" value="{{ request('date-from') }}" class="form-control ui-autocomplete-input"/>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label for="inputtypelabel" class="form-label">To</label>
                            <div class="input-group">
                                <input type="date" name="date-to" id="date-to" value="{{ request('date-to') }}" class="form-control ui-autocomplete-input"/>
                            </div>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-info me-2"><i class="fa fa-filter"></i> Filter</button>
                            <a href="{{ route('production.logs.fuel-consumption') }}" class="btn btn-danger me-2">
                            <i class="fa fa-retweet" aria-hidden="true"></i> Reset</a>
                        </div>
                    </form>
                </div>

            </div>
        
            <!-- End Filter For monthly Records -->
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Sr.No</th>
                        <th>Month-Year</th>
                        <th>Supplier Name</th>
                        <th>Vehicle Type</th>
                        <th>Fuel Type</th>
                        <th>Distance Travelled</th>
                        <th>No. of Rounds </th>
                        <th>Documents</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach(@$records as $record)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ date("M Y", strtotime($record->month_year)) }}</td>
                            <td>{{ $record->supplierInfo->c_name }}</td>
                            <td>{{ $record->vehicle_type }}</td>
                            <td>{{ $record->fuel_type }}</td>
                            <td>{{ $record->distance_travelled }}</td>
                            <td>{{ $record->number_of_rounds }}</td>
                            <td>
                                @if(!empty($record->chalan_url))
                                    <a href="{{ env('AWS_URL').'/'.$record->chalan_url }}" download class="btn btn-warning btn-sm">
                                        Chalan <i class="fa fa-file-download"></i>
                                    </a>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('production.logs.edit', $record->id) }}" class="btn btn-primary btn-sm">Edit</a>
                                <form action="{{ route('production.logs.delete', $record->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    @if(@$records->isEmpty())
                        <tr>
                            <td colspan="9" class="text-center">No records found</td>
                        </tr>
                    @endif
                </tbody>
                
            </table>
            <div class="d-flex justify-content-center mt-3">
                {{ @$records->links() }}
            </div>
        
        </div>
    </div>
@endsection
@section('footer')

@endsection