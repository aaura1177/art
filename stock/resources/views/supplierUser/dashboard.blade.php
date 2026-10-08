@extends('layouts.app')

@section('content')
    @php
        $dashSupplier = App\supplier::where('id', Auth::user()->supplier_id)->first();
        $acceptedPoDashboardUrl = url('/supplier-dashboard/accepted-purchase-orders');
        $acceptedPoDashboardIsBoth = $dashSupplier && $dashSupplier->type == 'Both';
        $dashDoesPackaging = $dashSupplier && ($dashSupplier->do_packaging ?? 'No') === 'Yes';
        $acceptedPoDashboardIsConsumableWithCarton = $dashSupplier
            && $dashSupplier->type == 'Consumable'
            && $dashDoesPackaging;
        if ($dashSupplier && $dashSupplier->type == 'Consumable') {
             $acceptedPoDashboardUrl = url('/supplier-dashboard/accepted-purchase-orders-consumable');
         } elseif ($dashSupplier && $dashSupplier->type == 'Service') {
             $acceptedPoDashboardUrl = url('/supplier-dashboard/accepted-service-orders');
         }
     @endphp
    <!-- Icon Cards-->
    <div class="row">
      <div class="col-xl-3 col-sm-6 mb-3">
        <div class="card text-white bg-primary o-hidden h-100">
          <div class="card-body">
            <div class="card-body-icon">
              <i class="fas fa-fw fa-cube"></i>
            </div>
            <div class="me-5"><h6>Purchase Orders - {{$purchaseOrders}}</h6></div>
            <div class="me-5"><h6>Pending Purchase Orders - {{$pendingPurchaseOrders}}</h6></div>
            <div class="me-5"><h6>Accepted Purchase Orders - {{$acceptedPurchaseOrders}}</h6></div>
          </div>
          <a class="card-footer text-white clearfix small z-1" href="{{ url('/supplier-dashboard/purchase-orders')}}">
            <span class="float-start">View Details</span>
            <span class="float-end">
              <i class="fas fa-angle-right"></i>
            </span>
          </a>
        </div>
      </div>
      <div class="col-xl-3 col-sm-6 mb-3">
        <div class="card text-white bg-warning o-hidden h-100">
          <div class="card-body">
            <div class="card-body-icon">
              <i class="fas fa-fw fa-handshake"></i>
            </div>
            <div class="me-5"><h6>Invoices - {{$invoices}}</h6></div>
            <div class="me-5"><h6>Accepted Invoices - {{$approvedInvoices}}</h6></div>
          </div>
          <a class="card-footer text-white clearfix small z-1" href="{{ url('/supplier-dashboard/invoice-orders')}}">
            <span class="float-start">View Details</span>
            <span class="float-end">
              <i class="fas fa-angle-right"></i>
            </span>
          </a>
        </div>
      </div>
      <div class="col-xl-3 col-sm-6 mb-3">
        <div class="card text-white bg-danger o-hidden h-100">
          <div class="card-body">
            <div class="card-body-icon">
              <i class="fas fa-fw fa-truck"></i>
            </div>
            <div class="me-5"><h6>Purchase Orders Due Soon - {{$soonduePurchaseOrders}}</h6></div>
            <div class="me-5"><h6>Purchase Orders Overdue - {{$overduePurchaseOrders}}</h6></div>
          </div>
          @if ($acceptedPoDashboardIsBoth)
          <div class="card-footer text-white clearfix small z-1">
            <a href="{{ url('/supplier-dashboard/accepted-purchase-orders') }}" class="text-white float-start me-3">
              <span>Accepted PO (Furniture)</span>
              <i class="fas fa-angle-right ms-1"></i>
            </a>
            <a href="{{ url('/supplier-dashboard/accepted-purchase-orders-consumable') }}" class="text-white float-start me-3">
              <span>Accepted PO (Consumables)</span>
              <i class="fas fa-angle-right ms-1"></i>
            </a>
            @if ($dashDoesPackaging)
            <a href="{{ url('/supplier-dashboard/accepted-purchase-orders-carton') }}" class="text-white float-start">
              <span>Accepted PO (Carton)</span>
              <i class="fas fa-angle-right ms-1"></i>
            </a>
            @endif
          </div>
          @elseif ($acceptedPoDashboardIsConsumableWithCarton)
          <div class="card-footer text-white clearfix small z-1">
            <a href="{{ url('/supplier-dashboard/accepted-purchase-orders-consumable') }}" class="text-white float-start me-3">
              <span>Accepted PO (Consumables)</span>
              <i class="fas fa-angle-right ms-1"></i>
            </a>
            <a href="{{ url('/supplier-dashboard/accepted-purchase-orders-carton') }}" class="text-white float-start">
              <span>Accepted PO (Carton)</span>
              <i class="fas fa-angle-right ms-1"></i>
            </a>
          </div>
          @else
          <a class="card-footer text-white clearfix small z-1" href="{{ $acceptedPoDashboardUrl }}">
            <span class="float-start">View Details</span>
            <span class="float-end">
              <i class="fas fa-angle-right"></i>
            </span>
          </a>
          @endif
        </div>
      </div>
      <div class="col-xl-3 col-sm-6 mb-3">
        <div class="card text-white bg-success o-hidden h-100">
          <div class="card-body">
            <div class="card-body-icon">
              <i class="fas fa-fw fa-suitcase"></i>
            </div>
            <div class="me-5"><h6>Rejected items - {{$rejectPurchaseOrders}}</h6></div>
            <div class="me-5"><h6>Repairable items - {{$repairPurchaseOrders}}</h6></div>
          </div>
          <a class="card-footer text-white clearfix small z-1" href="{{ url('/supplier-dashboard/returned-purchase-orders')}}">
            <span class="float-start">View Details</span>
            <span class="float-end">
              <i class="fas fa-angle-right"></i>
            </span>
          </a>
        </div>
      </div>      
    </div>
    
    <!-- Notifications -->
    <div class="row">
      <div class="col-xl-12 col-sm-12 mb-3">
        <div class="card">
          <div class="card-header"><i class="fas fa-fw fa-bell"></i> Notifications</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered" id="supplierNotificationsTable" width="100%" cellspacing="0">
                  <thead>
                    <tr>
                      <th>Message</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    @if(isset($notifications)) @foreach($notifications as $key => $notification)
                    <tr>
                      <td>{{$notification->notification}}</td>
                      <td>
                        @if($notification->is_read === 0)
                        <span class="tool-tip1" data-bs-toggle="tooltip" data-bs-placement="top" title="Mark as Read">
                          <a class="btn btn-success" style="color: #fff;" href="{{ URL::to('/supplier-dashboard/noti-read/'.$notification->id)}}" ><i class="fa fa-check"></i> Mark as Read</a>
                        </span>
                        @endif
                      </td>                    
                    </tr>
                    @endforeach @endif
                  </tbody>
                </table>
              </div>
            </div>
        </div>
      </div>
    </div>
    <!-- Area Chart Example-->
    <div class="row">
      <div class="col-xl-6 col-sm-6 mb-3">
        <div class="card">
          <div class="card-header"><i class="fas fa-fw fa-shopping-cart"></i> Recent Purchase Orders</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered" id="supplierRecentPoTable" width="100%" cellspacing="0">
                  <thead>
                    <tr>
                      <th>PO No.</th>                
                      <th>Supplier Name</th>
                      <th>PO Date</th>
                      <th>Delivery Date</th>
                      <th>Total Quantity</th>
                      <th>Total Amount</th>
                      <th>Status</th>              
                    </tr>
                  </thead>
                  <tbody>
                    @if(isset($purchaseOrderList)) @foreach($purchaseOrderList as $key => $purchaseOrder)
                    <tr>
                      <td>{{$purchaseOrder->pono}}</td>
                      <td>{{ optional($purchaseOrder->supplier)->c_name }}</td>
                      <td>{{date('d-M-Y',strtotime($purchaseOrder->podate))}}</td>
                      <td>{{date('d-M-Y',strtotime($purchaseOrder->del_date))}}</td>
                      <td>{{$purchaseOrder->tquantity}}</td>
                      <td>{{$purchaseOrder->tamount}}</td>
                      <td>@if($purchaseOrder->status == 0){{'Pending'}}@endif
                          @if($purchaseOrder->status == 1){{'Complete'}}@endif
                      </td>                    
                    </tr>
                    @endforeach @endif
                  </tbody>
                </table>
              </div>
            </div>
        </div>
      </div>
      <div class="col-xl-6 col-sm-6 mb-3">
        <div class="card">
          <div class="card-header"><i class="fas fa-fw fa-list"></i> Recent Invoices</div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered" id="supplierRecentInvoicesTable" width="100%" cellspacing="0">
                  <thead>
                    <tr>
                      <th>Invoice Number</th>                
                      <th>Eway Bill No</th>
                      <th>Vehicle No</th>
                      <th>Total Amount</th>                
                    </tr>
                  </thead>
                  <tbody>
                    @if(isset($invoicesList)) @foreach($invoicesList as $key => $invoice)
                    <tr>
                      <td>{{$invoice->supplier_invoice_number}}</td>
                      <td>{{$invoice->eway_bill_no}}</td>
                      <td>{{$invoice->vehicle_no}}</td>                      
                      <td>{{$invoice->tamount}}</td>                      
                    </tr>
                    @endforeach @endif
                  </tbody>
                </table>
              </div>
            </div>
        </div>
      </div>
    </div>
        
@endsection

@section('footer')
@endsection