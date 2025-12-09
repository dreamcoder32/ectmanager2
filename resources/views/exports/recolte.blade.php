<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recolte {{ $recolte->code }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', 'Liberation Sans', sans-serif;
            font-size: 12px;
            color: #222;
        }

        .header {
            margin-bottom: 8px;
        }

        .header .label {
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        - th,
        td {
            border: 1px solid #bfbfbf;
            padding: 6px 10px;
        }

        +th,
        td {
            border: 1px solid #bfbfbf;
            padding: 6px 8px;
        }

        +
        /* Add a bit more left padding for the first column (Tracking) */
        +

        /* table th:first-child, table td:first-child { padding-left: 14px; } */
        th {
            background: #f0f0f0;
            text-align: center;
            font-weight: 700;
        }

        td {
            vertical-align: middle;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .nowrap {
            white-space: nowrap;
        }

        .zebra tr:nth-child(even) td {
            background: #f7f7f7;
        }

        - .tracking {

            /* Widen tracking column to give long codes more space */
            .tracking {
                width: 42%;
            }

            .amount {
                width: 18%;
            }

            .phone {
                width: 15%;
            }



            .date {
                width: 15%;
            }

            .type {
                width: 10%;
            }

            @page {
                margin: 10mm;
            }
    </style>
</head>

<body>
    <div class="header">
        <div style="text-align: center; margin-bottom: 10px;">
            <img src="data:image/png;base64,{{ $barcode }}" alt="Barcode">
            <div style="font-size: 10px;">RCT-{{ $recolte->id }}</div>
        </div>
        <div><span class="label">Recolte Code:</span> RCT-{{ $recolte->code }} Créé par :
            {{ optional($recolte->createdBy)->name }}
        </div>
        @php
            $totalCod = $recolte->collections->sum(function ($c) {
                return $c->parcel ? (intval($c->amount) ?? 0) : 0;
            });

            $firstCollection = $recolte->collections->first();
            $recoltedBy = 'N/A';
            if ($firstCollection) {
                if ($firstCollection->driver) {
                    $recoltedBy = $firstCollection->driver->name;
                } elseif ($firstCollection->createdBy) {
                    $recoltedBy = $firstCollection->createdBy->first_name ?? $firstCollection->createdBy->name;
                }
            }
        @endphp
        <div><span class="label">Total COD:</span> {{ number_format((int) round($totalCod)) }} Da</div>
        <div><span class="label">Recolté par:</span> {{ $recoltedBy }}</div>
        <div>
            <span class="label">Note:</span> {{ $recolte->note }}
        </div>
    </div>

    <table class="zebra">
        <thead>
            <tr>
                <th style="width: 42%;">Tracking</th>
                <th style="width: 18%;">Montant</th>
                <th style="width: 15%;">telephone</th>
                <th style="width: 15%;">Recolté le</th>
                <th style="width: 10%;">Type</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($recolte->collections as $collection)
                @php
                    $tracking = optional($collection->parcel)->tracking_number ?? 'N/A';
                    $amount = (int) round(optional($collection->parcel)->cod_amount ?? 0);
                    $phone = optional($collection->parcel)->recipient_phone ?? 'N/A';
                    $by = $collection->createdBy ? ($collection->createdBy->first_name ?? ($collection->createdBy->name ?? 'N/A')) : 'N/A';
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
                    <td class="text-left nowrap">{{ $tracking }}</td>
                    <td class="text-right nowrap">{{ number_format($amount) }} Da</td>
                    <td class="text-left nowrap">{{ $phone }}</td>
                    <td class="text-center nowrap">{{ $date }}</td>
                    <td class="text-center nowrap">{{ $type }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($recolte->expenses->count() > 0)
        <div style="margin-top: 20px;">
            <div class="header">
                <span class="label">Dépenses:</span>
            </div>
            <table class="zebra">
                <thead>
                    <tr>
                        <th style="width: 70%;">Description</th>
                        <th style="width: 30%;">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recolte->expenses as $expense)
                        <tr>
                            <td class="text-left">{{ $expense->title }}
                                {{ $expense->description ? ' - ' . $expense->description : '' }}
                            </td>
                            <td class="text-right">{{ number_format($expense->amount, 2) }} Da</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div style="margin-top: 30px; border-top: 2px solid #000; padding-top: 10px;">
        @php
            $totalCollected = $recolte->collections->sum('amount');
            $totalExpenses = $recolte->expenses->sum('amount');
            $netTotal = $totalCollected - $totalExpenses;
        @endphp
        <table style="width: 50%; margin-left: auto;">
            <tr>
                <td class="text-right label" style="border: none;">Total Recolté:</td>
                <td class="text-right" style="border: none;">{{ number_format($totalCollected, 2) }} Da</td>
            </tr>
            <tr>
                <td class="text-right label" style="border: none;">Total Dépenses:</td>
                <td class="text-right" style="border: none;">- {{ number_format($totalExpenses, 2) }} Da</td>
            </tr>
            <tr style="font-size: 1.2em; font-weight: bold;">
                <td class="text-right label" style="border: none; border-top: 1px solid #ccc;">Net à Verser:</td>
                <td class="text-right" style="border: none; border-top: 1px solid #ccc;">
                    {{ number_format($netTotal, 2) }} Da
                </td>
            </tr>
        </table>
    </div>
    <script type="text/php">
if (isset($pdf)) {
    // Render page number at the bottom-right: "Page {current}/{total}"
    $font = $fontMetrics->get_font("Helvetica", "normal");
    $size = 10;
    $canvas = $pdf->get_canvas();
    $w = $canvas->get_width();
    $h = $canvas->get_height();
    $text = "page {PAGE_NUM}/{PAGE_COUNT}";

    // Place ~120pt from the right edge and ~24pt from the bottom edge
    $x = $w - 120;
    $y = $h - 24;

    $canvas->page_text($x, $y, $text, $font, $size, [0, 0, 0]);
}
</script>
</body>

</html>