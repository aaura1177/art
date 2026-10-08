@extends('layouts.app')

@section('content')

    <div class="row mx-3 my-2">
      <h2>Accepted Purchase Orders (Consumables)</h2>
      <div class="col">
        <button type="button" class="btn btn-success float-end" id="multi-raise-consumable-btn" style="color: #fff;">
          Raise Multi Invoice (Consumable)
        </button>
        @if (!empty($doesPackaging))
        <button type="button" class="btn btn-success float-end me-2" id="multi-raise-carton-btn" style="color: #fff;">
          Raise Multi Invoice (Carton)
        </button>
        @endif
      </div>
    </div>

    <form id="multi-po-consumable-form" method="POST"
        action="{{ url('/supplier-dashboard/raise-invoice/multiple-consumable') }}" target="_blank" class="d-none">
        @csrf
        <input type="hidden" name="selected_po_ids" id="selected_po_ids_consumable" value="">
    </form>
    @if (!empty($doesPackaging))
    <form id="multi-po-carton-form" method="POST"
        action="{{ url('/supplier-dashboard/raise-invoice/multiple-carton') }}" target="_blank" class="d-none">
        @csrf
        <input type="hidden" name="selected_po_ids" id="selected_po_ids_carton" value="">
    </form>
    @endif

    <div class="card mb-3">
      <div class="card-body">
          <p class="text-muted mb-2">
            Select two or more accepted POs using the checkboxes, then use
            <strong>Raise Multi Invoice (Consumable)</strong> for consumable/M-series POs.
            @if (!empty($doesPackaging))
              For carton/packaging POs, use <strong>Raise Multi Invoice (Carton)</strong> here or the
              <strong>Packaging (carton)</strong> menu.
            @endif
          </p>
          <div class="table-responsive">
            <table class="table table-bordered" id="dataTableConsumable" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>Select</th>
                  <th>PO No.</th>
                  <th>Supplier Ref.</th>
                  <th>PO Date</th>
                  <th>Delivery Date</th>
                  <th>Total Quantity</th>
                  <th>Total Amount</th>
                  <th>Status</th>
                  <th style="min-width:120px;">Pending Products</th>
                  <th>All Products</th>
                  <th style="min-width:120px;">Options</th>
                  <th style="display:none"></th>
                </tr>
              </thead>
              <tbody>
                @if(isset($purchaseOrder)) @foreach($purchaseOrder as $key => $purchaseOrder)
                @php $poUiId = '10000000' . $purchaseOrder->id; @endphp
                <tr>
                  <td>
                    @if ($purchaseOrder->supplier_status == 1)
                      <input type="checkbox" class="multi-po-select"
                        data-po-type="{{ (int) $purchaseOrder->type }}"
                        value="{{ $purchaseOrder->id }}"
                        aria-label="Select PO {{ $purchaseOrder->pono }}">
                    @endif
                  </td>
                  <td>{{$purchaseOrder->pono}}</td>
                  <td>{{$purchaseOrder->ref_supplier}}</td>
                  <td>{{date('d-M-Y',strtotime($purchaseOrder->podate))}}</td>
                  <td>{{date('d-M-Y',strtotime($purchaseOrder->del_date))}}</td>
                  <td>{{$purchaseOrder->tquantity}}</td>
                  <td>{{$purchaseOrder->tamount}}</td>
                  <td>@if($purchaseOrder->status == 0){{'Pending'}}@endif
                      @if($purchaseOrder->status == 1){{'Complete'}}@endif
                  </td>

                  <td>
                    @if ($purchaseOrder->type != 2)
                      @foreach($purchaseOrder->poTable as $potable)
                        @if($potable->remqty > 0)
                          @if ($potable->product)
                            {{ $potable->product->code }} - {{$potable->remqty}}/{{$potable->quantity}} <br>
                          @else
                            {{ optional($potable->consumable)->name ?? '—' }} - {{$potable->remqty}}/{{$potable->quantity}} <br>
                          @endif
                        @endif
                      @endforeach
                    @else
                      @foreach($purchaseOrder->popTable as $popTable)
                        @if($popTable->remqty_box1 > 0 || $popTable->remqty_box2 > 0)
                          {{ optional($popTable->product)->code ?? ('#'.$popTable->product_id) }}
                          <br>
                        @endif
                      @endforeach
                    @endif
                  </td>
                  <td>
                    @if ($purchaseOrder->type != 2)
                      @foreach($purchaseOrder->poTable as $potable)
                        @if ($potable->product)
                          {{ $potable->product->code }} <br>
                        @else
                          {{ optional($potable->consumable)->name ?? '—' }} <br>
                        @endif
                      @endforeach
                    @else
                      @foreach($purchaseOrder->popTable as $popTable)
                        {{ optional($popTable->product)->code ?? ('#'.$popTable->product_id) }} <br>
                      @endforeach
                    @endif
                  </td>

                  <td>
                    <div class="d-inline-flex flex-wrap gap-1 align-items-center">
                    @if($purchaseOrder->supplier_status != 1)
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Accept" id="{{ $poUiId }}_po" >
                      <button type="button" class="btn btn-info btn-sm" onclick="acceptePoConsumable('{{ $poUiId }}')">
                        <i class="fa fa-check"></i>
                      </button>
                    </span>
                    @endif
                    @if ($purchaseOrder->type == 2)
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                        <a class="btn btn-primary btn-sm" style="color: #fff;" href="{{ URL::to('/supplier-dashboard/modal/carton/' . $poUiId) }}" target="_blank"><i class="fa fa-eye"></i></a>
                      </span>
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Print">
                        <a class="btn btn-success btn-sm" style="color: #fff;" href="{{ URL::to('/supplier-dashboard/modal/carton/' . $poUiId . '?print=1')}}" target="_blank"><i class="fa fa-print"></i></a>
                      </span>
                    @else
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="View">
                        <a class="btn btn-primary btn-sm" style="color: #fff;" href="{{ URL::to('/supplier-dashboard/modal/' . $poUiId)}}" target="_blank"><i class="fa fa-eye"></i></a>
                      </span>
                      <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Print">
                        <a class="btn btn-success btn-sm" style="color: #fff;" href="{{ URL::to('/supplier-dashboard/modal/' . $poUiId . '?print=1')}}" target="_blank"><i class="fa fa-print"></i></a>
                      </span>
                    @endif
                    @if($purchaseOrder->supplier_status == 1)
                    <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Raise Invoice">
                      <a class="btn btn-success btn-sm" style="color: #fff;" href="{{ URL::to('/supplier-dashboard/raise-invoice/' . $poUiId)}}" target="_blank"><i class="fa fa-clipboard"></i> Raise Invoice</a>
                    </span>
                    @endif
                    </div>
                  </td>
                  <td style="display:none">{{$purchaseOrder->podate}}</td>
                </tr>
                @endforeach @endif
              </tbody>
            </table>
          </div>
      </div>
    </div>

