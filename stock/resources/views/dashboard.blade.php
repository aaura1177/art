@extends('layouts.app')

@section('content')

    <!-- Icon Cards-->
    <div class="row">
      <div class="col-xl-3 col-sm-6 mb-3">
        <div class="card text-white bg-primary o-hidden h-100">
          <div class="card-body">
            <div class="card-body-icon">
              <i class="fas fa-fw fa-cube"></i>
            </div>
            <div class="me-5"><h4>{{$productCount}} Products</h4></div>
          </div>
          <a class="card-footer text-white clearfix small z-1" href="{{ url('/product')}}">
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
            <div class="me-5"><h4>{{$buyerCount}} Buyers</h4></div>
          </div>
          <a class="card-footer text-white clearfix small z-1" href="{{ url('/buyer')}}">
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
            <div class="me-5"><h4>{{$supplierCount}} Suppliers</h4></div>
          </div>
          <a class="card-footer text-white clearfix small z-1" href="{{ url('/supplier')}}">
            <span class="float-start">View Details</span>
            <span class="float-end">
              <i class="fas fa-angle-right"></i>
            </span>
          </a>
        </div>
      </div>
      <div class="col-xl-3 col-sm-6 mb-3">
        <div class="card text-white bg-success o-hidden h-100">
          <div class="card-body">
            <div class="card-body-icon">
              <i class="fas fa-fw fa-suitcase"></i>
            </div>
            <div class="me-5"><h4>{{$contratorCount}} Contrators</h4></div>
          </div>
          <a class="card-footer text-white clearfix small z-1" href="{{ url('/contractor')}}">
            <span class="float-start">View Details</span>
            <span class="float-end">
              <i class="fas fa-angle-right"></i>
            </span>
          </a>
        </div>
      </div>
      <div class="col-xl-3 col-sm-6 mb-3">
        <div class="card text-white bg-info o-hidden h-100">
          <div class="card-body">
            <div class="card-body-icon">
              <i class="fas fa-fw fa-times-circle"></i>
            </div>
            <div class="me-5"><h4>{{$rejectRepairCount}} Reject / Repairs</h4></div>
          </div>
          <a class="card-footer text-white clearfix small z-1" href="{{ url('/rejectRepair')}}">
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
              <i class="fas fa-fw fa-sitemap"></i>
            </div>
            <div class="me-5"><h4>{{$allocationCount}} Allocations</h4></div>
          </div>
          <a class="card-footer text-white clearfix small z-1" href="{{ url('/allocation')}}">
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
                <table class="table table-bordered" id="dataTables1" width="100%" cellspacing="0">
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
    <div class="card mb-3">
      <div class="card-header">
        <i class="fas fa-chart-area"></i>
        Stock Chart</div>
      <div class="card-body">
        <canvas id="myAreaChart" width="100%" height="30"></canvas>
      </div>
    </div>
        
@endsection

@section('footer')

<!-- Script start for Stock chart -->
  <script src="{{ asset('js/demo/datatables-demo.js') }}"></script>
<!--   <script src="{{ asset('js/demo/chart-area-demo.js') }}"></script> -->

<script>
  
  $(document).ready(function(){
   
    var products = [];
    var productCode = [];
    var productQuantity = [];

    $.ajax({
        'url': "{{ url('/dashboard/data') }}",
        'method': 'GET'
    }).done(function(data) {
        if (data) {
            products = data.product;
        }
      });
    
    setTimeout(function() {
      for(var i=0; i<products.length; i++){
        productCode[i] = products[i].code;
        productQuantity[i] = products[i].quantity;
      }
      

      Chart.defaults.font.family = '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
      Chart.defaults.color = '#292b2c';

      var ctx = document.getElementById("myAreaChart");
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: productCode,
          datasets: [{
            label: "Quantity",
            tension: 0.3,
            fill: true,
            backgroundColor: "rgba(2,117,216,0.2)",
            borderColor: "rgba(2,117,216,1)",
            pointRadius: 5,
            pointBackgroundColor: "rgba(2,117,216,1)",
            pointBorderColor: "rgba(255,255,255,0.8)",
            pointHoverRadius: 5,
            pointHoverBackgroundColor: "rgba(2,117,216,1)",
            pointHitRadius: 50,
            pointBorderWidth: 2,
            data: productQuantity,
          }],
        },
        options: {
          scales: {
            x: {
              grid: { display: false },
              ticks: { maxTicksLimit: 10 }
            },
            y: {
              min: 100,
              max: 500,
              ticks: { maxTicksLimit: 10 },
              grid: { color: "rgba(0, 0, 0, .125)" }
            },
          },
          plugins: {
            legend: { display: false }
          }
        }
      });
    }, 1000);  
  });

</script>
<!-- Script end -->

@endsection