<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Recolte Export</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
        }

        .header p {
            margin: 5px 0 0;
            color: #666;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
            color: #555;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .amount {
            text-align: right;
            font-family: 'Courier New', Courier, monospace;
        }

        .total-row td {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 14px;
            background-color: #f0f0f0;
        }

        .summary-box {
            float: right;
            width: 300px;
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .summary-row.total {
            border-top: 1px solid #ccc;
            padding-top: 8px;
            margin-top: 8px;
            font-weight: bold;
            font-size: 14px;
        }

        .text-danger {
            color: #dc3545;
        }

        .text-success {
            color: #28a745;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #999;
            padding: 10px;
            border-top: 1px solid #eee;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Recolte Summary Report</h1>
        <p>Generated on {{ date('Y-m-d H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%">Code</th>
                <th style="width: 25%">Driver / Agent</th>
                <th style="width: 10%">Parcels</th>
                <th style="width: 15%" class="amount">Collected</th>
                <th style="width: 15%" class="amount">Expenses</th>
                <th style="width: 20%" class="amount">Net Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recoltes as $recolte)
                <tr>
                    <td><strong>RCT-{{ $recolte->code }}</strong></td>
                    <td>
                        {{ $recolte->related_name }}
                        <div style="font-size: 10px; color: #888;">{{ ucfirst($recolte->type) }}</div>
                    </td>
                    <td>{{ $recolte->collections->count() }}</td>
                    <td class="amount">{{ number_format($recolte->manual_amount ?? $recolte->total_cod_amount, 2) }}</td>
                    <td class="amount text-danger">
                        @if($recolte->total_expenses > 0)
                            -{{ number_format($recolte->total_expenses, 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="amount"><strong>{{ number_format($recolte->net_total, 2) }} DA</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="clear: both;"></div>

    <div class="summary-box">
        <div class="summary-row">
            <span>Total Recoltes:</span>
            <span>{{ $recoltes->count() }}</span>
        </div>
        <div class="summary-row">
            <span>Total Parcels:</span>
            <span>{{ $recoltes->sum(fn($r) => $r->collections->count()) }}</span>
        </div>
        <div class="summary-row">
            <span>Total Collected:</span>
            <span>{{ number_format($grandTotalCollected, 2) }} DA</span>
        </div>
        <div class="summary-row">
            <span>Total Expenses:</span>
            <span class="text-danger">-{{ number_format($grandTotalExpenses, 2) }} DA</span>
        </div>
        @if(isset($grandTotalCommission) && $grandTotalCommission > 0)
            <div class="summary-row">
                <span>Total Commission Livreurs:</span>
                <span class="text-danger">{{ number_format($grandTotalCommission, 2) }} DA</span>
            </div>
        @endif
        <div class="summary-row total">
            <span>Grand Net Total:</span>
            <span class="text-success">{{ number_format($grandNetTotal, 2) }} DA</span>
        </div>
    </div>

    <div class="footer">
        Page 1 of 1 &bull; ECTManager.online
    </div>
</body>

</html>