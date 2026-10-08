@extends('layouts.modal')

<body>
    <?php $smhcounts = []; ?>

    <!-- PDF Styling -->
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 100%;
        }

        table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            border: 1px solid black;
        }

        th, td {
            padding: 5px;
            text-align: left;
            border: 1px solid black;
            font-size: 10px;
            word-wrap: break-word;
        }

        /* Adjust column widths to fit within A4 */
        th:nth-child(1), td:nth-child(1) { width: 5%; } /* # */
        th:nth-child(2), td:nth-child(2) { width: 30%; } /* Product Description */
        th:nth-child(3), td:nth-child(3) { width: 10%; } /* QTY */
        th:nth-child(4), td:nth-child(4) { width: 10%; } /* Small Hardware */
        th:nth-child(5), td:nth-child(5) { width: 10%; } /* Total Small Hardware */
        th:nth-child(6), td:nth-child(6) { width: 15%; } /* Name */
        th:nth-child(7), td:nth-child(7) { width: 10%; } /* Rate */
        th:nth-child(8), td:nth-child(8) { width: 10%; } /* Value */
    </style>

    <header>
        <h1 class="text-center">Small Hardware Purchase Order - {{ $supplier->c_name }}</h1>
    </header>

    <div class="container">
        <div class="row box-space">
            <div class="col-6">
                <h3 style="text-decoration: underline;">BILL TO</h3><br />
                @if(isset($companyDetails))
                    <img width="450px" src="{{ url('uploads/' . $companyDetails->logoUrl) }}" alt="Logo" style="width:100px;">
                    <p style="font-size: 24px;"><strong>{{ $companyDetails->c_name }}</strong></p>
                    <address>
                        <p><strong>{{ $companyDetails->address1 }}</strong></p>
                        <p><strong>{{ $companyDetails->address2 }}, {{ $companyDetails->city }} - {{ $companyDetails->postcode }}, India</strong></p>
                        <p>State: {{ $companyDetails->state }}</p>
                        <p>PAN: {{ $companyDetails->pan }}</p>
                        <p>GSTIN: {{ $companyDetails->gstin }}</p>
                        <p>Email: po@artisanfurniture.net</p>
                    </address>
                @endif
            </div>

            <div class="col-6">
                <h1>PO No. {{ $purchaseOrderNo }}</h1>
                <p>PO Date: {{ date('d-M-Y', strtotime($date)) }}</p><br>
                <h3 class="box-space" style="text-decoration: underline;">SUPPLIER</h3>
                <p>{{ $supplier->c_name }}</p>
                <p>{{ $supplier->address1 }}</p>
                <p>{{ $supplier->address2 }}</p>
                <p>{{ $supplier->city }} - {{ $supplier->postcode }}</p>
                <p>State: {{ $supplier->state }}</p>
                <p>GSTIN: {{ $supplier->gstin }}</p>
                <p>Phone: {{ $supplier->phone1 }}</p>
                <p>Email: {{ $supplier->email }}</p>
            </div>
        </div>
        <div class="row box-space">
    <div class="col-6">
        <address>
          <table width="100%" cellspacing="0">
            <tbody>
              <tr>
                <td><h3 style="text-decoration: underline;">DISPATCH TO</h3></td>
              </tr>
              <tr>
                <td><p>Global Vision Direct (P) Ltd</p></td>
              </tr>
              <tr>
                <td><p>Plot# 1216/2, Mahapura Road,</p></td>
              </tr>
              <tr>
                <td><p>Jaipur - Mumbai National Highway,</p></td>
              </tr>
              <tr>
                <td><p>Bhankrota, Jaipur, Rajasthan - 08</p></td>
              </tr>
              <tr>
                <td><p>GSTIN/UIN: {{$companyDetails->gstin}}</p></td>
              </tr>
            </tbody>
          </table>
        </address>
    </div>
    
  </div>
        <!-- Invoice Table -->
        <div class="row mt-3">
            <div class="col">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product Description</th>
                            <th>QTY</th>
                            <th>Small Hardware</th>
                            <th>Total Small Hardware</th>
                            <th>Name</th>
                            <th>Rate</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $grandTotal = 0; $i = 1; @endphp
                        @foreach($invoice as $inv)
                            <tr>
                                <th colspan="8">{{ $inv->buyerorderno }}</th>
                            </tr>
                            @foreach($invoiceTable[$inv->id] ?? [] as $invt)
                                @foreach($pricings[$invt->product_id] ?? [] as $pricing)
                                    @if($pricing->quantity ?? 0 > 0)
                                        @php
                                            $totalValue = ($pricing->price ?? $pricing->smallhardwares->rate ?? 0) * $invt->quantity * $pricing->quantity;
                                            $grandTotal += $totalValue;
                                            $totsmh = $pricing->quantity * $invt->quantity;
                                            $smhcounts[$pricing->smallhardwares->name] = ($smhcounts[$pricing->smallhardwares->name] ?? 0) + $totsmh;
                                        @endphp
                                        <tr>
                                            <td>{{ $i++ }}</td>
                                            <td>{{ $invt->product->code }} - {{ $invt->product->name }}</td>
                                            <td>{{ $invt->quantity }}</td>
                                            <td>{{ $pricing->quantity ?? 0 }}</td>
                                            <td>{{ $pricing->quantity * $invt->quantity }}</td>
                                            <td>{{ $pricing->smallhardwares->name ?? '' }}</td>
                                            <td>{{ $pricing->price ?? $pricing->smallhardwares->rate ?? 0 }}</td>
                                            <td>{{ $totalValue }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endforeach
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6"><strong>Total</strong></td>
                            <td colspan="2"><strong>{{ $grandTotal }}</strong></td>
                        </tr>
                        <tr>
                            <td colspan="8"><strong>Total Small Hardwares</strong></td>
                        </tr>
                        @php $i = 1; @endphp
                        @foreach($smhcounts as $name => $smhcount)
                            <tr>
                                <td>{{ $i++ }}</td>
                                <td colspan="3"><strong>{{ $name }}</strong></td>
                                <td colspan="4">{{ $smhcount }}</td>
                            </tr>
                        @endforeach
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</body>

@if(isset($print) && $print == 1)
    <script type="text/javascript">
        window.print();
    </script>
@endif
