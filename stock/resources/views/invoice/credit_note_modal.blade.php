@extends('layouts.modal')
@include('layouts.partials.modal-print-styles', ['printFontSize' => 10])

  <div class="container">

    <!-- first row -->
    <div class="row box-space">    
        <div class="col-6">
          @if(isset($companyDetails))
            <img src="{{ url('uploads/' . $companyDetails->logoUrl) }}" alt="Logo Image" width="100" />
            <address>
              <p>{{$companyDetails->address1}}</p>
              <p>{{$companyDetails->address2}}, {{$companyDetails->city}} - {{$companyDetails->postcode}}</p>
              <p>State Name: {{$companyDetails->state}}</p>
            </address> 
            @endif
        </div>
        <div class="col-6">
            <div class="pull-right" id="supplier">
              @if(isset($credit_note))
              <h1>Credit Note No. {{$credit_note->num}}</h1>
              <h6>Invoice No. {{($credit_note->invoice->invoiceno)}}</h6>
              <h6>Date: {{date('d-M-Y',strtotime($credit_note->created_at))}}</h6>
              @endif
            </div>
        </div>
    </div>

    <!-- second row -->
    <div class="row">
        <div class="col">
            <table class="table table-striped table-bordered" width="100%" cellspacing="0">
              <thead>
                <tr>
                  <th>S.No.</th>
                  <th>Product Description</th>
                  <th>EAN</th>
                  <th>Returned QTY</th>
                  <th>Rate</th>
                  <th>Amount</th>
                  <th>Tax/GST</th>
                  <th>Total Amount</th>
                </tr>
              </thead>
      
              <tbody>
                @if(isset($credit_note_products)) @foreach($credit_note_products as $key => $credit_note_product)
                <tr>
                  <td>{{++$key}}</td>
                  <td>{{$credit_note_product->product->code}} - {{$credit_note_product->product->name}}</td>
                  <td>{{$credit_note_product->product->EAN}}</td>
                  <td>{{$credit_note_product->quantity}}</td>
                  <td>{{$credit_note_product->rate}}</td>
                  <td>{{$credit_note_product->amount}}</td>
                  <td>{{$credit_note_product->tax}}</td>
                  <td>{{$credit_note_product->amount + $credit_note_product->tax}}</td>
                </tr>
                @endforeach @endif

                <tr>
                    <td colspan="3"><b>Total</b></td>
                    <td><b>{{$credit_note->total_quantity}}</b></td>
                    <td><b></b></td>
                    <td><b>{{$credit_note->amount}}</b></td>
                    <td><b>{{$credit_note->total_tax}}</b></td>
                    <td><b>{{$credit_note->total_amount}}</b></td>
                </tr>
              </tbody>
            </table>
        </div>  
    </div>
  </div>  