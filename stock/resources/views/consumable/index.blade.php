@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
        <h2>Consumables List</h2>

        @hasrole('admin')
            <div class="col">
                <!-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal" data-bs-target="#mydateModal"  href="{{ url('/product/exportcsv') }}">Download CSV</a> -->
                <a class="btn btn-info float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal"
                    data-bs-target="#modalUpdateUnitType">Update Unit Type</a>
                <a class="btn btn-warning float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal"
                    data-bs-target="#modalUpdateSKU">Update SKU</a>
                <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal"
                    data-bs-target="#modalImportExcel">Import Excel</a>
                <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;"
                    href="{{ url('/consumables/exportcsv') }}">Download CSV</a>

                <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;"
                    href="{{ url('/consumables/create') }}">Add Consumable</a>
                <!-- <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/consumables/create') }}">Download  Excel</a> -->
            </div>
        @endhasrole

        @hasrole('factory')
            <div class="col">
                <a class="btn btn-info float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal"
                    data-bs-target="#modalUpdateUnitType">Update Unit Type</a>
                <a class="btn btn-warning float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal"
                    data-bs-target="#modalUpdateSKU">Update SKU</a>
                <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" data-bs-toggle="modal"
                    data-bs-target="#modalImportExcel">Import Excel</a>
                <!-- <a class="btn btn-success float-end" style="color: #fff; margin-left: 5px;" href="{{ url('/consumables/exportcsv') }}">Download CSV</a>-->
                <a class="btn btn-primary float-end" style="color: #fff; margin-left: 5px;"
                    href="{{ url('/consumables/create') }}">Add Consumable</a>
            </div>
        @endhasrole
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTables" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th>Supplier</th>
                            <th>Quantity</th>
                            <th>Payment terms</th>
                            @hasrole('admin')
                                <th>Options</th>
                            @endhasrole
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($consumables))
                            @foreach ($consumables as $key => $consumable)
                                <tr>
                                    <td>{{ $consumable->name }}</td>
                                    <td>{{ $consumable->unitType->name ?? 'N/A' }}</td>
                                    <td>{{ $consumable->rate }}</td>
                                    @php
                                        $supplierRaw = $consumable->supplier;
                                        $supplierData = is_string($supplierRaw)
                                            ? json_decode($supplierRaw, true)
                                            : $supplierRaw;
                                    @endphp

                                    @if (is_array($supplierData))
                                        @php
                                            $supplierNames = \App\supplier::whereIn('id', $supplierData)
                                                ->pluck('c_name')
                                                ->toArray();
                                            $supplierDisplay = implode(', ', $supplierNames);
                                        @endphp
                                        <td> {{ $supplierDisplay ?: 'N/A' }}</td>
                                    @else
                                        <td>
                                            {{ $supplierRaw ? $consumable->supp?->c_name : 'N/A' }}
                                        </td>
                                    @endif




                                    <td>{{ $consumable->quantity }}</td>
                                    <td>{{ $consumable->payment_terms }}</td>
                                    @hasrole('admin')
                                        <td>
                                            <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                                                <button class="btn btn-info" onclick="updateProduct('{{ $consumable->id }}')">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                            </span>
                                            <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                                                <button class="btn btn-danger" data-bs-toggle="modal"
                                                    onclick="deleteModal('{{ $consumable->id }}')">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </span>
                                        </td>
                                    @endhasrole
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL FOR DELETE -->
    <div class="modal fade" id="deleteProduct" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Delete Confirmation</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="deleteProductForm" method="POST" action="">
                    @csrf
                    <div class="modal-body">
                        <p>Are You sure you want to Delete this?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-bs-dismiss="modal" autofocus="">No</button>
                        <button type="submit" id="deleteProductForm" class="btn btn-danger">Yes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- MODAL FOR UPDATE UNIT TYPE (Excel/CSV) -->
    <div class="modal fade" id="modalUpdateUnitType" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Update Unit Type from Excel/CSV</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ url('/consumables/importUpdateUnitType') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body pb-0">
                        <p class="text-muted small">Upload a CSV/Excel with columns </p>
                        <input type="file" name="importCSV" id="importCSVUnitType" accept=".xlsx,.csv" required />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-info">Update Unit Type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL FOR UPDATE SKU (Excel/CSV) -->
    <div class="modal fade" id="modalUpdateSKU" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Update SKU from Excel/CSV</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ url('/consumables/importUpdateSKU') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body pb-0">
                        <p class="mb-3">
                            <a class="btn btn-sm btn-outline-success" href="{{ url('/consumables/exportcsv') }}" download>
                                Download Consumables CSV (with SKU)
                            </a>
                        </p>
                        <p class="text-muted small">Upload a CSV/Excel with columns <code>name</code> and <code>SKU</code>.</p>
                        <input type="file" name="importCSV" id="importCSVSKU" accept=".xlsx,.csv" required />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Update SKU</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- MODAL FOR IMPORT EXCEL -->
    <div class="modal fade" id="modalImportExcel" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"> Upload Excel File</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="modalImportExcelForm" action="{{ url('/consumables/importConsumable') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body pb-0">
                        <input type="file" name="importCSV" id="importCSV" />

                        <p class="mt-4 mb-0">Are You sure you want to Upload this?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-bs-dismiss="modal">No</button>
                        <button type="sumit" id="modalImportExcelForm" onclick="return validate()"
                            class="btn btn-success">Yes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('footer')
    <script type="text/javascript">
        $(function() {
            $('[data-bs-toggle="tooltip"]').tooltip()
        });

        function deleteModal(id) {
            $('#deleteProduct').modal('show');
            $('#deleteProductForm').attr('action', "{{ url('/consumables/delete') }}" + '/' + id);
        }

        function updateProduct(id) {
            location.href = "{{ url('/consumables/view') }}" + '/' + id;
        }

        function downloadcsv() {
            $("#modalPrintForm").attr("action", "{{ url('/consumables/exportCodeWiseExcel') }}");
            return true;
        }
    </script>
@endsection
