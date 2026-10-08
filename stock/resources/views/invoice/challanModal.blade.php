<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link href="/images/favicon.ico" type="image/x-icon" rel="icon">
    <link href="/images/favicon.ico" type="image/x-icon" rel="shortcut icon">
    <title>Global Vision Direct (P) Ltd</title>
    <link rel="stylesheet" href="style.css" media="screen">
</head>
<style>
    body {
        font-family: Arial, sans-serif;
        padding: 20px;
        font-size: 14px;
    }

    .invoice {
        border: 1px solid #000;
        padding: 20px;
        max-width: 800px;
        margin: auto;
    }

    .header {
        text-align: center;
    }

    .contact {
        font-size: 12px;
    }

    .details-section {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    }

    .invoice-details table {
        font-size: 13px;
    }

    .item-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .item-table th,
    .item-table td {
        border: 1px solid #000;
        padding: 5px;
        text-align: left;
    }

    .note {
        margin-top: 15px;
        font-weight: bold;
    }

    .totals {
        width: 50%;
        margin-top: 20px;
        font-size: 13px;
    }

    .totals td {
        padding: 3px 5px;
    }

    .footer {
        margin-top: 40px;
        text-align: right;
    }

    .receiver-sign {
        margin-top: 30px;
        text-align: left;
    }

    /*
     * Print: avoid Chrome/Edge “broken” corners from border-collapse. Draw the grid with
     * separate + spacing 0: outer top/left on the table, each cell only right + bottom.
     */
    @media print {
        @page {
            margin: 12mm;
        }

        body {
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .invoice {
            border: 1pt solid #000;
            max-width: none;
        }

        hr {
            border: 0;
            border-top: 1pt solid #000;
            height: 0;
        }

        .item-table {
            width: 100%;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            border-top: 1pt solid #000;
            border-left: 1pt solid #000;
            margin-top: 20px;
        }

        .item-table th,
        .item-table td {
            border: none !important;
            border-right: 1pt solid #000 !important;
            border-bottom: 1pt solid #000 !important;
            padding: 5pt 6pt !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .totals {
            width: 50%;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            border-top: 1pt solid #000;
            border-left: 1pt solid #000;
            margin-top: 20px;
        }

        .totals td {
            border: none !important;
            border-right: 1pt solid #000 !important;
            border-bottom: 1pt solid #000 !important;
            padding: 3pt 5pt !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>

<body>

    <div class="invoice">
        <div class="header">
            <h2>{{ $challan->supplier->c_name }}</h2>
            <p>MRFS: Wooden Export Furniture & Handicraft Items</p>
            <p>Factory: {{ $challan->supplier->address1 }},
                {{ $challan->supplier->address2 }},{{ $challan->supplier->city }} {{ $challan->supplier->postcode }}
                ({{ $challan->supplier->state }}) State Code: {{ $challan->supplier->state_code }}</p>
            <p>GSTIN NO. {{ $challan->supplier->gstin }}
            </p>
            <div class="contact">
                <p>📞 {{ $challan->supplier->phone1 }} | 📠 {{ $challan->supplier->phone2 }}</p>
            </div>
        </div>

        <hr>

        <div class="details-section">
            <div class="buyer-details">
                <strong>M/S:</strong> {{ $companyDetails->c_name }}<br>
                {{ $companyDetails->address1 }},{{ $companyDetails->address2 }}, {{ $companyDetails->city }}<br>
                GSTIN: {{ $companyDetails->gstin }} &nbsp;&nbsp;&nbsp; State Code: 08
            </div>
            <div class="invoice-details">
                <table>
                    <tr>
                        <td>Delivery No:</td>
                        <td>{{ $challan->challan_number }}</td>
                    </tr>
                    <tr>
                        <td>Delivery Date:</td>
                        <td>{{ $challan->challan_date }}</td>
                    </tr>
                    <tr>
                        <td>Vehicle No:</td>
                        <td>{{ $challan->vehicle_no }}</td>
                    </tr>
                    <tr>
                        <td>PO No:</td>
                        <td>{{ $challan->purchaseOrder->pono }}</td>
                    </tr>
                    <tr>
                        <td>Date of Supply:</td>
                        <td>{{ $challan->challan_date }}</td>
                    </tr>
                    <tr>
                        <td>Place of Supply:</td>
                        <td>{{ $companyDetails->address1 }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <table class="item-table">
            <thead>
                <tr>
                    <th>S. No.</th>
                    <th>Item Name & Size</th>
                    <th>PO No. / Ref No.</th>
                    <th>Product SKU</th>
                    <th>Qty</th>
                    <th>Rate</th>
                    <th>Amount</th>

                    <th>GST</th>
                    @if ($challan->supplier->state_code == '08')
                        <th>SGST</th>
                        <th>CGST</th>
                    @else
                        <th>IGST </th>
                    @endif
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $i = 1;
                    $grandTotal = 0;
                @endphp

                @foreach ($poTable as $po)
                    @php
                        $grandTotal += (float) ($po->total ?? 0);
                    @endphp
                    <tr>
                        <td>{{ $i++ }}</td>
                        <td>{{ optional($po->consumable)->name ?? 'N/A' }}</td>
                        <td>{{ optional($challan->purchaseOrder)->pono ?? 'N/A' }}</td>
                        <td>{{ optional($po->product)->code ?? 'N/A' }}</td>
                        <td>{{ $po->quantity }}</td>
                        <td>{{ view_amount($po->rate) }}</td>
                        <td>{{ view_amount($po->amount) }}</td>
                        <td>{{ $po->gstslab }}</td>
                        @if ($challan->supplier->state_code == '08')
                            <td>{{ view_amount((float) ($po->gst ?? 0) / 2) }}</td>
                            <td>{{ view_amount((float) ($po->gst ?? 0) / 2) }}</td>
                        @else
                            <td>{{ view_amount($po->gst) }}</td>
                        @endif

                        <td>{{ view_amount($po->total) }}</td>
                    </tr>
                @endforeach
                <tr style="font-weight: bold;">
                    <td colspan="6" style="text-align: right;">Total</td>
                    <td>{{ view_amount($challan->subTotal ?? 0) }}</td>
                    <td>—</td>
                    @if ($challan->supplier->state_code == '08')
                        <td>{{ view_amount(($challan->tgst ?? 0) / 2) }}</td>
                        <td>{{ view_amount(($challan->tgst ?? 0) / 2) }}</td>
                    @else
                        <td>{{ view_amount($challan->tgst ?? 0) }}</td>
                    @endif
                    <td>{{ view_amount($challan->tamount ?? 0) }}</td>
                </tr>
            </tbody>

        </table>

        <p class="note">Note: Goods replaced & returned through Outward Challan No. {{ $challan->challan_number }}
        </p>

        <table class="totals">


            <tr>
                <td>Amount:</td>
                <td>{{ view_amount($challan->subTotal ?? 0) }}</td>
            </tr>
            @if ($challan->supplier->state_code == '08')
                <tr>
                    <td>CGST:</td>
                    <td>{{ view_amount(($challan->tgst ?? 0) / 2) }}</td>
                </tr>
                <tr>
                    <td>SGST:</td>
                    <td>{{ view_amount(($challan->tgst ?? 0) / 2) }}</td>
                </tr>
            @else
                <tr>
                    <td>IGST:</td>
                    <td>{{ view_amount($challan->tgst ?? 0) }}</td>
                </tr>
            @endif

            @php
                $challanIntraState = ($challan->supplier->state_code ?? '') === '08';
                $challanPrintRoundOff = \App\Helpers\PrintAmountHelper::gstRoundOff(
                    (float) ($challan->subTotal ?? 0),
                    (float) ($challan->tgst ?? 0),
                    (float) ($challan->tamount ?? 0),
                    $challanIntraState
                );
            @endphp
            @include('partials.print-gst-roundoff-row', [
                'variant' => 'challan_totals',
                'printRoundOff' => $challanPrintRoundOff,
            ])

            <tr>
                <td><strong>TOTAL:</strong></td>
                <td><strong>{{ view_amount($challan->tamount ?? 0) }}</strong></td>
            </tr>
            <tr>
                <td>₹ in Words:</td>
                <td id="amountInWords">Calculating...</td>
            </tr>
        </table>

        <div class="footer">
            <p>For {{ $challan->supplier->c_name }}</p>
            <p>Prop./Auth. Signatory</p>
            <p class="receiver-sign">Receiver's Signature __________________</p>
        </div>

        <p style="text-align: center; margin-top: 1.25rem; font-size: 12px; color: #555;">This is a Computer Generated Document</p>
    </div>

    <script src="script.js"></script>
    <script>
        function numberToWordsIndian(num) {
            const ones = [
                '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
                'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
                'Seventeen', 'Eighteen', 'Nineteen'
            ];
            const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

            function getWords(n) {
                let word = '';
                if (n > 19) {
                    word += tens[Math.floor(n / 10)] + ' ' + ones[n % 10];
                } else {
                    word += ones[n];
                }
                return word.trim();
            }

            if (num == 0) return 'Zero Rupees';

            let result = '';

            let rupees = Math.floor(num);
            let paise = Math.round((num - rupees) * 100);

            const crore = Math.floor(rupees / 10000000);
            const lakh = Math.floor((rupees % 10000000) / 100000);
            const thousand = Math.floor((rupees % 100000) / 1000);
            const hundred = Math.floor((rupees % 1000) / 100);
            const rest = rupees % 100;

            if (crore) result += getWords(crore) + ' Crore ';
            if (lakh) result += getWords(lakh) + ' Lakh ';
            if (thousand) result += getWords(thousand) + ' Thousand ';
            if (hundred) result += getWords(hundred) + ' Hundred ';
            if (rest) {
                if (result !== '') result += ' ';
                result += getWords(rest) + ' ';
            }

            result = result.trim() + ' Rupees';

            if (paise > 0) {
                result += ' and ' + getWords(paise) + ' Paise';
            }

            return result;
        }

        let grandTotal = {{ (float) ($challan->tamount ?? $grandTotal ?? 0) }};
        document.getElementById('amountInWords').innerText = numberToWordsIndian(grandTotal);
    </script>
    @if (!empty($print))
        <script>
            /* Short delay so layout + amount-in-words are painted before print (matches on-screen table borders). */
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 200);
            });
        </script>
    @endif
</body>

</html>
