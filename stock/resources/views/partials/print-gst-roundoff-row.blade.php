@php
    use App\Helpers\PrintAmountHelper;
    $showPrintRoundOff = isset($showPrintRoundOff)
        ? $showPrintRoundOff
        : PrintAmountHelper::shouldShowRoundOff((float) ($printRoundOff ?? 0));
    $roundOffDisplay = view_amount((float) ($printRoundOff ?? 0));
@endphp
@if ($showPrintRoundOff)
    @if ($variant === 'challan_totals')
        <tr>
            <td>Round Off:</td>
            <td>{{ $roundOffDisplay }}</td>
        </tr>
    @elseif ($variant === 'pb_label_amount')
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td><strong>Round Off (₹)</strong></td>
            <td><strong>{{ $roundOffDisplay }}</strong></td>
            <td><strong></strong></td>
            <td><strong></strong></td>
        </tr>
    @elseif ($variant === 'pb_label_amount_multi')
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td><strong>Round Off (₹)</strong></td>
            <td><strong>{{ $roundOffDisplay }}</strong></td>
            <td><strong></strong></td>
            <td><strong></strong></td>
            <td><strong></strong></td>
            <td><strong></strong></td>
        </tr>
    @elseif ($variant === 'po_consumable')
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td><strong>Round Off (₹)</strong></td>
            <td></td>
            @if (!empty($intraState))
                <td></td>
                <td></td>
            @else
                <td></td>
            @endif
            <td><strong>₹{{ $roundOffDisplay }}</strong></td>
        </tr>
    @elseif ($variant === 'po_furniture')
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td><strong>Round Off (₹)</strong></td>
            <td></td>
            <td></td>
            @if (!empty($intraState))
                <td></td>
                <td></td>
            @else
                <td></td>
            @endif
            <td><strong>₹{{ $roundOffDisplay }}</strong></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    @elseif ($variant === 'po_carton')
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td><strong>Round Off (₹)</strong></td>
            <td></td>
            @if (!empty($intraState))
                <td></td>
                <td></td>
            @else
                <td></td>
            @endif
            <td><strong>₹{{ $roundOffDisplay }}</strong></td>
        </tr>
    @elseif ($variant === 'po_recommended')
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td><strong>Round Off (₹)</strong></td>
            <td></td>
            @if (!empty($intraState))
                <td></td>
                <td></td>
            @else
                <td></td>
            @endif
            <td><strong>₹{{ $roundOffDisplay }}</strong></td>
        </tr>
    @elseif ($variant === 'supplier_po_furniture')
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            @if (empty($hideDiscountCol))
                <td></td>
            @endif
            <td><strong>Round Off (₹)</strong></td>
            @if (!empty($intraState))
                <td></td>
                <td></td>
            @else
                <td></td>
            @endif
            <td><strong>₹{{ $roundOffDisplay }}</strong></td>
            <td></td>
        </tr>
    @elseif ($variant === 'supplier_po_consumable')
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            @if (empty($hideDiscountCol))
                <td></td>
            @endif
            <td><strong>Round Off (₹)</strong></td>
            @if (!empty($intraState))
                <td></td>
                <td></td>
            @else
                <td></td>
            @endif
            <td><strong>₹{{ $roundOffDisplay }}</strong></td>
            <td></td>
        </tr>
    @elseif ($variant === 'supplier_carton')
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td><strong>Round Off (₹)</strong></td>
            <td></td>
            @if (!empty($intraState))
                <td></td>
                <td></td>
            @else
                <td></td>
            @endif
            <td><strong>₹{{ $roundOffDisplay }}</strong></td>
        </tr>
    @elseif ($variant === 'si_furniture')
        <tr>
            <td></td>
            <td>Round Off</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            @if (!empty($intraState))
                <td></td>
                <td></td>
            @else
                <td></td>
            @endif
            <td>{{ $roundOffDisplay }}</td>
        </tr>
    @elseif ($variant === 'si_consumable')
        <tr>
            <td></td>
            <td>Round Off</td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            @if (!empty($intraState))
                <td></td>
                <td></td>
            @else
                <td></td>
            @endif
            <td>{{ $roundOffDisplay }}</td>
        </tr>
    @elseif ($variant === 'si_multi_consumable')
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td><strong>Round Off (₹)</strong></td>
            <td></td>
            @if (!empty($intraState))
                <td></td>
                <td></td>
            @else
                <td></td>
            @endif
            <td><strong>₹{{ $roundOffDisplay }}</strong></td>
        </tr>
    @elseif ($variant === 'si_carton')
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Round Off</td>
            <td></td>
            @if (!empty($intraState))
                <td></td>
                <td></td>
            @else
                <td></td>
            @endif
            <td>{{ $roundOffDisplay }}</td>
        </tr>
    @elseif ($variant === 'si_multi_carton')
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td><strong>Round Off (₹)</strong></td>
            @if (!empty($intraState))
                <td></td>
                <td></td>
            @else
                <td></td>
            @endif
            <td><strong>₹{{ $roundOffDisplay }}</strong></td>
        </tr>
    @elseif ($variant === 'po_service')
        <tr>
            <td></td>
            <td colspan="2"></td>
            <td></td>
            <td></td>
            <td><strong>Round Off (₹)</strong></td>
            <td></td>
            @if (!empty($intraState))
                <td></td>
                <td></td>
            @else
                <td></td>
            @endif
            <td><strong>₹{{ $roundOffDisplay }}</strong></td>
            <td></td>
        </tr>
    @endif
@endif
