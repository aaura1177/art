@extends('layouts.app')

@section('content')
@php use App\Support\SupplierProductPriceLogWriter as LogPresent; @endphp
    <style>
        .supplier-pricing-fields-center {
            text-align: center !important;
            vertical-align: middle !important;
        }
    </style>

    <div class="row mx-3 my-2">
        <h2>Supplier Price</h2>
        <div class="col">
            <a class="btn btn-outline-secondary float-end" style="margin-left: 5px;" href="{{ url('/supplier/pricing/history') }}">Price history</a>
            @can('supplier-edit')
                <button type="button" class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#modalSupplierPricingImport">Import pricing</button>
                <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/supplier/pricing/import-template') }}">Download import sheet</a>
            @endcan
            <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/supplier/pricing/export/pdf') }}">Export PDF</a>
            <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/supplier/pricing/export/csv') }}">Export CSV</a>
            <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/supplier/pricing/export/excel') }}">Export Excel</a>
        </div>
    </div>

    <div class="row mx-3 my-2">
        <div class="col-md-6 col-lg-5">
            <form method="GET" action="{{ url('/supplier/pricing') }}" class="d-flex flex-wrap align-items-center">
                <input type="hidden" name="per_page" value="{{ (int) $perPage }}">
                <label for="supplierPricingSearch" class="me-2 mb-0">Search</label>
                <input type="search" name="q" id="supplierPricingSearch" class="form-control w-auto me-2"
                    style="width: 220px;" placeholder="SKU, supplier name, price, or UK 45 price" autocomplete="off" value="{{ $q ?? '' }}" />
                <button type="submit" class="btn btn-primary me-2">Search</button>
                <a href="{{ url('/supplier/pricing') }}" class="btn btn-secondary">Clear</a>
            </form>
        </div>
        <div class="col-md-6 col-lg-4 mt-2 mt-md-0">
            <form method="GET" action="{{ url('/supplier/pricing') }}" class="d-flex flex-wrap align-items-center">
                <input type="hidden" name="q" value="{{ $q ?? '' }}">
                <label for="per_page" class="me-2 mb-0">Items per page</label>
                <select id="per_page" name="per_page" class="form-select w-auto" onchange="this.form.submit()">
                    @foreach ([10, 25, 50, 100, 200] as $n)
                        <option value="{{ $n }}" {{ (int) $perPage === $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="supplierPricingTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="supplier-pricing-fields-center">#</th>
                            <th class="supplier-pricing-fields-center">Product Sku</th>
                            <th class="supplier-pricing-fields-center">Supplier Name</th>
                            <th class="supplier-pricing-fields-center">Price</th>
                            <th class="supplier-pricing-fields-center">UK 45 Price</th>
                            <th class="supplier-pricing-fields-center">Last update</th>
                            <th class="supplier-pricing-fields-center" style="width:70px;">Log</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paginator as $group)
                            @php
                                $productIndex = ($paginator->firstItem() ?? 0) + $loop->index;
                                $group = $group->sortBy(function ($sp) {
                                    return optional($sp->supplier)->c_name ?? '';
                                })->values();
                                $productId = $group->first()->product_id;
                                $product = $group->first()->product;
                                $rowspan = $group->count();
                            @endphp
                            @foreach ($group as $sp)
                                @php
                                    $logKey = $sp->product_id.':'.$sp->supplier_id;
                                    $latest = ($latestLogs[$logKey] ?? null);
                                @endphp
                                <tr>
                                    @if ($loop->first)
                                        <td rowspan="{{ $rowspan }}" class="supplier-pricing-fields-center">{{ $productIndex }}</td>
                                        <td rowspan="{{ $rowspan }}" class="supplier-pricing-fields-center">{{ $product ? $product->code : 'N/A' }}</td>
                                    @endif
                                    <td class="supplier-pricing-fields-center">{{ optional($sp->supplier)->c_name ?? 'Unknown Supplier' }}</td>
                                    <td class="supplier-pricing-fields-center">{{ $sp->rate }}</td>
                                    <td class="supplier-pricing-fields-center">{{ $sp->uk_45_rate }}</td>
                                    <td class="supplier-pricing-fields-center">
                                        @if ($latest)
                                            <div>{{ LogPresent::friendlyWhen($latest->created_at) }}</div>
                                            <div class="small text-muted">{{ $latest->changed_by_label ?: 'Unknown' }}</div>
                                        @elseif ($sp->updated_at)
                                            <div>{{ LogPresent::friendlyWhen($sp->updated_at) }}</div>
                                            <div class="small text-muted">No log yet</div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="supplier-pricing-fields-center">
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="{{ url('/supplier/pricing/history/'.$sp->product_id.'/'.$sp->supplier_id) }}"
                                           title="View price change log">
                                            <i class="fas fa-history"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No supplier pricing found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($paginator->total() > 0)
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3">
                    <div class="text-muted small mb-2 mb-md-0">
                        Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} products
                    </div>
                    <div>
                        {{ $paginator->onEachSide(1)->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- MODAL FOR DELETE -->
    <div class="modal fade" id="deletesupplier" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Delete Confirmation</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="deletesupplierForm" method="POST" action="">
                    @csrf
                    <div class="modal-body">
                        <p>Are You sure you want to Delete this?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
                        <button type="submit" id="deletesupplierForm" class="btn btn-danger">Yes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @can('supplier-edit')
        <div class="modal fade" id="modalSupplierPricingImport" tabindex="-1" aria-labelledby="modalSupplierPricingImportLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ url('/supplier/pricing/import') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalSupplierPricingImportLabel">Import supplier pricing</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-2">Upload the filled import sheet (xlsx, xls, or csv). Matching is by product SKU and supplier name.</p>
                            <input type="file" name="pricing_import_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Import</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection
