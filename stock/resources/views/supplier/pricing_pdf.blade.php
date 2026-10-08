<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Supplier pricing</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h2 { margin: 0 0 8px 0; font-size: 16px; }
        .meta { margin-bottom: 12px; font-size: 10px; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 5px 6px; text-align: left; vertical-align: middle; }
        th { background: #eee; font-weight: bold; }
        .num { width: 36px; text-align: center; }
        .sku { min-width: 90px; }
        .price { text-align: right; white-space: nowrap; }
    </style>
</head>
<body>
    <h2>Supplier pricing</h2>
    <div class="meta">Downloaded as on {{ $downloadedAt }}</div>
    <table>
        <thead>
            <tr>
                <th class="num">#</th>
                <th class="sku">Product SKU</th>
                <th>Supplier name</th>
                <th class="price">Price</th>
                <th class="price">UK 45 Price</th>
            </tr>
        </thead>
        <tbody>
            @php $productIndex = 0; @endphp
            @foreach ($grouped as $productId => $group)
                @php
                    $productIndex++;
                    $group = $group->sortBy(function ($sp) {
                        return optional($sp->supplier)->c_name ?? '';
                    })->values();
                    $product = $group->first()->product;
                    $rowspan = $group->count();
                @endphp
                @foreach ($group as $sp)
                    <tr>
                        @if ($loop->first)
                            <td class="num" rowspan="{{ $rowspan }}">{{ $productIndex }}</td>
                            <td class="sku" rowspan="{{ $rowspan }}">{{ $product ? $product->code : 'N/A' }}</td>
                        @endif
                        <td>{{ optional($sp->supplier)->c_name ?? 'Unknown Supplier' }}</td>
                        <td class="price">{{ $sp->rate }}</td>
                        <td class="price">{{ $sp->uk_45_rate }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>
