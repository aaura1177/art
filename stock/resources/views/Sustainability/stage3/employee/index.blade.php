@extends('layouts.app')

@section('content')

    <div class="row mx-1 my-2">
        <div class="d-flex justify-content-between align-items-center w-100">
            <h2 class="mb-0">Sustainability Stage-3 Employee</h2>
            <a class="btn btn-info float-end" style="color: #fff;" href="{{ route('sustainability.stage3.employee.create')}}"><i class="fa fa-plus"></i> Add New </a> 
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Employee Transport Details</h5>
            <div class="btn-group" role="group" aria-label="Basic mixed styles example">
                <a href="{{ route('sustainability.stage3.miscellaneous.index') }}" class="btn btn-warning me-2">Miscellaneous </a>
                <a href="{{ route('sustainability.stage3.power-consumption') }}" class="btn btn-info">Power Consumption </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Filter For Monthly Records -->
            <div class="row align-items-end mb-3 pt-3">
                <div class="col-md-8">
                    <form class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Keyword" class="form-control"/>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Month</label>
                            <select name="month" id="month" class="form-control">
                                <option value="">All</option>
                                @for($i=1; $i<=12; $i++)
                                    <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                                        {{ date("F", mktime(0, 0, 0, $i, 1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Year</label>
                            <select name="year" id="year" class="form-control">
                                <option value="">All</option>
                                @for($i=2022; $i<=date('Y'); $i++)
                                    <option value="{{ $i }}" {{ request('year') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-info me-2"><i class="fa fa-filter"></i> Filter</button>
                            <a href="{{ route('sustainability.stage3.employee.index') }}" class="btn btn-danger me-2">
                            <i class="fa fa-retweet" aria-hidden="true"></i> Reset</a>
                        </div>
                    </form>
                </div>

                @if(!empty(@$records[0]))
                    <div class="col-md-4 d-flex justify-content-end">
                        <form action="{{ route('sustainability.stage3.employee.export') }}" method="post" class="d-flex">
                            @csrf
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <input type="hidden" name="month" value="{{ request('month') }}">
                            <input type="hidden" name="year" value="{{ request('year') }}">
                            <button type="submit" class="btn btn-success"> <i class="fa fa-file-excel"></i> Export</button>
                        </form>
                    </div>
                @endif
            </div>
                
            <!-- End Filter For monthly Records -->
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Sr.No</th>
                        <th>Month-Year</th>
                        <th>User Name</th>
                        <th>Vehicle Type </th>
                        <th>Fuel Type</th>
                        <th>Distance Travelled (One Way)</th>
                        <th>No. of Rounds </th>
                        <th>Days </th>
                        <th>Total Distance Travelled (Km)</th>
                        <th>Carbon Emission</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(@$records as $record)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ date("M - Y", strtotime($record->month_year)) }}</td>
                            <td>{{ $record->user_name }}</td>
                            <td>{{ $record->vehicle_type }}</td>
                            <td>{{ $record->fuel_type }}</td>
                            <td>{{ $record->distance_travelled }}</td>
                            <td>{{ $record->number_of_rounds }}</td>
                            <td>{{ $record->days }}</td>
                            <td>{{ $record->distance_travelled * $record->number_of_rounds * $record->days }}</td>
                            <td>{{ $record->carbon_emission }}</td>
                            <td>
                                <a href="{{ route('sustainability.stage3.employee.edit', $record->id) }}" class="btn btn-primary btn-sm">Edit</a>
                                <form action="{{ route('sustainability.stage3.employee.delete', $record->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    @if(@$records->isEmpty())
                        <tr>
                            <td colspan="11" class="text-center">No records found</td>
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