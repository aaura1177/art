@extends('layouts.app')

@section('content')

    <div class="row mx-1 my-2">
        <div class="d-flex justify-content-between align-items-center w-100">
        <h2>Sustainability Stage-6</h2>
            <a class="btn btn-info float-end" style="color: #fff;" href="{{ route('sustainability.stage6.create')}}"><i class="fa fa-plus"></i> Add New </a> 
        </div>
    </div>


    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Last Mile Distribution Emission</h5>
            
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ request('tab') == 'monthly' || !request('tab') ? 'active' : '' }}" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly" type="button" role="tab" aria-controls="monthly" aria-selected="{{ request('tab') == 'monthly' || !request('tab') ? 'true' : 'false' }}">Monthly</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ request('tab') == 'yearly' ? 'active' : '' }}" id="yearly-tab" data-bs-toggle="tab" data-bs-target="#yearly" type="button" role="tab" aria-controls="yearly" aria-selected="{{ request('tab') == 'yearly' ? 'true' : 'false' }}">Yearly</button>
                </li>
            </ul>
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade {{ request('tab') == 'monthly' || !request('tab') ? 'show active' : '' }}" id="monthly" role="tabpanel" aria-labelledby="monthly-tab">
                    <!-- Filter For Monthly Records -->
                    <div class="row align-items-end mb-3 pt-3">
                        <div class="col-md-8">
                            <form class="row g-2">
                                <input type="hidden" name="tab" value="monthly">

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
                                    <a href="{{ route('sustainability.stage6.index') }}" class="btn btn-danger me-2">
                                    <i class="fa fa-retweet" aria-hidden="true"></i> Reset</a>
                                </div>
                            </form>
                        </div>

                        @if(!empty(@$records[0]))
                            <div class="col-md-4 d-flex justify-content-end">
                                <form action="{{ route('sustainability.stage6.export') }}" method="post" class="d-flex">
                                    @csrf
                                    <input type="hidden" name="tab" value="monthly">
                                    <input type="hidden" name="search" value="{{ request('search') }}">
                                    <input type="hidden" name="month" value="{{ request('month') }}">
                                    <input type="hidden" name="year" value="{{ request('year') }}">
                                    <button type="submit" class="btn btn-success"> <i class="fa fa-file-excel"></i> Export</button>
                                </form>
                            </div>
                        @endif
                    </div>
                    <!-- End Filter For Monthly Records -->

                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Sr.No</th>
                                <th>Month-Year</th>
                                <th>Location</th>
                                <th>Parcel Delivered</th>
                                <th>Carbon Emission</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(@$records as $record)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ date("M - Y", strtotime($record->month_year)) }}</td>
                                    <td>{{ $record->location }}</td>
                                    <td class="d-flex align-items-center">
                                        <span class="me-2">{{ $record->parcel_delivered }}</span>
                                        <form action="{{ route('sustainability.stage6.export-parcel-deliverd') }}" method="post">
                                            @csrf
                                            <input type="hidden" name="month_year" value="{{ date('Y-m', strtotime($record->month_year)) }}">
                                            <input type="hidden" name="location" value="{{ $record->location }}">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fa fa-file-excel"></i>
                                            </button>
                                        </form>
                                    </td>

                                    <td>{{ $record->carbon_emission }}</td>
                                    <td>
                                        <a href="{{ route('sustainability.stage6.edit', $record->id) }}" class="btn btn-primary btn-sm">Edit</a>
                                        <form action="{{ route('sustainability.stage6.delete', $record->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            @if(@$records->isEmpty())
                                <tr>
                                    <td colspan="6" class="text-center">No records found</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot class="table-dark">
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td class="fw-bold">{{ $records->sum('parcel_delivered') }}</td>
                                <td class="fw-bold">{{ $records->sum('carbon_emission') }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="d-flex justify-content-center mt-3">
                        {{ @$records->links() }}
                    </div>
                </div>
                <div class="tab-pane fade {{ request('tab') == 'yearly' ? 'show active' : '' }}" id="yearly" role="tabpanel" aria-labelledby="yearly-tab">
                    <!-- Filter For Yearly Records -->
                    <div class="row align-items-end mb-3 pt-3">
                        <div class="col-md-8">
                            <form class="row g-2">
                                <input type="hidden" name="tab" value="yearly">
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
                                    <a href="{{ route('sustainability.stage6.index') }}" class="btn btn-danger me-2">
                                    <i class="fa fa-retweet" aria-hidden="true"></i> Reset</a>
                                </div>
                            </form>
                        </div>

                        @if(!empty(@$records[0]))
                        <div class="col-md-4 d-flex justify-content-end">
                            <form action="{{ route('sustainability.stage6.export') }}" method="post" class="d-flex">
                                @csrf
                                <input type="hidden" name="tab" value="yearly">
                                <input type="hidden" name="search" value="{{ request('search') }}">
                                <input type="hidden" name="date-from" value="{{ request('date-from') }}">
                                <input type="hidden" name="date-to" value="{{ request('date-to') }}">
                                <button type="submit" class="btn btn-success"> <i class="fa fa-file-excel"></i> Export</button>
                            </form>
                        </div>
                        @endif
                    </div>
                    <!-- End Filter For Yearly Records -->
                  
                    <div class="table-container">
                        <table class="table table-bordered table-striped table-hover table-fixed">
                            <thead class="table-dark">
                            <tr>
                                <th style="width: 100px;">Sr.No</th>
                                <th style="width: 150px;">Month-Year</th>
                                <th style="width: 150px;">IN</th>
                                <th style="width: 150px;">UK</th>
                                <th style="width: 150px;">US</th>
                                <th style="width: 150px;">EU</th>
                                <th style="width: 150px;">CA</th>
                                <th>Total Parcel Delivered</th>
                                <th>Carbon Emission</th>
                            </tr>
                            </thead>
                            <tbody>
                                @php
                                    $groupedRecords = $records->groupBy(function($item) {
                                        return date('Y-m', strtotime($item->month_year)); // Group by Year-Month
                                    });
                                @endphp
                                @forelse(@$groupedRecords as $month => $monthRecords)
                                    @php
                                        $totals = [
                                            'IN' => $monthRecords->where('location', 'IN')->sum('parcel_delivered'),
                                            'UK' => $monthRecords->where('location', 'UK')->sum('parcel_delivered'),
                                            'US' => $monthRecords->where('location', 'US')->sum('parcel_delivered'),
                                            'EU' => $monthRecords->where('location', 'EU')->sum('parcel_delivered'),
                                            'CA' => $monthRecords->where('location', 'CA')->sum('parcel_delivered'),
                                        ];

                                        $total_carbon_emission = [
                                            'IN' => $monthRecords->where('location', 'IN')->sum('carbon_emission'),
                                            'UK' => $monthRecords->where('location', 'UK')->sum('carbon_emission'),
                                            'US' => $monthRecords->where('location', 'US')->sum('carbon_emission'),
                                            'EU' => $monthRecords->where('location', 'EU')->sum('carbon_emission'),
                                            'CA' => $monthRecords->where('location', 'CA')->sum('carbon_emission'),
                                        ];
                                        
                                        $total_parcel_delivered = array_sum($totals);
                                        $total_emission = array_sum($total_carbon_emission);
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ date('M - Y', strtotime($month . '-01')) }}</td>
                                        <td>{{ $totals['IN'] }}</td>
                                        <td>{{ $totals['UK'] }}</td>
                                        <td>{{ $totals['US'] }}</td>
                                        <td>{{ $totals['EU'] }}</td>
                                        <td>{{ $totals['CA'] }}</td>
                                        <td>{{ $total_parcel_delivered }}</td>
                                        <td>{{ $total_emission }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No records found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <td colspan="2" class="text-end"><strong>Total:</strong></td>
                                    <td class="fw-bold">{{ $records->where('location', 'IN')->sum('parcel_delivered') }}</td>
                                    <td class="fw-bold">{{ $records->where('location', 'UK')->sum('parcel_delivered') }}</td>
                                    <td class="fw-bold">{{ $records->where('location', 'US')->sum('parcel_delivered') }}</td>
                                    <td class="fw-bold">{{ $records->where('location', 'EU')->sum('parcel_delivered') }}</td>
                                    <td class="fw-bold">{{ $records->where('location', 'CA')->sum('parcel_delivered') }}</td>
                                    <td class="fw-bold">{{ $records->sum('parcel_delivered') }}</td>
                                    <td class="fw-bold">{{ $records->sum('carbon_emission') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                        <div class="d-flex justify-content-center mt-3">
                            {{ @$records->links() }}
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
   

@endsection
@section('footer')

@endsection