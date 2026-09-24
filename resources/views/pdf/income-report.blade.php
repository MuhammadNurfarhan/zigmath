<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Pemasukan</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        h2 {
            font-size: 14px;
            margin-top: 0;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th,
        table td {
            border: 1px solid #ddd;
            padding: 6px;
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
    </style>
</head>

<body>
    <h1>{{ \App\Models\Setting::get('bimbel_name', 'Zigmath') }}</h1>
    <h2>Laporan Pemasukan - {{ now()->translatedFormat('d M Y') }}</h2>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>No. Pembayaran</th>
                <th>No. Invoice</th>
                <th>Siswa</th>
                <th>Metode</th>
                <th class="text-right">Nominal</th>
            </tr>
        </thead>

        <tbody>
            @php $total = 0; @endphp

            @forelse($payments as $payment)
                @php $total += $payment->amount; @endphp

                <tr>
                    <td>{{ $payment->paid_at?->format('d M Y H:i') }}</td>
                    <td>{{ $payment->payment_no }}</td>
                    <td>{{ $payment->invoice->invoice_no ?? '-' }}</td>
                    <td>{{ $payment->invoice->student->name ?? '-' }}</td>
                    <td>
                        {{ match ($payment->method) {
                            'cash' => 'Tunai',
                            'transfer' => 'Transfer',
                            'qris' => 'QRIS',
                            'e_wallet' => 'E-Wallet',
                            default => 'Lainnya',
                        } }}
                    </td>
                    <td class="text-right">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Tidak ada data.</td>
                </tr>
            @endforelse

            <tr>
                <td colspan="5" class="total">Total Pemasukan</td>
                <td class="text-right total">Rp {{ number_format($total, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</body>

</html>
