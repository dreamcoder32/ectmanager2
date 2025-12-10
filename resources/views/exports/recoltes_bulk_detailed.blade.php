<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Detailed Recoltes</title>
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

        th,
        td {
            border: 1px solid #bfbfbf;
            padding: 6px 8px;
        }

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

        .tracking {
            width: 42%;
        }

        @page {
            margin: 10mm;
        }

        .recolte-separator {
            border-top: 2px dashed #999;
            margin: 20px 0;
            padding-top: 20px;
        }

        .recolte-wrapper {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>
    @foreach($recoltes as $index => $recolte)
        <div class="recolte-wrapper">
            @if($index > 0)
                <div class="recolte-separator"></div>
            @endif

            <div class="header" style="position: relative;">
                <div style="text-align: center; margin-bottom: 10px;">
                    <img src="data:image/png;base64,{{ $recolte->barcode_base64 }}" alt="Barcode">
                    <div style="font-size: 10px;">RCT-{{ $recolte->code }}</div>
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
                <div style="position: absolute; top: 0; right: 0; text-align: right;">
                    @if($recolte->amount_discrepancy_note)
                        <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiBmaWxsPSIjMDAwMDAwIj48cGF0aCBkPSJNMTIgNS45OUwxOS41MyAxOUg0LjQ3TDEyIDUuOTlNMTIgMkwxIDIxaDIyTDEyIDJ6bTEgMTRoLTJ2Mmgydi0yem0wLTZoLTJ2NGgydi00eiIvPjwvc3ZnPg=="
                            alt="Warning" width="64" height="64" style="vertical-align: top;" />
                    @endif
                    @if($recolte->note)
                        <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiBmaWxsPSIjMDAwMDAwIj48cGF0aCBkPSJNMTQgMkg2Yy0xLjEgMC0yIC45LTIgMnYxNmMwIDEuMS45IDIgMiAyaDEyYzEuMSAwIDItLjkgMi0yVjhsLTYtNnptMiAxNkg2VjRoN3Y1aDV2MTF6Ii8+PC9zdmc+"
                            alt="Note" width="64" height="64" style="vertical-align: top;" />
                    @endif
                </div>

                @if($recolte->amount_discrepancy_note)
                    <div
                        style="margin-top: 5px; background-color: #f8d7da; padding: 5px; border: 1px solid #f5c6cb; border-radius: 4px;">
                        <span class="label" style="color: #721c24;">Discrepancy:</span> {{ $recolte->amount_discrepancy_note }}
                    </div>
                @endif

                @if($recolte->note)
                    <div
                        style="margin-top: 5px; background-color: #fff3cd; padding: 5px; border: 1px solid #ffeeba; border-radius: 4px;">
                        <span class="label" style="color: #856404;">Note:</span> {{ $recolte->note }}
                    </div>
                @endif
            </div>

            @php
                $firstCollection = $recolte->collections->first();
                $isDriverRecolte = $firstCollection && $firstCollection->driver_id;
            @endphp

            <table class="zebra">
                <thead>
                    <tr>
                        <th style="width: {{ $isDriverRecolte ? '32%' : '42%' }};">Tracking</th>
                        <th style="width: 18%;">Montant</th>
                        @if($isDriverRecolte)
                            <th style="width: 10%;">Comm.</th>
                        @endif
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
                            $commission = (int) round($collection->driver_commission ?? 0);
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
                            @if($isDriverRecolte)
                                <td class="text-right nowrap">{{ number_format($commission) }} Da</td>
                            @endif
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
                    $totalCommission = $recolte->collections->sum('driver_commission');
                    $netTotal = $totalCollected - $totalExpenses;

                    if ($totalCommission > 0) {
                        $netTotal -= $totalCommission;
                    }
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
                    @if($totalCommission > 0)
                        <tr>
                            <td class="text-right label" style="border: none;">Total Commission:</td>
                            <td class="text-right" style="border: none;">- {{ number_format($totalCommission, 2) }} Da</td>
                        </tr>
                    @endif
                    <tr style="font-size: 1.2em; font-weight: bold;">
                        <td class="text-right label" style="border: none; border-top: 1px solid #ccc;">Net à Verser:</td>
                        <td class="text-right" style="border: none; border-top: 1px solid #ccc;">
                            {{ number_format($netTotal, 2) }} Da
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    @endforeach

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} / {PAGE_COUNT} • ECTManager.online";
            $size = 10;
            $font = $fontMetrics->get_font("Helvetica");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 35;
            $pdf->page_text($x, $y, $text, $font, $size, array(0, 0, 0));
        }
    </script>
</body>

</html>