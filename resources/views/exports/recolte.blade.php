<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recolte {{ $recolte->code }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', 'Liberation Sans', sans-serif; font-size: 12px; color: #222; }
        .header { margin-bottom: 8px; }
        .header .label { font-weight: 700; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
-        th, td { border: 1px solid #bfbfbf; padding: 6px 10px; }
+        th, td { border: 1px solid #bfbfbf; padding: 6px 8px; }
+        /* Add a bit more left padding for the first column (Tracking) */
+        /* table th:first-child, table td:first-child { padding-left: 14px; } */
        th { background: #f0f0f0; text-align: center; font-weight: 700; }
        td { vertical-align: middle; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .nowrap { white-space: nowrap; }
        .zebra tr:nth-child(even) td { background: #f7f7f7; }
-        .tracking { width: 24%; }
+        /* Widen tracking column to give long codes more space */
+        .tracking { width: 32%; }
        .amount { width: 14%; }
        .phone { width: 11%; }
        .by { width: 20%; }
        .date { width: 15%; }
        .type { width: 8%; }
        @page { margin: 10mm; }
    </style>
</head>
<body>
    <div class="header">
        <div><span class="label">Recolte Code:</span> RCT-{{ $recolte->code }} Créé par : {{ optional($recolte->createdBy)->name }}</div>
        @php
            $totalCod = $recolte->collections->sum(function ($c) { return $c->parcel ? (intval($c->parcel->cod_amount) ?? 0) : 0; });
        @endphp
        <div><span class="label">Total COD:</span> {{ number_format((int) round($totalCod)) }} Da</div>
    </div>

    <table class="zebra">
        <thead>
            <tr>
                <th style="width: 32%;">Tracking</th>
                <th style="width: 14%;">Montant</th>
                <th style="width: 11%;">telephone</th>
                <th style="width: 20%;">Recolté Par</th>
                <th style="width: 15%;">Recolté le</th>
                <th style="width: 8%;">Type</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($recolte->collections as $collection)
            @php
                $tracking = optional($collection->parcel)->tracking_number ?? 'N/A';
                $amount = (int) round(optional($collection->parcel)->cod_amount ?? 0);
                $phone = optional($collection->parcel)->recipient_phone ?? 'N/A';
                $by = $collection->createdBy ? ($collection->createdBy->display_name ?? ($collection->createdBy->name ?? 'N/A')) : 'N/A';
                $date = $collection->collected_at ? $collection->collected_at->format('Y-m-d H:i') : '';
                $rawType = $collection->parcel_type ?? (optional($collection->parcel)->delivery_type ?? null);
                if ($rawType) {
                    if (in_array($rawType, ['home_delivery', 'home', 'homeDelivery'])) {
                        $type = 'a domicile';
                    } elseif (in_array($rawType, ['stopdesk', 'stop_desk'])) {
                        $type = 'stopdesk';
                    } else {
                        $type = $rawType;
                    }
                } else {
                    $type = method_exists($collection, 'isHomeDelivery') && $collection->isHomeDelivery() ? 'home' : 'stopdesk';
                }
            @endphp
            <tr>
-                <td class="text-left nowrap">{{ $tracking }}</td>
-                <td class="text-right nowrap">{{ number_format($amount) }} Da</td>
-                <td class="text-left nowrap">{{ $phone }}</td>
-                <td class="text-center nowrap">{{ $by }}</td>
-                <td class="text-center nowrap">{{ $date }}</td>
-                <td class="text-center nowrap">{{ $type }}</td>
+            
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>