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
            <h2 class="mb-0">Sustainability Stage-2</h2>
            <a class="btn btn-info float-end" style="color: #fff;" href="{{ route('sustainability.stage2.create')}}"><i class="fa fa-plus"></i> Add New </a> 
        </div>

    </div>

    @php
    $present_supplier_ids = $yearlyRecords->pluck('supplier_id')->unique();

    // Pre-compute supplier totals (yearly tab)
    $supplierTotals = [];
    foreach ($suppliers as $supplier) {
        if ($present_supplier_ids->contains($supplier->id)) {
            $supplierRecords = $yearlyRecords->where('supplier_id', $supplier->id);
            $totalDistance = $supplierRecords->sum(function($r) {
                return $r->distance_travelled * $r->number_of_rounds;
            });
            $supplierTotals[$supplier->id] = $totalDistance;
        }
    }

    $overallTotalDistance = $yearlyRecords->sum(function($r) {
        return $r->distance_travelled * $r->number_of_rounds;
    });

    $overallTotalEmission = $yearlyRecords->sum('carbon_emission');
    @endphp

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Fuel from Supplier factory to Artisan warehouse </h5>
            <div class="btn-group" role="group" aria-label="Basic mixed styles example">
                <a href="{{ route('sustainability.stage2.power-consumption') }}" class="btn btn-success">Electricity Consumption <i class="fa fa-arrow-right"></i></a>
            </div>
        </div>

        <div class="card-body">
            <ul class="nav nav-tabs" id="myTab1" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ request('tab') == 'monthly' || !request('tab') ? 'active' : '' }}" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly" type="button" role="tab" aria-controls="monthly" aria-selected="{{ request('tab') == 'monthly' || !request('tab') ? 'true' : 'false' }}">Monthly Fuel Consumtion</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ request('tab') == 'yearly' ? 'active' : '' }}" id="yearly-tab" data-bs-toggle="tab" data-bs-target="#yearly" type="button" role="tab" aria-controls="yearly" aria-selected="{{ request('tab') == 'yearly' ? 'true' : 'false' }}">Yearly Fuel Consumption</button>
                </li>
            </ul>
            <div class="tab-content" id="myTab1Content">
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
                                    <a href="{{ route('sustainability.stage2.fuel-consumption') }}" class="btn btn-danger me-2">
                                    <i class="fa fa-retweet" aria-hidden="true"></i> Reset</a>
                                </div>
                            </form>
                        </div>

                        @if(!empty(@$monthlyRecords[0]))
                            <div class="col-md-4 d-flex justify-content-end">
                                <form action="{{ route('sustainability.stage2.fuel-consumption.export') }}" method="post" class="d-flex">
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
                
                    <!-- End Filter For monthly Records -->
                    @if(!empty($monthlyRecords->financialYearLabel))
                        <p class="mb-2"><strong>Financial Year {{ $monthlyRecords->financialYearLabel }}</strong></p>
                    @endif
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
                                <th>Carbon Emission</th>
                                <th>Created By</th>
                                <th>Documents</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach(@$monthlyRecords as $record)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ date("M - Y", strtotime($record->month_year)) }}</td>
                                    <td>{{ $record->supplierInfo->c_name }}</td>
                                    <td>{{ $record->vehicle_type }}</td>
                                    <td>{{ $record->fuel_type }}</td>
                                    <td>{{ $record->distance_travelled }}</td>
                                    <td>{{ $record->number_of_rounds }}</td>
                                    <td>{{ $record->carbon_emission }}</td>
                                    <td><span class="{{ $record->created_by == 'Supplier' ? 'text-danger' : 'text-primary' }}">{{ $record->created_by }} </span></td>
                                    <td>
                                        @if(!empty($record->chalan_url))
                                            <a href="{{ env('AWS_URL').'/'.$record->chalan_url }}" download class="btn btn-warning btn-sm">
                                                Chalan <i class="fa fa-file-download"></i>
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('sustainability.stage2.edit', $record->id) }}" class="btn btn-primary btn-sm">Edit</a>
                                        <form action="{{ route('sustainability.stage2.delete', $record->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            @if(@$monthlyRecords->isEmpty())
                                <tr>
                                    <td colspan="11" class="text-center">No records found</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot class="table-dark">
                            <tr>
                                <td colspan="3" class="text-center"><strong>Total</strong></td>
                                <td colspan="2"></td>
                                <td><strong>{{ $monthlyRecords->sum(fn($r) => $r->distance_travelled * $r->number_of_rounds) }}</strong></td>
                                <td></td>
                                <td><strong>{{ $monthlyRecords->sum('carbon_emission') }}</strong></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="d-flex justify-content-center mt-3">
                        {{ @$monthlyRecords->links('vendor.pagination.financial-year') }}
                    </div>
                </div>
                <div class="tab-pane fade {{ request('tab') == 'yearly' ? 'show active' : '' }}" id="yearly" role="tabpanel" aria-labelledby="yearly-tab">
                    
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
                                    <a href="{{ route('sustainability.stage2.fuel-consumption') }}" class="btn btn-danger me-2">
                                    <i class="fa fa-retweet" aria-hidden="true"></i> Reset</a>
                                </div>
                            </form>
                        </div>

                        @if(!empty(@$yearlyRecords[0]))
                            <div class="col-md-4 d-flex justify-content-end">
                                <form action="{{ route('sustainability.stage2.fuel-consumption.export') }}" method="post" class="d-flex">
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
                                <!-- @foreach($suppliers as $supplier)
                                    <th style="width: 150px;">{{ $supplier->c_name }}</th>
                                @endforeach -->
                                @foreach($suppliers as $supplier)
                                    @if($present_supplier_ids->contains($supplier->id))
                                        <th style="width: 150px;">{{ $supplier->c_name }}</th>
                                    @endif
                                @endforeach
                                <th>Total Kms</th>
                                <th>Carbon Emission</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $groupedRecords = $yearlyRecords->groupBy(fn($item) => date('Y-m', strtotime($item->month_year))); // Group by Year-Month
                                
                            @endphp
                            @forelse($groupedRecords as $month => $monthRecords)
                          
                                @php
                                    $total_distance = 0;
                                    $total_emission = 0;
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ date('M - Y', strtotime($month . '-01')) }}</td>

                                    @foreach($suppliers->whereIn('id', $present_supplier_ids) as $supplier)
                                        @php
                                            $supplierMonthRecords = $monthRecords->where('supplier_id', $supplier->id);
                                            $supplierDistance = $supplierMonthRecords->sum(function($r) {
                                                return $r->distance_travelled * $r->number_of_rounds;
                                            });
                                            
                                            $total_distance += $supplierDistance;
                                            $total_emission += $supplierMonthRecords->sum('carbon_emission');
                                        @endphp
                                        <td>{{ $supplierDistance }}</td>
                                    @endforeach

                                    <td>{{ $total_distance }}</td>
                                    <td>{{ $total_emission }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 3 + $suppliers->whereIn('id', $present_supplier_ids)->count() }}" class="text-center">No records found</td>
                                </tr>
                            @endforelse
                            </tbody>

                            <tfoot class="table-dark">
                            <tr>
                                <td colspan="2" class="text-center fw-bolder">Total</td>
                                @foreach($suppliers as $supplier)
                                    @if($present_supplier_ids->contains($supplier->id))
                                        <td>{{ $supplierTotals[$supplier->id] ?? 0 }}</td>
                                    @endif
                                @endforeach
                                <td>{{ $overallTotalDistance }}</td>
                                <td>{{ $overallTotalEmission }}</td>
                            </tr>
                            <!-- <tr>
                                <td colspan="2" class="text-center fw-bolder">Avg. Total</td>
                                @foreach($suppliers as $supplier)
                                    @if($present_supplier_ids->contains($supplier->id))
                                        <td>{{ round(($supplierTotals[$supplier->id] ?? 0) / 12, 5) }}</td>
                                    @endif
                                @endforeach
                                <td>{{ round($overallTotalDistance / 12, 5) }}</td>
                                <td>{{ round($overallTotalEmission / 12, 5) }}</td>
                            </tr> -->
                        </tfoot>

                        </table>
                        <div class="d-flex justify-content-center mt-3">
                            {{ @$yearlyRecords->links() }}
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
@section('footer')

@endsection