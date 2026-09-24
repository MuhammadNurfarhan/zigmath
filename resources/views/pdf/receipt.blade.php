<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Kwitansi {{ $payment->payment_no }}</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
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
            width: 30%;
            background-color: #f3f4f6;
            text-align: left;
        }
    </style>
</head>

<body>
    <div class="header">
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

    <h2 style="text-align:center;">KWITANSI PEMBAYARAN</h2>

    <table>
        <tr>
            <th>No. Kwitansi</th>
            <td>{{ $payment->payment_no }}</td>
        </tr>

        <tr>
            <th>Tanggal Pembayaran</th>
            <td>{{ $payment->paid_at?->format('d M Y H:i') }}</td>
        </tr>

        <tr>
            <th>No. Invoice</th>
            <td>{{ $payment->invoice->invoice_no ?? '-' }}</td>
        </tr>

        <tr>
            <th>Siswa</th>
            <td>{{ $payment->invoice->student->name ?? '-' }}</td>
        </tr>

        <tr>
            <th>Orang Tua</th>
            <td>{{ $payment->invoice->student->parent_name ?? '-' }}</td>
        </tr>

        <tr>
            <th>Metode Pembayaran</th>
            <td>
                {{ match ($payment->method) {
                    'cash' => 'Tunai',
                    'transfer' => 'Transfer',
                    'qris' => 'QRIS',
                    'e_wallet' => 'E-Wallet',
                    default => 'Lainnya',
                } }}
            </td>
        </tr>

        <tr>
            <th>Jumlah Pembayaran</th>
            <td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
        </tr>

        <tr>
            <th>Keterangan</th>
            <td>{{ $payment->notes ?? '-' }}</td>
        </tr>
    </table>
</body>

</html>
