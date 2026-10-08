@extends('layouts.app')

@section('content')

    <div class="row mx-1 my-2">
        <div class="d-flex justify-content-between align-items-center w-100">
            <h2>Sustainability Summary</h2>
            <!-- <a class="btn btn-primary float-end" style="color: #fff;" href=""> <i class="fa fa-plus"></i> Add New </a> -->
        </div>
    </div>


    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Summary </h5>
        </div>
        <div class="card-body">
            <!-- Filter For Records -->
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
                            <a href="{{ route('sustainability.summary.index') }}" class="btn btn-danger me-2">
                            <i class="fa fa-retweet" aria-hidden="true"></i> Reset</a>
                        </div>
                    </form>
                </div>

                <div class="col-md-4 d-flex justify-content-end">
                    <form action="{{ route('sustainability.summary.export') }}" method="post" class="d-flex">
                        @csrf
                        <!-- <input type="hidden" name="search" value="{{ request('search') }}"> -->
                        <input type="hidden" name="date-from" value="{{ request('date-from') }}">
                        <input type="hidden" name="date-to" value="{{ request('date-to') }}">
                        <button type="submit" class="btn btn-success"> <i class="fa fa-file-excel"></i> Export</button>
                    </form>
                </div>
            </div>
            <!-- End Filter Records -->

            <div class="alert alert-light border mb-3">
                <h6 class="fw-bold mb-2">How Per Product Emissions are calculated</h6>
                <p class="mb-2">
                    <strong>Per Product Emissions (Overall)</strong> is the company-wide average:
                    total carbon from Stages 1–6 divided by total products shipped in the selected period
                    (same as Emission Per Container ÷ average pieces per container).
                </p>
                <p class="mb-2">
                    <strong>Per Product by country</strong> (UK, US, EU, CA, IN) uses the same supply chain for each market:
                </p>
                <ul class="mb-2">
                    <li>Stages 1–3 (making the product in India) are shared and split by how many products each country received.</li>
                    <li>Stages 4–5 (shipping and inbound logistics) use the saved totals, split by each country’s share of Stage 5–processed destinations/shipments.</li>
                    <li>Stage 6 (last-mile delivery) is already tracked by country and used directly.</li>
                </ul>
                <p class="mb-0">
                    Then: that country’s carbon ÷ that country’s products = Per Product for that country.
                    Country rates are <strong>not</strong> added together — weighted by product qty, they average back to the overall figure.
                    Destinations outside UK/US/EU/CA/IN (for example Australia) are included in the overall total but are not listed as a separate country row.
                </p>
            </div>
           
            <table class="table table-bordered table-striped table-hover">
                <caption class="caption-top bg-primary-subtle text-center">
                   <h4>Carbon Emissions Report ({{ $records['date-range'] }})</h4> 
                </caption>
                <thead class="table-dark">
                    <tr>
                        <th>Sr.No</th>
                        <th>Stage</th>
                        <th>Process</th>
                        <th>Carbon Emission</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records['data'] as $stage)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ ucfirst($stage['name']) }}</td>
                            <td>{{ $stage['process'] }}</td>
                            <td>{{ number_format($stage['carbon_emission_monthly'], 5) }}</td>
                        </tr>
                    @endforeach
                    
                </tbody>
                <tfoot class="table-warning">
                        <tr><td colspan="4"></tr></tr>
                    @foreach($records['avg'] as $label => $value)
                        <tr>
                            <td colspan="3" class="text-end"><strong>{{ $label }}</strong></td>
                            <td>{{ number_format((float) $value, 5) }}</td>
                        </tr>
                    @endforeach
                </tfoot>
                
            </table>

            @if(!empty($records['country_breakdown']))
                @php $breakdown = $records['country_breakdown']; @endphp
                <div class="card border mt-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Per Country Calculation Breakdown (with numbers)</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">
                            Formula for each country:
                            <code>(S1–3 × qty share + S4 × s4 share + S5 × s5 share + S6 country) ÷ (saved qty × qty share)</code>
                        </p>

                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-secondary">
                                    <tr>
                                        <th colspan="2">Shared saved totals used in this report</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Stages 1–3 total</td>
                                        <td class="text-end">{{ number_format($breakdown['inputs']['stage_1_3'], 5) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Stage 4 total (saved)</td>
                                        <td class="text-end">{{ number_format($breakdown['inputs']['stage_4'], 5) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Stage 5 total (saved)</td>
                                        <td class="text-end">{{ number_format($breakdown['inputs']['stage_5'], 5) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Stage 6 total (saved)</td>
                                        <td class="text-end">{{ number_format($breakdown['inputs']['stage_6'], 5) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Total products (saved qty)</td>
                                        <td class="text-end">{{ number_format($breakdown['inputs']['qty'], 2) }}</td>
                                    </tr>
                                    <tr class="table-warning">
                                        <td><strong>Overall total carbon (S1–6)</strong></td>
                                        <td class="text-end"><strong>{{ number_format($breakdown['inputs']['total_carbon'], 5) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Invoice qty base (Stage 5 processed invoices, for shares)</td>
                                        <td class="text-end">{{ number_format($breakdown['share_bases']['qty_total'], 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Mundra container base (for Stage 4 shares)</td>
                                        <td class="text-end">{{ number_format($breakdown['share_bases']['s4_total'], 0) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Stage 5 signal base (for Stage 5 shares)</td>
                                        <td class="text-end">{{ number_format($breakdown['share_bases']['s5_total'], 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="accordion" id="countryCalcAccordion">
                            @foreach($breakdown['countries'] as $i => $row)
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-{{ $row['country'] }}">
                                        <button class="accordion-button {{ $i === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $row['country'] }}" aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="collapse-{{ $row['country'] }}">
                                            <strong class="me-2">{{ $row['country'] }}</strong>
                                            — Per Product Emissions =
                                            <span class="ms-1">{{ number_format($row['per_product'], 5) }}</span>
                                        </button>
                                    </h2>
                                    <div id="collapse-{{ $row['country'] }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" aria-labelledby="heading-{{ $row['country'] }}" data-bs-parent="#countryCalcAccordion">
                                        <div class="accordion-body">
                                            @if($row['qty_country'] <= 0)
                                                <p class="mb-0 text-muted">
                                                    No products mapped to {{ $row['country'] }} in this period, so Per Product = 0.
                                                </p>
                                            @else
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered mb-3">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Step</th>
                                                                <th>Numbers</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>Qty share</td>
                                                                <td>
                                                                    {{ number_format($row['invoice_qty'], 2) }}
                                                                    ÷ {{ number_format($breakdown['share_bases']['qty_total'], 2) }}
                                                                    = <strong>{{ number_format($row['qty_share'], 2) }}%</strong>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Stage 4 share</td>
                                                                <td>
                                                                    {{ number_format($row['invoice_s4_containers'], 0) }}
                                                                    ÷ {{ number_format($breakdown['share_bases']['s4_total'], 0) }}
                                                                    = <strong>{{ number_format($row['s4_share'], 2) }}%</strong>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Stage 5 share</td>
                                                                <td>
                                                                    {{ number_format($row['invoice_s5_signal'], 2) }}
                                                                    ÷ {{ number_format($breakdown['share_bases']['s5_total'], 2) }}
                                                                    = <strong>{{ number_format($row['s5_share'], 2) }}%</strong>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>{{ $row['country'] }} products</td>
                                                                <td>
                                                                    {{ number_format($breakdown['inputs']['qty'], 2) }}
                                                                    × {{ number_format($row['qty_share'], 2) }}%
                                                                    = <strong>{{ number_format($row['qty_country'], 2) }}</strong>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Stages 1–3 for {{ $row['country'] }}</td>
                                                                <td>
                                                                    {{ number_format($breakdown['inputs']['stage_1_3'], 5) }}
                                                                    × {{ number_format($row['qty_share'], 2) }}%
                                                                    = <strong>{{ number_format($row['stage_1_3'], 5) }}</strong>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Stage 4 for {{ $row['country'] }}</td>
                                                                <td>
                                                                    {{ number_format($breakdown['inputs']['stage_4'], 5) }}
                                                                    × {{ number_format($row['s4_share'], 2) }}%
                                                                    = <strong>{{ number_format($row['stage_4'], 5) }}</strong>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Stage 5 for {{ $row['country'] }}</td>
                                                                <td>
                                                                    {{ number_format($breakdown['inputs']['stage_5'], 5) }}
                                                                    × {{ number_format($row['s5_share'], 2) }}%
                                                                    = <strong>{{ number_format($row['stage_5'], 5) }}</strong>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Stage 6 for {{ $row['country'] }}</td>
                                                                <td>
                                                                    Saved by location =
                                                                    <strong>{{ number_format($row['stage_6'], 5) }}</strong>
                                                                </td>
                                                            </tr>
                                                            <tr class="table-light">
                                                                <td><strong>{{ $row['country'] }} total carbon</strong></td>
                                                                <td>
                                                                    {{ number_format($row['stage_1_3'], 5) }}
                                                                    + {{ number_format($row['stage_4'], 5) }}
                                                                    + {{ number_format($row['stage_5'], 5) }}
                                                                    + {{ number_format($row['stage_6'], 5) }}
                                                                    = <strong>{{ number_format($row['total_carbon'], 5) }}</strong>
                                                                </td>
                                                            </tr>
                                                            <tr class="table-warning">
                                                                <td><strong>Per Product Emissions ({{ $row['country'] }})</strong></td>
                                                                <td>
                                                                    {{ number_format($row['total_carbon'], 5) }}
                                                                    ÷ {{ number_format($row['qty_country'], 2) }}
                                                                    = <strong>{{ number_format($row['per_product'], 5) }}</strong>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @php $other = $breakdown['other'] ?? null; @endphp
                        @if(!empty($other))
                            <div class="border rounded p-3 mt-4 bg-light">
                                <h6 class="fw-bold mb-2">Other destinations (outside UK / US / EU / CA / IN)</h6>
                                <p class="small text-muted mb-3">
                                    These markets are included in the overall company total, but are not shown as main Per Product country rows.
                                    This is why UK+US+EU+CA+IN total carbon is lower than Overall total carbon.
                                </p>

                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Destination</th>
                                                <th class="text-end">Invoices</th>
                                                <th class="text-end">Invoice qty</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($other['destinations'] as $dest)
                                                <tr>
                                                    <td>{{ $dest['destination'] }}</td>
                                                    <td class="text-end">{{ number_format($dest['invoices'], 0) }}</td>
                                                    <td class="text-end">{{ number_format($dest['qty'], 2) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">No other destinations in this period</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <tbody>
                                            <tr>
                                                <td>Other destinations total carbon (allocated)</td>
                                                <td class="text-end"><strong>{{ number_format($other['summary']['total_carbon'], 5) }}</strong></td>
                                            </tr>
                                            <tr>
                                                <td>Other destinations products (allocated from saved qty)</td>
                                                <td class="text-end">{{ number_format($other['summary']['qty_country'], 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td>Other destinations Per Product (reference only)</td>
                                                <td class="text-end">{{ number_format($other['summary']['per_product'], 5) }}</td>
                                            </tr>
                                            <tr class="table-light">
                                                <td>UK + US + EU + CA + IN total carbon</td>
                                                <td class="text-end">{{ number_format($other['five_countries_total'], 5) }}</td>
                                            </tr>
                                            <tr class="table-warning">
                                                <td><strong>5 countries + Other = Overall total</strong></td>
                                                <td class="text-end">
                                                    <strong>
                                                        {{ number_format($other['five_countries_total'], 5) }}
                                                        + {{ number_format($other['summary']['total_carbon'], 5) }}
                                                        = {{ number_format($other['check_total'], 5) }}
                                                    </strong>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
           
        </div>
    </div>
   

@endsection
@section('footer')

@endsection