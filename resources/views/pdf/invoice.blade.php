<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_no }}</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
        }

        .invoice-title {
            font-size: 18px;
            font-weight: bold;
            text-align: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th,
        table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        table th {
            background-color: #f3f4f6;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .total {
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="header">
        <div>
            <div class="company-name">
                {{ \App\Models\Setting::get('bimbel_name', 'Zigmath') }}
            </div>

            <div>
                {{ \App\Models\Setting::get('bimbel_address') }}
            </div>

            <div>
                {{ \App\Models\Setting::get('bimbel_phone') }}
            </div>
        </div>

        <div class="invoice-title">
            INVOICE
            <br>
            {{ $invoice->invoice_no }}
        </div>
    </div>

    <table>
        <tr>
            <th>Siswa</th>
            <td>{{ $invoice->student->name ?? '-' }}</td>
        </tr>

        <tr>
            <th>Orang Tua</th>
            <td>{{ $invoice->student->parent_name ?? '-' }}</td>
        </tr>

        <tr>
            <th>No HP</th>
            <td>{{ $invoice->student->parent_phone ?? '-' }}</td>
        </tr>

        <tr>
            <th>Paket</th>
            <td>{{ $invoice->student->package->name ?? '-' }}</td>
        </tr>

        <tr>
            <th>Periode</th>
            <td>{{ \Carbon\Carbon::parse($invoice->period . '-01')->translatedFormat('F Y') }}</td>
        </tr>

        <tr>
            <th>Jatuh Tempo</th>
            <td>{{ $invoice->due_date->format('d M Y') }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Keterangan</th>
                <th class="text-right">Jumlah</th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td>
                    Tagihan {{ $invoice->student->package->name ?? 'Paket' }}
                    Periode {{ \Carbon\Carbon::parse($invoice->period . '-01')->translatedFormat('F Y') }}
                </td>
                <td class="text-right">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
            </tr>

            <tr>
                <td class="total">Total</td>
                <td class="text-right total">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
            </tr>

            <tr>
                <td class="total">Terbayar</td>
                <td class="text-right total">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</td>
            </tr>

            <tr>
                <td class="total">Sisa Tagihan</td>
                <td class="text-right total">Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        {{ \App\Models\Setting::get('invoice_footer', 'Terima kasih telah membayar tepat waktu.') }}
    </div>
</body>

</html>
