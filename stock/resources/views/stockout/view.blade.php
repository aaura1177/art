@extends('layouts.modal')

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
              @if(isset($stockout))
              <h1>Stock No. {{$stockout->id}}</h1>
              <h6>Invoice No. {{($stockout->invoice_id)?$stockout->invoice->invoiceno:$stockout->buyer_ref_no}}</h6>
              <h6>Buyer Ref. No: {{$stockout->buyer_ref_no}}</h6>
              <h6>Date: {{date('d-M-Y',strtotime($stockout->created_at))}}</h6>
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
                  <th>QTY</th>
                  <th>Receive QTY</th>
                  <th>Remaining QTY</th>
                  <th>Location</th>
                </tr>
              </thead>
      
              <tbody>
                @if(isset($stockoutTable)) @foreach($stockoutTable as $key => $stockoutTable)
                <tr>
                  <td>{{++$key}}</td>
                  <td>{{$stockoutTable->product->code}} - {{$stockoutTable->product->name}}</td>
                  <td>{{$stockoutTable->product->EAN}}</td>
                  <td>{{$stockoutTable->orderqty}}</td>
                  <td>{{$stockoutTable->receiveqty}}</td>
                  <td>{{$stockoutTable->remainingqty}}</td>
                  <td>{{$stockoutTable->location}}</td>
                </tr>
                @endforeach @endif
              </tbody>
            </table>
        </div>  
    </div>
  </div>  