@endsection

@section('footer')

<script type="text/javascript">

  $(function () {
    $('[data-bs-toggle="tooltip"]').tooltip()
  });

  function collectSelectedPoIds(poType) {
    const ids = [];
    $('.multi-po-select:checked').each(function() {
      if (parseInt($(this).data('po-type'), 10) === poType) {
        ids.push($(this).val());
      }
    });
    return ids;
  }

  $('#multi-raise-consumable-btn').on('click', function() {
    const ids = collectSelectedPoIds(1);
    if (ids.length < 2) {
      alert('Please select at least 2 consumable purchase orders (non-carton).');
      return;
    }
    $('#selected_po_ids_consumable').val(ids.join(','));
    $('#multi-po-consumable-form').submit();
  });

  @if (!empty($doesPackaging))
  $('#multi-raise-carton-btn').on('click', function() {
    const ids = collectSelectedPoIds(2);
    if (ids.length < 2) {
      alert('Please select at least 2 carton purchase orders.');
      return;
    }
    $('#selected_po_ids_carton').val(ids.join(','));
    $('#multi-po-carton-form').submit();
  });
  @endif

  $(function(){
    var table = $("#dataTableConsumable").DataTable();
    $('#dataTableConsumable_filter').append( '<br><label>Search products: <input id="pending-product-search-consumable" class="form-control" type="text" placeholder="Search product list" data-index="" /></label>');
    $('#dataTableConsumable_filter').append( '<br><label>Date From: <input type="date" id="date-from-consumable" class="form-control" /></label>');
    $('#dataTableConsumable_filter').append( '<br><label>Date To: <input type="date" id="date-to-consumable" class="form-control" /></label>');

    $(table.table().container()).on('keyup', '#pending-product-search-consumable', function () {
      table
        .column(9)
        .search(this.value)
        .draw();
    });

    $('#date-from-consumable').change(function(){
      minDateFilterConsumable = new Date(this.value).getTime();
      table.draw();
    });

    $('#date-to-consumable').change(function(){
      maxDateFilterConsumable = new Date(this.value).getTime();
      table.draw();
    });

    minDateFilterConsumable = "";
    maxDateFilterConsumable = "";

    $.fn.dataTable.ext.search.push(
      function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'dataTableConsumable') {
          return true;
        }
        var createdAt = data[11] || 0;

        if  ((minDateFilterConsumable == "" || maxDateFilterConsumable == "") || (moment(createdAt).isSameOrAfter(minDateFilterConsumable) && moment(createdAt).isSameOrBefore(maxDateFilterConsumable))) {
          return true;
        }
        return false;
      }
    );
  });

  function acceptePoConsumable(id){
    $.ajax({
      'url': "{{ url('/supplier-dashboard/accept-purchase-order') }}" + '/' + id,
      'method': 'get',
      success:function(r){
        if(r.states == true) {
          $("#"+id+ "_po").remove();
        }
        alert(r.msg);
      }
    });
  }
</script>

@endsection
