@extends('layouts.app')

@section('content')

    <div class="row mx-1 my-2">
        <div class="d-flex justify-content-between align-items-center w-100">
            <h2>Sustainability Stage-3</h2>
            <a class="btn btn-info float-end" style="color: #fff;" href="{{ route('sustainability.stage3.create')}}"> 
            <i class="fa fa-plus"></i> Add New </a> 
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Electricity Consumption</h5>
            <div class="btn-group" role="group" aria-label="Basic mixed styles example">
                <a href="{{ route('sustainability.stage3.miscellaneous.index') }}" class="btn btn-warning me-2">Miscellaneous </a>
                <a href="{{ route('sustainability.stage3.employee.index') }}" class="btn btn-info">Employee Travelling Consumption </a>
            </div>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ request('tab') == 'monthly' || !request('tab') ? 'active' : '' }}" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly" type="button" role="tab" aria-controls="monthly" aria-selected="{{ request('tab') == 'monthly' || !request('tab') ? 'true' : 'false' }}">Monthly Power Consumption</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ request('tab') == 'yearly' ? 'active' : '' }}" id="yearly-tab" data-bs-toggle="tab" data-bs-target="#yearly" type="button" role="tab" aria-controls="yearly" aria-selected="{{ request('tab') == 'yearly' ? 'true' : 'false' }}">Yearly Power Consumption</button>
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
                                    <a href="{{ route('sustainability.stage3.power-consumption') }}" class="btn btn-danger me-2">
                                    <i class="fa fa-retweet" aria-hidden="true"></i> Reset</a>
                                </div>
                            </form>
                        </div>

                        @if(!empty(@$records[0]))
                        <div class="col-md-4 d-flex justify-content-end">
                            <form action="{{ route('sustainability.stage3.power-consumption.export') }}" method="post" class="d-flex">
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
                                <th>Office</th>
                                <th>Power Consumed (KWh)</th>
                                <th>Carbon Emission</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(@$records as $record)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ date("M - Y", strtotime($record->month_year)) }}</td>
                                    <td>{{ $record->office_type }}</td>
                                    <td>{{ $record->power_consumed }}</td>
                                    <td>{{ $record->carbon_emission }}</td>
                                    <td>
                                        <a href="{{ route('sustainability.stage3.edit', $record->id) }}" class="btn btn-primary btn-sm">Edit</a>
                                        <form action="{{ route('sustainability.stage3.delete', $record->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this record?');">
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
                                <td colspan="3" class="text-center"><strong>Total</strong></td>
                                <td><strong>{{ $records->sum('power_consumed') }}</strong></td>
                                <td><strong>{{ $records->sum('carbon_emission') }}</strong></td>
                                <td></td>
                            </tr>
                            <!-- Avg total per month -->
                            <!-- <tr>
                                <td colspan="2" class="text-center"><strong>Average</strong></td>
                                <td><strong>{{ round($records->sum('power_consumed') / 12, 5) }}</strong></td>
                                <td><strong>{{ round(($records->sum('power_consumed') * ($sustainabilityVariable->carbon_emission_factor_per_kwh)) / 12, 5) }}</strong></td>
                            </tr> -->
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
                                    <a href="{{ route('sustainability.stage3.power-consumption') }}" class="btn btn-danger me-2">
                                    <i class="fa fa-retweet" aria-hidden="true"></i> Reset</a>
                                </div>
                            </form>
                        </div>

                        @if(!empty(@$records[0]))
                            <div class="col-md-4 d-flex justify-content-end">
                                <form action="{{ route('sustainability.stage3.power-consumption.export') }}" method="post" class="d-flex">
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
                                <th style="width: 150px;">Factory</th>
                                <th style="width: 150px;">Office </th>
                                <th style="width: 150px;">Uk Office </th>
                                <th>Total Consumption (KWh)</th>
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
                                            'Factory' => $monthRecords->where('office_type', 'Factory')->sum('power_consumed'),
                                            'Office' => $monthRecords->where('office_type', 'Office')->sum('power_consumed'),
                                            'Uk Office' => $monthRecords->where('office_type', 'Uk Office')->sum('power_consumed'),
                                        ];
                                        $total_power = array_sum($totals);
                                        $total_emission = $monthRecords->sum('carbon_emission');
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ date('M - Y', strtotime($month . '-01')) }}</td>
                                        <td>{{ $totals['Factory'] }}</td>
                                        <td>{{ $totals['Office'] }}</td>
                                        <td>{{ $totals['Uk Office'] }}</td>
                                        <td>{{ $total_power }}</td>
                                        <td>{{ $total_emission }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No records found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <td colspan="2" class="text-center"><strong>Total</strong></td>
                                    <td><strong>{{ $records->where('office_type', 'Factory')->sum('power_consumed') }}</strong></td>
                                    <td><strong>{{ $records->where('office_type', 'Office')->sum('power_consumed') }}</strong></td>
                                    <td><strong>{{ $records->where('office_type', 'Uk Office')->sum('power_consumed') }}</strong></td>
                                    <td><strong>{{ $records->sum('power_consumed') }}</strong></td>
                                    <td><strong>{{ $records->sum('carbon_emission') }}</strong></td>
                                </tr>
                                <!-- Avg total per month -->
                                <!-- <tr>
                                    <td colspan="2" class="text-center"><strong>Average</strong></td>
                                    <td><strong>{{ round($records->where('office_type', 'Factory')->sum('power_consumed') / 12, 5) }}
                                    </strong></td>
                                    <td><strong>{{ round($records->where('office_type', 'Office')->sum('power_consumed') / 12, 5) }}</strong></td>
                                    <td><strong>{{ round($records->where('office_type', 'Uk Office')->sum('power_consumed') / 12, 5) }}</strong></td>
                                    <td><strong>{{ round($records->sum('power_consumed') / 12, 5) }}</strong></td>
                                    <td><strong>{{ round(($records->sum('power_consumed') * ($sustainabilityVariable->carbon_emission_factor_per_kwh ?? 0)) / 12, 5) }}</strong></td>
                                </tr> -->
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