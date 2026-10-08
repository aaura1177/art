@extends('layouts.modal')
<style>
.table td{
	font-size: 13px !important;
}
</style>
  
  
  <div class="container">
	<div>
		<h1 class="text-center">Global Vision Direct (P) Limited</h1>
		<div class="text-center">Global Factory, Mahapura Road, Jaipur Ajmer - Highway, Jaipur-302026</div>
		<div style="font-size:20px;" class="text-center"><b><u>Job Card</u></b></div>
	</div>  
    <!-- first row -->
	@if(isset($performanceCard))
    <div class="row box-space">
        <div class="col-6">
			<span>Serial No.:</span> {{$performanceCard->job}}
        </div>
        <div class="col-6 text-end">
			<span>Date:</span> {{date('d M Y',strtotime($performanceCard->date))}}
        </div>
    </div>

    <div class="row box-space">
		<div class="col-6">
			Team/Name : {{$performanceCard->contractor->c_name}}
		</div>
    </div>

    

    <!-- Product details -->
    <div class="row" style="margin-top:10px;">
        <div class="col">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th style="max-width: 50px;">S. No.</th>
                  <th>Description of Goods</th>
                  <th>QTY Received</th>
                  <th>QTY Rejected</th>
                  <th>Net Qty</th>
                  <th>Remarks</th>
                </tr>
              </thead>
               
              <tbody>
                @if(isset($performanceCardProduct)) @foreach($performanceCardProduct as $key => $performanceCardProduct)
                <tr>
                  <td>{{++$key}}</td>
                  <td>{{$performanceCardProduct->product->code}} - {{$performanceCardProduct->product->name}}</td>
                  <td>{{$performanceCardProduct->qty_received}}</td>
                  <td>{{$performanceCardProduct->qty_rejected}}</td>
                  <td>{{$performanceCardProduct->net_qty}}</td>
                  <td>{{$performanceCardProduct->remarks}}</td>
                </tr>
                @endforeach @endif
             
               
              </tbody>
            </table>
        </div>  
    </div>
     @endif

      <!-- Declaration & Signature row -->
      <div class="row" style="margin-top:24px;">
        <div class="col-4 text-center">
			Team Leader/Contractor<br>
				(Signature)
        </div>
		<div class="col-4 text-center">
			Supervisor<br>
			(Signature)
		</div>
		<div class="col-4 text-center">
			Joy Dutt - Head QC<br>
			(Signature)
		</div>
      </div>
  </div>



<!-- Script start for Amount in Words -->
<script src="{{ asset('ui-vendor/jquery/jquery.min.js') }}"></script>
<script type="text/javascript">
  $(document).ready(function(){

    var n = document.getElementById('tamount').textContent;
    n = n.replace(/[^0-9.]/g,'');
    n = parseFloat(n,10);

    var nums = n.toString().split('.')

    var whole = convertNumberToWords(nums[0]);

      if (nums.length == 2) 
      {
          var fraction = convertNumberToWords(nums[1]);
          if(whole == 0)
          {            
              var s=document.getElementById('amountInWords');
              s.innerHTML = fraction + 'Paise';
          }
          else
          {
              if(fraction)
              {                
                  var s=document.getElementById('amountInWords');
                  s.innerHTML = whole + 'Rupees and ' + fraction + 'Paise';
              }
              else
              {
                  var s=document.getElementById('amountInWords');
                  s.innerHTML = whole + 'Rupees';
              }
          }
              
      }

                  
                      
      else 
      {
        if(whole==0)
         {                  
            var s=document.getElementById('amountInWords');
            s.innerHTML = whole + 'Zero Rupee';        
         }
        else
         {
            var s=document.getElementById('amountInWords');
            s.innerHTML = whole + 'Rupees';
         }
      }                    
                      
      function convertNumberToWords(amount) {
      
        var words = new Array();
        words[0] = '';
        words[1] = 'One';
        words[2] = 'Two';
        words[3] = 'Three';
        words[4] = 'Four';
        words[5] = 'Five';
        words[6] = 'Six';
        words[7] = 'Seven';
        words[8] = 'Eight';
        words[9] = 'Nine';
        words[10] = 'Ten';
        words[11] = 'Eleven';
        words[12] = 'Twelve';
        words[13] = 'Thirteen';
        words[14] = 'Fourteen';
        words[15] = 'Fifteen';
        words[16] = 'Sixteen';
        words[17] = 'Seventeen';
        words[18] = 'Eighteen';
        words[19] = 'Nineteen';
        words[20] = 'Twenty';
        words[30] = 'Thirty';
        words[40] = 'Forty';
        words[50] = 'Fifty';
        words[60] = 'Sixty';
        words[70] = 'Seventy';
        words[80] = 'Eighty';
        words[90] = 'Ninety';

        var amount = amount.toString();
        var atemp = amount.split(".");
        var number = atemp[0].split(",").join("");
        var n_length = number.length;
        var words_string = "";
            
        if (n_length <= 9) {
            var n_array = new Array(0, 0, 0, 0, 0, 0, 0, 0, 0);
            var received_n_array = new Array();
            for (var i = 0; i < n_length; i++) {
                received_n_array[i] = number.substr(i, 1);
            }
            for (var i = 9 - n_length, j = 0; i < 9; i++, j++) {
                n_array[i] = received_n_array[j];
            }
            for (var i = 0, j = 1; i < 9; i++, j++) {
                if (i == 0 || i == 2 || i == 4 || i == 7) {
                    if (n_array[i] == 1) {
                        n_array[j] = 10 + parseInt(n_array[j]);
                        n_array[i] = 0;
                    }
                }
            }

            value = "";
            for (var i = 0; i < 9; i++) {
                if (i == 0 || i == 2 || i == 4 || i == 7) {
                    value = n_array[i] * 10;
                } else {
                    value = n_array[i];
                }
                if (value != 0) {
                    words_string += words[value] + " ";
                }
                if ((i == 1 && value != 0) || (i == 0 && value != 0 && n_array[i + 1] == 0)) {
                    words_string += "Crores ";
                }
                if ((i == 3 && value != 0) || (i == 2 && value != 0 && n_array[i + 1] == 0)) {
                    words_string += "Lakhs ";
                }
                if ((i == 5 && value != 0) || (i == 4 && value != 0 && n_array[i + 1] == 0)) {
                    words_string += "Thousand ";
                }
                if (i == 6 && value != 0 && (n_array[i + 1] != 0 && n_array[i + 2] != 0)) {
                    words_string += "Hundred ";
                } else if (i == 6 && value != 0) {
                    words_string += "Hundred ";
                }
            }
            words_string = words_string.split("  ").join(" ");
        }
        return words_string;
      }
  });

  var gstin = document.getElementById("gstin").textContent;
    if(gstin.length != 0){
      document.getElementById("gstin").innerHTML = gstin;
    }
    else{
      document.getElementById("gstin").innerHTML = "N.A";
      
    }

</script>
<!-- Script end -->


<!-- Script start for Print PO -->
@if(isset($print) && $print==1)
    <script type="text/javascript">
      document.ready = window.print();
    </script>
@endif
<!-- Script end -->