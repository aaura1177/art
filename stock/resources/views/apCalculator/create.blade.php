@extends('layouts.app')

@section('content')

<div class="mx-2">
    <div class="row mx-0 my-2">
        <h2>Average Profit Calculator</h2>
    </div>

      <!-- Form Starts -->
      <div class="form-group"> 
          
          <!-- first row -->
          <div class="row mt-3">
            <div class="col-4">
              <label class="control-label">{{ __('Buyer') }}</label>
              <select type="text" class="selectpicker" data-live-search="true" name="buyer_id" id="buyer_id">
                <option value="" selected disabled>Select Temporary Buyer</option>
                @if(isset($tempBuyers)) @foreach($tempBuyers as $key => $tempBuyer)
                <option value="{{$tempBuyer->id}}">
                  {{$tempBuyer->c_name}}
                  </option>
                @endforeach @endif
              </select>
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Valid From') }}</label>
              <input type="date" class="form-control" name="startDate" id="startDate" />                     
            </div>
            <div class="col-4">
              <label class="control-label">{{ __('Valid To') }}</label>
              <input type="date" class="form-control" name="endDate" id="endDate" />
            </div>
          </div>

          <!-- third row -->
          <div class="row mt-3">
            <div class="col-8">
               <button type="submit" class="btn btn-primary mt-3" id="calculateProfit">Calculate Profit</button>
            </div>
          </div>

          <div class="row mt-3">
            <div class="col text-success text-center">
               <p id="avgProfit" style="font-size: 10rem;">0%</p>
            </div>
          </div>

      </div>
</div>
@endsection

@section('footer')

<script type="text/javascript">

  $("#calculateProfit" ).click(function() {
    var id = $('#buyer_id').val();
    var startDate = $('#startDate').val();
    var endDate = $('#endDate').val();

    if(startDate > endDate){
      alert('Valid from Date should be less than Valid To Date');
      return false;
    }

    else{

        var validFrom = new Date(startDate);
        var year = validFrom.getFullYear();
        var month = validFrom.getMonth();
        var day = validFrom.getDate();
        var expiryDate = new Date(year + 1, month, day -1);
        var newDate = moment(expiryDate).format("YYYY-MM-DD");

        if(endDate <= newDate){

          $.ajax({
          'url': "{{ url('/apCalculator/apData') }}/"+id+'/'+startDate+'/'+endDate,
          }).done(function(data) {
              if (data) {
                  avgProfit = data.avg;
                  avgProfit = avgProfit;
                  $('#avgProfit').text(avgProfit+'%');
              }
                
            });
        }

        else{
          alert('Range 1year between Valid from - to Date');
        }
    }
   

      


      

  });


  


    




    


 

</script>

@endsection