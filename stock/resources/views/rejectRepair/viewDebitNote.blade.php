@extends('layouts.modal')


  <div>
    <h1 class="text-center">Debit Note</h1>  
  </div>  
  
  <div class="container">

    <!-- first row -->
    <div class="row box-space">    
        <div class="col-6">
          @if(isset($companyDetails))
            <img width="500px" src="{{ url('uploads/' . $companyDetails->logoUrl) }}" alt="Logo Image">
            <p style="font-size: 24px;"><strong>{{ $companyDetails->c_name }}</strong></p>
            <address>
              <p style="font-weight: 700;">{{$companyDetails->address1}}</p>
              <p style="font-weight: 700;">{{$companyDetails->address2}}, {{$companyDetails->city}} - {{$companyDetails->postcode}}</p>
              <p>State Name: {{$companyDetails->state}}</p>
              <p>Company PAN: {{$companyDetails->pan}}</p>
              <p>GSTIN/UIN: {{$companyDetails->gstin}}</p>
              <p>E-mail:  po@artisanfurniture.net</p>
            </address> 
            @endif
        </div>
        <div class="col-6">
            <div class="pull-right" id="supplier">
              @if(isset($rejectRepair))
              <h1>Debit Note #{{$RejectRepairPtable->id}}</h1>
              <h3>Supplier Inv. #{{$rejectRepair->supplier_inv_no}}</h3>
              @endif
              <br>
              @if(isset($supplier))
                <address>
                <h3 class="box-space" style="text-decoration: underline;">SUPPLIER</h3>
                <p>{{$supplier->c_name}}</p>
                <p>{{$supplier->address1}}</p>
                <p>{{$supplier->address2}},</p>
                <p>{{$supplier->city}} - {{$supplier->postcode}}</p>
                <p>State Name: {{$supplier->state}}</p>
                <p>GSTIN/UIN: <span style="font-weight: 600; font-size: 14px;" id="gstin">{{$supplier->gstin}}</span></p>
                <p>Phone: {{$supplier->phone1}}</p>
                <p>E-mail: {{$supplier->email}}</p>
              </address>
            </div>
        </div>
    </div>


    

    <!-- Product details -->
    <div class="row">
        <div class="col">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>S.No.</th>
                  <th>Description of Goods</th>
                  <th>HSN/SAC</th>
                  <th>QTY</th>
                  @if(isset($suppInvoice->id))
                    <th>Rate</th>
                    <th>Amount</th>
                  @endif
                  
                  <th>Remarks</th>
                </tr>
              </thead>
               
              <tbody>
                @if(isset($RejectRepairPtable))
                <tr>
                  <td>1</td>
                  <td>{{$RejectRepairPtable->product->code}} - {{$RejectRepairPtable->product->name}}</td>
                  <td>{{$RejectRepairPtable->product->HSN}}</td>
                  <td>{{$RejectRepairPtable->quantity}}</td>
                  @if(isset($suppInvoice->id))
                @php
                    $sip = App\supplierInvoiceProduct::where('supplier_invoice_id', $suppInvoice->id)
                        ->where('product_id', $RejectRepairPtable->product_id)
                        ->first();
                @endphp

                @if($sip)
                    <td>{{ $sip->amount / $sip->quantity }}</td>
                    <td>{{ ($sip->amount / $sip->quantity) * $RejectRepairPtable->quantity }}</td>
                @else
                    <td colspan="2">N/A</td>
                @endif
            @else
                <td colspan="2">N/A</td>
            @endif

                  <td>{{$RejectRepairPtable->remarks}}</td>
                </tr>
                @endif
             
              </tbody>
            </table>
        </div>  
    </div>
     @endif

     

      <!-- Declaration & Signature row -->
      <div class="row">
        <div class="col">
          <table class="table" width="100%" cellspacing="0">
            <tr>
              <td class="col-6">
              </td>
              <td class="col-6" style="vertical-align: bottom;">
                <h3>For Global Vision Direct (P) Ltd</h3>
                  <div style="padding-top: 45px;">___________________________________</div>
                  <div><p>Authorised Signatory</p></div>
              </td>
            </tr>
          </table>
        </div>
      </div>
  </div>

  <div class="text-center">
    <p>
      SUBJECTED TO JAIPUR, RAJASTHAN JURISDICTION<br>
      This is a Computer Generated Document 
    </p>
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