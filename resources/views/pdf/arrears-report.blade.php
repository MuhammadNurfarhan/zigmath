<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Tunggakan</title>

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

        .danger {
            color: #dc2626;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h1>{{ \App\Models\Setting::get('bimbel_name', 'Zigmath') }}</h1>
    <h2>Laporan Tunggakan - {{ now()->translatedFormat('d M Y') }}</h2>

    <table>
        <thead>
            <tr>
                <th>No. Invoice</th>
                <th>Siswa</th>
                <th>Paket</th>
                <th>Periode</th>
                <th>Jatuh Tempo</th>
                <th class="text-right">Tagihan</th>
                <th class="text-right">Terbayar</th>
                <th class="text-right">Sisa</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
            @php $total = 0; @endphp

            @forelse($invoices as $invoice)
                @php $total += $invoice->remaining_balance; @endphp

                <tr>
                    <td>{{ $invoice->invoice_no }}</td>
                    <td>{{ $invoice->student->name ?? '-' }}</td>
                    <td>{{ $invoice->student->package->name ?? '-' }}</td>
                    <td>{{ $invoice->period }}</td>
                    <td>{{ $invoice->due_date?->format('d M Y') }}</td>
                    <td class="text-right">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</td>
                    <td class="text-right danger">Rp {{ number_format($invoice->remaining_balance, 0, ',', '.') }}</td>
                    <td>
                        {{ match ($invoice->status) {
                            'unpaid' => 'Belum Bayar',
                            'partial' => 'Cicilan',
                            'overdue' => 'Terlambat',
                            'paid' => 'Lunas',
                            default => ucfirst($invoice->status),
                        } }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">Tidak ada tunggakan.</td>
                </tr>
            @endforelse

            <tr>
                <td colspan="7"><strong>Total Tunggakan</strong></td>
                <td class="text-right danger">Rp {{ number_format($total, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>

</html>
