@extends('layouts.app')

@section('content')
    <div class="row mx-3 my-2">
        <h2>Hardware Month-End Consumable Onboarding</h2>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Hardware ID</th>
                            <th>Hardware Name</th>
                            <th>Hardware Rate</th>
                            <th>Hardware Supplier</th>
                            <th>Name Match in Consumable</th>
                            <th>Linked Consumable</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($hardwares as $hardware)
                            @php
                                $linked = $hardware->consumable;
                                $nameMatched = $hardware->name_matched_consumable;
                                $isDone = !empty($hardware->consumable_id);
                            @endphp
                            <tr>
                                <td>{{ $hardware->id }}</td>
                                <td>{{ $hardware->name }}</td>
                                <td>{{ $hardware->rate }}</td>
                                <td>{{ optional($hardware->hardwareSuppliers)->name }}</td>
                                <td>
                                    @if ($nameMatched)
                                        {{ $nameMatched->name }} (ID: {{ $nameMatched->id }})
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($linked)
                                        {{ $linked->name }} (ID: {{ $linked->id }})
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($isDone)
                                        <span class="badge badge-success">Done</span>
                                    @else
                                        <span class="badge badge-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($isDone)
                                        <button class="btn btn-secondary btn-sm" disabled>Done</button>
                                    @else
                                        <a class="btn btn-primary btn-sm"
                                            href="{{ route('hardwares.monthend.onboarding.create_consumable_form', ['hardware' => $hardware->id]) }}">
                                            Create Consumable
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

