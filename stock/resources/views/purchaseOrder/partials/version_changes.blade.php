@php
    use App\Support\PurchaseOrderVersionWriter as V;
    $changed = is_array($changed ?? null) ? $changed : [];
    $grouped = V::groupChangesForDisplay($changed);
    $summaryText = V::friendlyVersionSummary($changed, $versionNumber ?? null);
@endphp
@if($changed === [])
    <span class="text-muted">—</span>
@else
    <div class="pov-summary mb-2">{{ $summaryText }}</div>
    <details class="pov-details">
        <summary class="text-primary" style="cursor:pointer;">See what changed</summary>
        <div class="pov-compare mt-2">
            @if($grouped['header'] !== [])
                <div class="pov-group mb-2">
                    <div class="pov-group-title">PO header</div>
                    @foreach($grouped['header'] as $row)
                        @if(in_array($row['key'] ?? '', ['po_revise_date'], true))
                            @continue
                        @endif
                        <div class="pov-row">
                            <span class="pov-field">{{ $row['field'] }}</span>
                            <span class="pov-from">{{ V::formatFieldValue($row['key'] ?? null, $row['from']) }}</span>
                            <span class="pov-arrow">→</span>
                            <span class="pov-to">{{ V::formatFieldValue($row['key'] ?? null, $row['to']) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            @foreach($grouped['products'] as $sku => $rows)
                <div class="pov-group mb-2">
                    <div class="pov-group-title">{{ $sku }}</div>
                    @foreach($rows as $row)
                        @if(in_array($row['key'] ?? '', ['amount', 'gstamount'], true))
                            @continue
                        @endif
                        <div class="pov-row">
                            <span class="pov-field">{{ $row['field'] }}</span>
                            <span class="pov-from">{{ V::formatFieldValue($row['key'] ?? null, $row['from']) }}</span>
                            <span class="pov-arrow">→</span>
                            <span class="pov-to">{{ V::formatFieldValue($row['key'] ?? null, $row['to']) }}</span>
                        </div>
                    @endforeach
                    {{-- show amount/gst only if rate/qty not already shown --}}
                    @php
                        $keys = array_column($rows, 'key');
                        $showDerived = !in_array('rate', $keys, true) && !in_array('quantity', $keys, true);
                    @endphp
                    @if($showDerived)
                        @foreach($rows as $row)
                            @if(in_array($row['key'] ?? '', ['amount', 'gstamount'], true))
                                <div class="pov-row">
                                    <span class="pov-field">{{ $row['field'] }}</span>
                                    <span class="pov-from">{{ V::formatFieldValue($row['key'] ?? null, $row['from']) }}</span>
                                    <span class="pov-arrow">→</span>
                                    <span class="pov-to">{{ V::formatFieldValue($row['key'] ?? null, $row['to']) }}</span>
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>
            @endforeach

            @foreach($grouped['other'] as $row)
                <div class="pov-row">
                    <span class="pov-field">{{ $row['field'] }}</span>
                    <span class="pov-from">{{ V::formatFieldValue($row['key'] ?? null, $row['from']) }}</span>
                    <span class="pov-arrow">→</span>
                    <span class="pov-to">{{ V::formatFieldValue($row['key'] ?? null, $row['to']) }}</span>
                </div>
            @endforeach
        </div>
    </details>
@endif
