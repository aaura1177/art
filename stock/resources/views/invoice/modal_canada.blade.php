@extends('layouts.modal')
@include('layouts.partials.modal-print-styles-nested')
<body>
    <div class="container" style="font-size: 18px;">
    <table style="width: 100%;font-family: Arial, Helvetica, sans-serif; " cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding: 40px 30px;">
                <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                    <tr>
                        <td style="border: solid 2px #000;">
                            <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                <tr>
                                    <td>
                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                            <tr>
                                                <td style="width: 50%; text-align: center; line-height: 22px;padding:15px 10px;font-size:18px;">
                                                    <div style="text-align:center;margin-bottom:24px;">
                                                        <img style="max-width: 240px;" src="{{ Storage::disk('s3')->url('stock/images/gvllogo.png') }}"/>
                                                    </div>
                                                    Global Vision Direct Ltd.<br/>
                                                    {{$companyDetails->address1}}<br/>
                                                    {{$companyDetails->address2}}<br/>
                                                    {{$companyDetails->postcode}}, {{$companyDetails->city}}, {{$companyDetails->country}}<br/>
                                                    Tel # {{$companyDetails->phone1}}<br/>
                                                    {{$companyDetails->state}}<br/>
                                                    {{$companyDetails->vat}}<br/>
                                                </td>
                                                <td style="width: 50%; border-left: solid 2px #000;vertical-align: top;">
                                                    <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                                        <tr>
                                                            <td style="text-align: center; font-size: 50px; font-weight: 700;padding: 10px 5px;">Invoice</td>
                                                        </tr>
                                                    </table>
                                                    <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;border-top: solid 2px #000; ">
                                                        <tr>
                                                            <td style="width:50%;padding: 3px 5px; font-weight: 600;">Invoice No.</td>
                                                            <td style="width:50%;padding: 3px 5px;">{{$invoice->invoiceno}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="width:50%;padding: 3px 5px; font-weight: 600;">Date :</td>
                                                            <td style="width:50%;padding: 3px 5px;">{{date('d-M-Y', strtotime($invoice->date))}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="width:50%;padding: 3px 5px; font-weight: 600;">Buyer's Order # </td>
                                                            <td style="width:50%;padding: 3px 5px;">{{$invoice->buyerorderno}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="width:50%;padding: 3px 5px; font-weight: 600;">Container Size :</td>
                                                            <td style="width:50%;padding: 3px 5px;">{{$invoice->container_size}}</td>
                                                        </tr>
                                                    </table>
                                                    <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;border-top: solid 2px #000;">
                                                        <tr>
                                                            <td style="width:50%;padding: 3px 5px; font-weight: 600;">Delivery Term</td>
                                                            <td style="width:50%;padding: 3px 5px;">{{$invoice->delivery_term}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="width:50%;padding: 3px 5px; font-weight: 600;">Payment Term : </td>
                                                            <td style="width:50%;padding: 3px 5px;">{{$invoice->payterms}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="width:50%;padding: 3px 5px; font-weight: 600;">Shipment :</td>
                                                            <td style="width:50%;padding: 3px 5px;">{{$invoice->shipmentby}}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <!-- Second Row -->
                                <tr>
                                    <td style="border-top: solid 2px #000;">
                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                            <tr>
                                                <td style="width: 30%; text-align: center; padding-top: 5px;">
                                                    <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                                        <tr>
                                                            <td style="width: 30%;padding:6px 10px;">
                                                                <div style="text-align:center;">
                                                                    <img style="max-width: 80px;" src="{{ Storage::disk('s3')->url('stock/images/logo-img1.png') }}"/>
                                                                </div>
                                                            </td>
                                                            <td style="width:70%; text-align:center; line-height: 22px;font-size:12px;">
                                                                Fabric, Foam & <br/>
                                                                Interliner confirm to FR <br/>
                                                                standard BS 5852
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td style="width: 40%; text-align: center; padding-top: 5px;border-left: solid 2px #000;">
                                                    <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                                        <tr>
                                                            <td style="width: 30%;padding:6px 6px;">
                                                                <div style="text-align:center;">
                                                                    <img style="max-width: 80px;" src="{{ Storage::disk('s3')->url('stock/gmp.jpeg') }}"/>
                                                                </div>
                                                            </td>
                                                            <td style="width:70%; text-align:center; line-height: 22px;font-size:12px;">
                                                                Good Manufacturing Practice (GMP) Certified<br>
                                                                Certificate Number: QVA-GLHV-22-2314534
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td style="width: 30%; border-left: solid 2px #000;vertical-align: top;">
                                                    
                                                    <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                                        <tr>
                                                            <td style="width: 30%; padding:6px 6px;">
                                                                <div style="text-align:center;">
                                                                    <img style="max-width: 80px;" src="{{ Storage::disk('s3')->url('stock/vriksh-logo.png') }}"/>
                                                                </div>
                                                            </td>
                                                            <td style="width:70%; text-align:center; line-height: 22px;font-size:12px;">
                                                                {{$certificate->name}} <br/>
                                                                {!!nl2br($certificate->description)!!}
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="border-top: solid 2px #000;">
                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                            <tr>
                                                <td style="width: 30%; text-align: center; padding-top: 5px;" rowspan="3">
                                                    <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                                        <tr>
                                                            <td style="width: 30%;padding:10px 10px;">
                                                                <div style="text-align:center;">
                                                                    <img style="max-width: 140px;" src="{{ Storage::disk('s3')->url('stock/images/sedex.png') }}"/>
                                                                </div>
                                                            </td>
                                                            <td style="width:70%; text-align:center; line-height: 22px;font-size:14px;">
                                                            	Socially Compliant Factory under audit code ZC411331401
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <!-- Third Row -->
                                <tr>
                                    <td style="border-top: solid 2px #000;">
                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                            <tr>
                                                <td style="width: 30%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    Bill To 
                                                </td>
                                                <td style="width: 40%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    Ship To 
                                                </td>
                                                <td style="width: 30%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    Shipping Agent, if any
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 30%; text-align: center; padding: 10px 3px; line-height: 20px;">
                                                    {!!nl2br($invoice->bill_to)!!}
                                                </td>
                                                <td style="width: 40%; text-align: center; padding: 10px 3px;border-left:solid 2px #000; line-height: 20px;">
                                                    {!!nl2br($invoice->ship_to)!!}
                                                </td>
                                                <td style="width: 30%; text-align: center; padding: 10px 3px;border-left:solid 2px #000; line-height: 20px;">
                                                    <strong>{{$invoice->agent_name}}</strong>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-top: solid 2px #000;">
                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0; font-size: 14px;">
                                            <tr>
                                                <td style="padding: 15px 3px;background: #f2f2f2;text-align: center; line-height: 20px;font-size:16px;">
                                                    <strong>Bank Details :</strong> 
                                                    @php
    $bankDetails = match($invoice->currency) {
        '£' => $companyDetails->bank_details,
        '€' => $companyDetails->bank_details_euro,
        '$' => $companyDetails->bank_details_dollar,
        'C$' => $companyDetails->bank_details,
        default => $companyDetails->bank_details,
    };
@endphp

{{ $bankDetails }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-top: solid 2px #000;">
                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                            <tr>
                                                <td style="width: 30%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    Container #
                                                </td>
                                                <td style="width: 40%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    Total Packages
                                                </td>
                                                <td style="width: 30%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    Description of Goods
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 30%; text-align: center; padding: 5px 5px; line-height: 24px;">
                                                    {{$invoice->containerno}}
                                                </td>
                                                <td style="width: 40%; text-align: center; padding: 5px 5px;border-left:solid 2px #000; line-height: 24px;">
                                                    {{$invoice->totalbox}}
                                                </td>
                                                <td style="width: 30%; text-align: center; padding: 5px 5px;border-left:solid 2px #000; line-height: 24px;">
                                                    {{$invoice->desgoods}} 
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-top: solid 2px #000;">
                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                            <tr>
                                                <td style="width:45%; text-align: center; padding: 10px 3px; font-weight: 700;">
                                                    {{$invoice->desgoods}} 
                                                </td>
                                                <td style="width:25%; text-align: center; padding: 5px 3px; font-weight: 700; border-left:solid 2px #000;">
                                                    Quantity
                                                </td>
                                                <td style="width:10%; text-align: center; padding: 5px 3px; font-weight: 700; border-left:solid 2px #000;">
                                                    Rate {{$invoice->currency}}
                                                </td>
                                                <td style="width:20%; text-align: center; padding: 5px 3px; font-weight: 700; border-left:solid 2px #000;">
                                                    Amount {{$invoice->currency}}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="text-align: center; padding: 5px 5px; font-weight: 700;">
                                                    Value of Goods
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    {{$invoice->totalamount - $invoice->packing_charges + $invoice->discount - $invoice->shipping_charges}}
                                                </td>
                                            </tr>
                                            @if($invoice->shipping_charges)
                                            <tr>
                                                <td style="text-align: center; padding: 5px 5px; font-weight: 700;">
                                                    Ocean Freight
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    {{$invoice->shipping_charges}}
                                                </td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td style="text-align: center; padding: 2px 5px; font-weight: 700;">
                                                    Packing Charges
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000; font-weight: 700;">
                                                    {{number_format($invoice->packing_charges,2,'.','')}}
                                                </td>
                                            </tr>
                                            @if($invoice->discount)
                                            <tr>
                                                <td style="text-align: center; padding: 2px 5px; font-weight: 500;">
                                                    Discount
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000; font-weight: 500;">
                                                    {{number_format($invoice->discount,2,'.','')}}
                                                </td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td style="text-align: center; padding: 5px 5px;">
                                                    Add: VAT @ {{ ($invoice->vat)}}%
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    {{ ($invoice->totalamount*($invoice->vat/100))}}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="text-align: center; padding: 5px 5px; font-weight: 700;">
                                                    Total Amount
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000; font-weight: 700;">
                                                    {{ $invoice->totalamount + ($invoice->totalamount*($invoice->vat/100)) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="text-align: center; padding: 5px 5px;">
                                                    @if($invoice->deposit_date)
                                                    Deposit received on {{date('d F Y',strtotime($invoice->deposit_date)) }}
                                                    @endif
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    @if($invoice->deposit_date)
                                                    {{ $invoice->deposit }}
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="text-align: center; padding: 15px 5px; font-weight: 700; font-size: 15px;">
                                                    Balance Payable
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; border-left:solid 2px #000; font-weight: 700;">
                                                    {{ $invoice->totalamount + ($invoice->totalamount*($invoice->vat/100)) - $invoice->deposit }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="text-align: center; padding: 12px 5px; font-weight: 700; border-top:solid 2px #000;">
                                                    TOTAL
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; font-weight: 700; border-left:solid 2px #000; border-top:solid 2px #000;">
                                                    {{ $invoice->totalquantity }}
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; font-weight: 700; border-left:solid 2px #000; border-top:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; font-weight: 700; border-left:solid 2px #000; border-top:solid 2px #000;">
                                                    {{ $invoice->totalamount + ($invoice->totalamount*($invoice->vat/100)) }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-top: solid 2px #000;">
                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                            <tr>
                                                <td style="width:70%; text-align: left; padding: 15px 3px; font-size: 16px; line-height: 24px;">
                                                    <strong>As a part of CANADA's Waste Packaging regulations the total weight of packaging material</strong> <br/>
                                                    <strong>againts this shipment is (A-B) = &nbsp;&nbsp;&nbsp;{{$invoice->totalwt}}</strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Kg  <br/>
                                                    We declare the above information is true & correct.
                                                </td>
                                                <td style="width:30%; text-align: left; padding: 12px 3px; border-left:solid 2px #000; line-height: 24px;font-size:16px;">
                                                    <strong>Signature & Date</strong> <br/>
                                                    {{ date('d-m-Y',strtotime($invoice->date)) }} <br/>
                                                    <strong>Authorized Signatory</strong>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <!-- Page Second 2nd -->
        <tr>
            <td style="padding: 40px 30px;">
                <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                    <tr>
                        <td style="border: solid 2px #000;">
                            
                            <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                <thead>
                                    <tr>
                                        <td colspan="5">
                                            <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;border-top:1px solid #000;border-bottom: solid 2px #000;">
                                                <tr>
                                                    <td style="width: 50%; text-align: center; line-height: 22px;padding:15px 10px;font-size:18px;">
                                                        <div style="text-align:center;margin-bottom:20px;">
                                                            <img style="max-width: 240px;" src="{{ Storage::disk('s3')->url('stock/images/gvllogo.png') }}"/>
                                                        </div>
                                                        Global Vision Direct Ltd.<br/>
                                                        {{$companyDetails->address1}}<br/>
                                                        {{$companyDetails->address2}}<br/>
                                                        {{$companyDetails->postcode}}, {{$companyDetails->city}}, {{$companyDetails->country}}<br/>
                                                        Tel # {{$companyDetails->phone1}}<br/>
                                                        {{$companyDetails->state}}<br/>
                                                        {{$companyDetails->vat}}<br/>
                                                    </td>
                                                    <td style="width: 50%; border-left: solid 2px #000;vertical-align: top;">
                                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                                            <tr>
                                                                <td style="text-align: center; font-size: 30px; font-weight: 700;padding: 7px 5px;">Invoice</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="text-align: center; font-size: 20px; font-weight: 700;padding: 7px 5px;">Continuation Sheet - 1</td>
                                                            </tr>
                                                        </table>
                                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;border-top: solid 2px #000; ">
                                                            <tr>
                                                                <td style="width:50%;padding: 3px 5px; font-weight: 600;">Invoice No.</td>
                                                                <td style="width:50%;padding: 3px 5px;">{{$invoice->invoiceno}}</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:50%;padding: 3px 5px; font-weight: 600;">Date :</td>
                                                                <td style="width:50%;padding: 3px 5px;">{{$invoice->date}}</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:50%;padding: 3px 5px; font-weight: 600;">Buyer's Order # </td>
                                                                <td style="width:50%;padding: 3px 5px;">{{$invoice->buyerorderno}}</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:50%;padding: 3px 5px; font-weight: 600;">Container Size :</td>
                                                                <td style="width:50%;padding: 3px 5px;">{{$invoice->container_size}}</td>
                                                            </tr>
                                                        </table>
                                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;border-top: solid 2px #000;">
                                                            <tr>
                                                                <td style="width:50%;padding: 3px 5px; font-weight: 600;">Delivery Term</td>
                                                                <td style="width:50%;padding: 3px 5px;">{{$invoice->delivery_term}}</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:50%;padding: 3px 5px; font-weight: 600;">Payment Term : </td>
                                                                <td style="width:50%;padding: 3px 5px;">{{$invoice->payterms}}</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:50%;padding: 3px 5px; font-weight: 600;">Shipment :</td>
                                                                <td style="width:50%;padding: 3px 5px;">{{$invoice->shipmentby}}</td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </thead>
                                <tr>
                                    <td style="width: 10%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                        Code
                                    </td>
                                    <td style="width: 40%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;border-left:solid 2px #000;">
                                        Description
                                    </td>
                                    <td style="width: 16.6666%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;border-left:solid 2px #000;">
                                        Quantity
                                    </td>
                                    <td style="width: 16.6666%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;border-left:solid 2px #000;">
                                        Rate {{$invoice->currency}} 
                                    </td>
                                    <td style="width: 16.6666%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;border-left:solid 2px #000;">
                                        Amount {{$invoice->currency}}
                                    </td>
                                </tr>
                                <?php $j = 55; ?>
                                @if(isset($invoiceTable)) @foreach($invoiceTable as $key => $invoiceTable)
                                <tr>
                                    <td style="text-align: center; padding: 5px 3px; line-height: 20px;">
                                        {{$invoiceTable->product->code}}
                                    </td>
                                    <td style="text-align: center; padding: 5px 3px; line-height: 20px; border-left:solid 2px #000;">
                                        {{$invoiceTable->product->name}} 
                                    </td>
                                    <td style="text-align: center; padding: 5px 3px; line-height: 20px; border-left:solid 2px #000;">
                                        {{$invoiceTable->quantity}}
                                    </td>
                                    <td style="text-align: center; padding: 5px 3px; line-height: 20px; border-left:solid 2px #000;">
                                        {{$invoiceTable->rate}}
                                    </td>
                                    <td style="text-align: center; padding: 5px 3px; line-height: 20px; border-left:solid 2px #000;">
                                        {{$invoiceTable->amount}}
                                    </td>
                                </tr>
                                <?php $j--; ?>
                                @endforeach @endif
                                @for($i = 0;$i<$j;$i++)
                                <tr>
                                    <td style="text-align: center; padding: 5px 3px; line-height: 20px;">
                                        
                                    </td>
                                    <td style="text-align: center; padding: 5px 3px; line-height: 20px; border-left:solid 2px #000;">
                                            
                                    </td>
                                    <td style="text-align: center; padding: 5px 3px; line-height: 20px; border-left:solid 2px #000;">
                                        
                                    </td>
                                    <td style="text-align: center; padding: 5px 3px; line-height: 20px; border-left:solid 2px #000;">
                                        
                                    </td>
                                    <td style="text-align: center; padding: 5px 3px; line-height: 20px; border-left:solid 2px #000;">
                                        
                                    </td>
                                </tr>
                                @endfor
                                <tr>
                                    <td style="text-align: center; padding: 2p5pxx 3px; line-height: 20px;">
                                        &nbsp;
                                    </td>
                                    <td style="text-align: center; padding: 5px 3px; line-height: 20px; border-left:solid 2px #000;">
                                        &nbsp; 
                                    </td>
                                    <td style="text-align: center; padding: 5px 3px; line-height: 20px; font-weight:700; border-top: solid 2px #000; border-left: solid 2px #000;">
                                        {{$invoice->totalquantity}}
                                    </td>
                                    <td style="text-align: center; padding: 5px 3px; line-height: 20px; font-weight:700; border-top: solid 2px #000; border-left: solid 2px #000;">
                                        Total
                                    </td>
                                    <td style="text-align: center; padding: 5px 3px; line-height: 20px; font-weight:700; border-top: solid 2px #000; border-left: solid 2px #000;">
                                        {{$invoice->totalamount - $invoice->packing_charges + $invoice->discount - $invoice->shipping_charges}}
                                    </td>
                                </tr>
                            </table>
                            <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;border-top: solid 2px #000;">
                                <tr>
                                    <td style="width:70%; text-align: left; padding: 2px 3px; font-size: 14px; line-height: 24px;">
                                        
                                    </td>
                                    <td style="width:30%; text-align: left; padding: 22px 3px; border-left:solid 2px #000; line-height: 24px;">
                                        <strong>Signature & Date</strong> <br/>
                                        {{ date('d-m-Y',strtotime($invoice->date)) }} <br/>
                                        <strong>Authorized Signatory</strong>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <!-- Page Third 3rd -->
        <tr>
            <td style="padding: 40px 30px;">
                <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                    <tr>
                        <td style="border: solid 2px #000;">
                            <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                <tr>
                                    <td>
                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                            <tr>
                                                <td style="width: 50%; text-align: center; line-height: 22px;padding:22px 10px;">
                                                    <div style="text-align:center;margin-bottom:20px;">
                                                        <img style="max-width: 240px;" src="{{ Storage::disk('s3')->url('stock/images/gvllogo.png') }}"/>
                                                    </div>
                                                    Global Vision Direct Ltd.<br/>
                                                    {{$companyDetails->address1}}<br/>
                                                    {{$companyDetails->address2}}<br/>
                                                    {{$companyDetails->postcode}}, {{$companyDetails->city}}, {{$companyDetails->country}}<br/>
                                                    Tel # {{$companyDetails->phone1}}<br/>
                                                    {{$companyDetails->state}}<br/>
                                                    {{$companyDetails->vat}}<br/>
                                                </td>
                                                <td style="width: 50%; border-left: solid 2px #000;vertical-align: top;">
                                                    <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                                        <tr>
                                                            <td style="text-align: center; font-size: 50px; font-weight: 700;padding: 10px 5px;">Packing List</td>
                                                        </tr>
                                                    </table>
                                                    <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;border-top: solid 2px #000; ">
                                                        <tr>
                                                            <td style="width:50%;padding: 3px 5px; font-weight: 600;">Invoice No.</td>
                                                            <td style="width:50%;padding: 3px 5px;">{{$invoice->invoiceno}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="width:50%;padding: 3px 5px; font-weight: 600;">Date :</td>
                                                            <td style="width:50%;padding: 3px 5px;">{{ date('d-m-Y',strtotime($invoice->date)) }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="width:50%;padding: 3px 5px; font-weight: 600;">Buyer's Order # </td>
                                                            <td style="width:50%;padding: 3px 5px;">{{$invoice->buyerorderno}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="width:50%;padding: 3px 5px; font-weight: 600;">Container Size :</td>
                                                            <td style="width:50%;padding: 3px 5px;">{{$invoice->container_size}}</td>
                                                        </tr>
                                                    </table>
                                                    <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;border-top: solid 2px #000;">
                                                        <tr>
                                                            <td style="width:50%;padding: 3px 5px; font-weight: 600;">Delivery Term</td>
                                                            <td style="width:50%;padding: 3px 5px;">{{$invoice->delivery_term}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="width:50%;padding: 3px 5px; font-weight: 600;">Payment Term : </td>
                                                            <td style="width:50%;padding: 3px 5px;">{{$invoice->payterms}}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="width:50%;padding: 3px 5px; font-weight: 600;">Shipment :</td>
                                                            <td style="width:50%;padding: 3px 5px;">{{$invoice->shipmentby}}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <!-- Third Row -->
                                <tr>
                                    <td style="border-top: solid 2px #000;">
                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                            <tr>
                                                <td style="width: 30%; text-align: center; padding: 12px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    Bill To 
                                                </td>
                                                <td style="width: 40%; text-align: center; padding: 12px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    Ship To 
                                                </td>
                                                <td style="width: 30%; text-align: center; padding: 12px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    Shipping Agent, if any
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 30%; text-align: center; padding: 22px 3px; line-height: 20px;">
                                                    {!!nl2br($invoice->bill_to)!!}
                                                </td>
                                                <td style="width: 40%; text-align: center; padding: 22px 3px;border-left:solid 2px #000; line-height: 20px;">
                                                    {!!nl2br($invoice->ship_to)!!}
                                                </td>
                                                <td style="width: 30%; text-align: center; padding: 22px 3px;border-left:solid 2px #000; line-height: 20px;">
                                                    <strong>{{$invoice->agent_name}}</strong>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-top: solid 2px #000;">
                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                            <tr>
                                                <td style="width: 30%; text-align: center; padding: 2px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    Container #
                                                </td>
                                                <td style="width: 40%; text-align: center; padding: 2px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    Total Packages
                                                </td>
                                                <td style="width: 30%; text-align: center; padding: 2px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    Description of Goods
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 30%; text-align: center; padding: 7px 5px; line-height: 24px;">
                                                    {{$invoice->containerno}}
                                                </td>
                                                <td style="width: 40%; text-align: center; padding: 7px 5px;border-left:solid 2px #000; line-height: 24px;">
                                                    {{$invoice->totalbox}}
                                                </td>
                                                <td style="width: 30%; text-align: center; padding: 7px 5px;border-left:solid 2px #000; line-height: 24px;">
                                                    {{$invoice->desgoods}}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-top: solid 2px #000;">
                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                            <tr>
                                                <td style="width: 10%; text-align: center; padding: 2px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    
                                                </td>
                                                <td style="width: 40%; text-align: center; padding: 2px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="width: 16.6666%; text-align: center; padding: 2px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;border-left:solid 2px #000;">
                                                    Quantity
                                                </td>
                                                <td style="width: 16.6666%; text-align: center; padding: 2px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;border-left:solid 2px #000;">
                                                    Box #
                                                </td>
                                                <td style="width: 16.6666%; text-align: center; padding: 2px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;border-left:solid 2px #000;">
                                                   
                                                </td>
                                            </tr>
                                            @for($i = 0;$i<50;$i++)
                                            <tr>
                                                <td style="text-align: center; padding: 5px 5px; font-weight: 700;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 5px 5px; font-weight: 700;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 5px 5px; font-weight: 700; border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 5px 5px; font-weight: 700; border-left:solid 2px #000;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 5px 5px; font-weight: 700; border-left:solid 2px #000;">
                                                    
                                                </td>
                                            </tr>
                                            @endfor
                                            <tr>
                                                <td style="text-align: center; padding: 2px 5px; font-weight: 700;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; font-weight: 700;">
                                                    
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; font-weight: 700; border-left:solid 2px #000; border-top:solid 2px #000;">
                                                    {{$invoice->totalquantity}}
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; font-weight: 700; border-left:solid 2px #000; border-top:solid 2px #000;">
                                                    {{$invoice->totalbox}}
                                                </td>
                                                <td style="text-align: center; padding: 2px 5px; font-weight: 700; border-left:solid 2px #000; border-top:solid 2px #000;">
                                                    
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border-top: solid 2px #000;">
                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                            <tr>
                                                <td style="width:70%; text-align: left; padding: 22px 3px; font-size: 14px; line-height: 20px;">
                                                    Total Pkgs {{$invoice->totalbox}} <br/>
                                                    Total Pieces : {{$invoice->totalquantity}} <br/>
                                                    Gross Wt (Kg) {{$invoice->totalgrosswt}} <br/>
                                                    Net Wt. (Kg) {{$invoice->totalwt}}
                                                </td>
                                                <td style="width:30%; text-align: left; padding: 22px 3px; border-left:solid 2px #000; line-height: 24px;">
                                                    <strong>Signature & Date</strong> <br/>
                                                    {{date('d-m-Y',strtotime($invoice->date))}} <br/>
                                                    <strong>Authorized Signatory</strong>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <!-- Page Forth 4th -->
        <tr>
            <td style="padding: 40px 30px;">
                <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                    <tr>
                        <td style="border: solid 2px #000;">
                            
                            <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                <thead>
                                    <tr>
                                        <td colspan="5">
                                            <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;border-top:1px solid #000;border-bottom: solid 2px #000">
                                                <tr>
                                                    <td style="width: 50%; text-align: center; line-height: 22px;padding:22px 10px;">
                                                        <div style="text-align:center;margin-bottom:20px;">
                                                            <img style="max-width: 240px;" src="{{ Storage::disk('s3')->url('stock/images/gvllogo.png') }}"/>
                                                        </div>
                                                        Global Vision Direct Ltd.<br/>
                                                        {{$companyDetails->address1}}<br/>
                                                        {{$companyDetails->address2}}<br/>
                                                        {{$companyDetails->postcode}}, {{$companyDetails->city}}, {{$companyDetails->country}}<br/>
                                                        Tel # {{$companyDetails->phone1}}<br/>
                                                        {{$companyDetails->state}}<br/>
                                                        {{$companyDetails->vat}}<br/>
                                                    </td>
                                                    <td style="width: 50%; border-left: solid 2px #000;vertical-align: top;">
                                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                                            <tr>
                                                                <td style="text-align: center; font-size: 50px; font-weight: 700;padding: 10px 5px;">Packing List</td>
                                                            </tr>
                                                        </table>
                                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;border-top: solid 2px #000; ">
                                                            <tr>
                                                                <td style="width:50%;padding: 3px 5px; font-weight: 600;">Invoice No.</td>
                                                                <td style="width:50%;padding: 3px 5px;">{{$invoice->invoiceno}}</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:50%;padding: 3px 5px; font-weight: 600;">Date :</td>
                                                                <td style="width:50%;padding: 3px 5px;">{{ date('d-m-Y',strtotime($invoice->date)) }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:50%;padding: 3px 5px; font-weight: 600;">Buyer's Order # </td>
                                                                <td style="width:50%;padding: 3px 5px;">{{$invoice->buyerorderno}}</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:50%;padding: 3px 5px; font-weight: 600;">Container Size :</td>
                                                                <td style="width:50%;padding: 3px 5px;">{{$invoice->container_size}}</td>
                                                            </tr>
                                                        </table>
                                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;border-top: solid 2px #000;">
                                                            <tr>
                                                                <td style="width:50%;padding: 3px 5px; font-weight: 600;">Delivery Term</td>
                                                                <td style="width:50%;padding: 3px 5px;">{{$invoice->delivery_term}}</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:50%;padding: 3px 5px; font-weight: 600;">Payment Term : </td>
                                                                <td style="width:50%;padding: 3px 5px;">{{$invoice->payterms}}</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="width:50%;padding: 3px 5px; font-weight: 600;">Shipment :</td>
                                                                <td style="width:50%;padding: 3px 5px;">{{$invoice->shipmentby}}</td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </thead>
                                <tr>
                                    <td colspan="5">
                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0;">
                                            <tr>
                                                <td style="width: 30%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    Bill To 
                                                </td>
                                                <td style="width: 40%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    Ship To 
                                                </td>
                                                <td style="width: 30%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    Shipping Agent, if any
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 30%; text-align: center; padding: 12px 3px; line-height: 20px;">
                                                    {!!nl2br($invoice->bill_to)!!}
                                                </td>
                                                <td style="width: 40%; text-align: center; padding: 12px 3px;border-left:solid 2px #000; line-height: 20px;">
                                                    {!!nl2br($invoice->ship_to)!!}
                                                </td>
                                                <td style="width: 30%; text-align: center; padding: 12px 3px;border-left:solid 2px #000; line-height: 20px;">
                                                    <strong>{{$invoice->agent_name}}</strong>
                                                </td>
                                            </tr>
                                        </table>
                                        <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0; border-top: solid 2px #000; border-bottom: solid 2px #000">
                                            <tr>
                                                <td style="width: 30%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    Container #
                                                </td>
                                                <td style="width: 40%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    Total Packages
                                                </td>
                                                <td style="width: 30%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                                    Description of Goods
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 30%; text-align: center; padding: 7px 5px; line-height: 24px;">
                                                    {{$invoice->containerno}}
                                                </td>
                                                <td style="width: 40%; text-align: center; padding: 7px 5px;border-left:solid 2px #000; line-height: 24px;">
                                                    {{$invoice->totalbox}}
                                                </td>
                                                <td style="width: 30%; text-align: center; padding: 7px 5px;border-left:solid 2px #000; line-height: 24px;">
                                                    {{$invoice->desgoods}} 
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 10%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;">
                                        Code
                                    </td>
                                    <td style="width: 40%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;border-left:solid 2px #000;">
                                        Product
                                    </td>
                                    <td style="width: 16.6666%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;border-left:solid 2px #000;">
                                        Quantity
                                    </td>
                                    <td style="width: 16.6666%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;border-left:solid 2px #000;">
                                        Box #
                                    </td>
                                    <td style="width: 16.6666%; text-align: center; padding: 5px 3px;border-bottom:solid 2px #000;background: #f2f2f2; font-weight: 700;border-left:solid 2px #000;">
                                        Qty / Box
                                    </td>
                                </tr>
                                <?php $j = 25; ?>
                                @if(isset($packing_list_products)) @foreach($packing_list_products as $packing_list_product)
                                <tr>
                                    <td style="text-align: center; padding: 4px 5px; ">
                                        {{$packing_list_product->product->code}}
                                    </td>
                                    <td style="text-align: center; padding: 4px 5px; border-left:solid 2px #000;">
                                        {{$packing_list_product->product->name}}
                                    </td>
                                    <td style="text-align: center; padding: 4px 5px; border-left:solid 2px #000;">
                                        {{$packing_list_product->quantity}}
                                    </td>
                                    <td style="text-align: center; padding: 4px 5px; border-left:solid 2px #000;">
                                        {{$packing_list_product->box}} - {{$packing_list_product->endBox}}
                                    </td>
                                    <td style="text-align: center; padding: 4px 5px; border-left:solid 2px #000;">
                                        {{$packing_list_product->qtybox}} Piece per box
                                    </td>
                                </tr>
                                <?php $j--; ?>
                                @endforeach @endif
                                @for($i = 0;$i<$j;$i++)
                                <tr>
                                    <td style="text-align: center; padding: 4px 5px; font-weight: 700;">
                                        
                                    </td>
                                    <td style="text-align: center; padding: 4px 5px; font-weight: 700;border-left:solid 2px #000;">
                                        
                                    </td>
                                    <td style="text-align: center; padding: 4px 5px; font-weight: 700; border-left:solid 2px #000;">
                                        
                                    </td>
                                    <td style="text-align: center; padding: 4px 5px; font-weight: 700; border-left:solid 2px #000;">
                                        
                                    </td>
                                    <td style="text-align: center; padding: 4px 5px; font-weight: 700; border-left:solid 2px #000;">
                                        
                                    </td>
                                </tr>
                                @endfor
                                <tr>
                                    <td style="text-align: center; padding: 4px 5px; font-weight: 700;">
                                        
                                    </td>
                                    <td style="text-align: center; padding: 4px 5px; font-weight: 700;border-left:solid 2px #000;">
                                        
                                    </td>
                                    <td style="text-align: center; padding: 4px 5px; font-weight: 700; border-left:solid 2px #000; border-top:solid 2px #000;">
                                        {{$invoice->totalquantity}}
                                    </td>
                                    <td style="text-align: center; padding: 4px 5px; font-weight: 700; border-left:solid 2px #000; border-top:solid 2px #000;">
                                        {{$invoice->totalbox}}
                                    </td>
                                    <td style="text-align: center; padding: 4px 5px; font-weight: 700; border-left:solid 2px #000; border-top:solid 2px #000;">
                                        
                                    </td>
                                </tr>
                            </table>
                            <table cellpadding="0" cellspacing="0" border="0" style="width: 100%;padding: 0; border-top: solid 2px #000">
                                <tr>
                                    <td style="width:70%; text-align: left; padding: 12px 3px; font-size: 14px; line-height: 20px;">
                                        Total Pkgs {{$invoice->totalbox}} <br/>
                                        Total Pieces : {{$invoice->totalbox}} <br/>
                                        Gross Wt (Kg) {{$invoice->totalgrosswt}} <br/>
                                        Net Wt. (Kg) {{$invoice->totalwt}}<br/>
                                        <strong>As a part of CANADA's Waste Packaging regulations the total weight of packaging material</strong> <br/>
                                        <strong>againts this shipment is (A-B) = &nbsp;&nbsp;&nbsp;{{$invoice->totalwt}}</strong> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Kg  <br/>
                                        We declare the above information is true & correct.
                                    </td>
                                    <td style="width:30%; text-align: left; padding: 12px 3px; border-left:solid 2px #000; line-height: 24px;">
                                        <strong>Signature & Date</strong> <br/>
                                        {{date('d-m-Y',strtotime($invoice->date))}} <br/>
                                        <strong>Authorized Signatory</strong>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
</body>