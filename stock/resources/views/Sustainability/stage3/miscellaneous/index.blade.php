@extends('layouts.app')

@section('content')

    <div class="row mx-1 my-2">
        <div class="d-flex justify-content-between align-items-center w-100">
            <h2>Sustainability Stage-3 Miscellaneous</h2>
            <a class="btn btn-info float-end" style="color: #fff;" href="{{ route('sustainability.stage3.miscellaneous.create')}}"><i class="fa fa-plus"></i> Add New </a> 
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Fuel Consumption During Work Visit </h5>
            <div class="btn-group" role="group" aria-label="Basic mixed styles example">
                <a href="{{ route('sustainability.stage3.power-consumption') }}" class="btn btn-warning me-2">Power Consumption </a>
                <a href="{{ route('sustainability.stage3.employee.index') }}" class="btn btn-info">Employee Travelling Consumption </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Filter For Yearly Records -->
            <div class="row align-items-end mb-3 pt-3">
                <div class="col-md-8">
                    <form class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Keyword" class="form-control"/>
                        </div>

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
                            <a href="{{ route('sustainability.stage3.miscellaneous.index') }}" class="btn btn-danger me-2">
                            <i class="fa fa-retweet" aria-hidden="true"></i> Reset</a>
                        </div>
                    </form>
                </div>

                @if(!empty(@$records[0]))
                    <div class="col-md-4 d-flex justify-content-end">
                        <form action="{{ route('sustainability.stage3.miscellaneous.export') }}" method="post" class="d-flex">
                            @csrf
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="date-from" value="{{ request('date-from') }}">
                            <input type="hidden" name="date-to" value="{{ request('date-to') }}">
                            <button type="submit" class="btn btn-success"> <i class="fa fa-file-excel"></i> Export</button>
                        </form>
                    </div>
                @endif
            </div>
            <!-- End Filter  Records -->

            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Sr.No</th>
                        <th>Month/Year</th>
                        <th>Person Name</th>
                        <th>Travel Mode</th>
                        <th>Origin</th>
                        <th>Destination</th>
                        <th>Distance Traveled</th>
                        <th>Carbon Emission</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach(@$records as $record)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ date("M Y", strtotime($record->month_year)) }}</td>
                        <td>{{ $record->user_name }}</td>
                        <td>{{ $record->travel_mode }}</td>
                        <td>{{ $record->origin_name }}</td>
                        <td>{{ $record->destination_name }}</td>
                        <td>{{ $record->distance_travelled }}</td>
                        <td>{{ $record->carbon_emission }}</td>
                        <td>
                            <a href="{{ route('sustainability.stage3.miscellaneous.edit', $record->id) }}" class="btn btn-primary btn-sm">Edit</a>
                            <form action="{{ route('sustainability.stage3.miscellaneous.delete', $record->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this record?');">
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
                <tfoot class="table-dark">
                    <tr>
                        <th colspan="6" class="text-end">Total:</th>
                        <th><strong>{{ @$records->sum('distance_travelled') }}</strong></th>
                        <th>{{ @$records->sum('distance_travelled') * ($sustainabilityVariable->carbon_emission_rate)?? "" }}</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
            <div class="d-flex justify-content-center mt-3">
                {{ @$records->links() }}
            </div>
            
        </div>
    </div>
@endsection
@section('footer')

@endsection