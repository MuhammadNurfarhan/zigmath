<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Siswa</title>

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
    </style>
</head>

<body>
    <h1>{{ \App\Models\Setting::get('bimbel_name', 'Zigmath') }}</h1>
    <h2>Laporan Siswa - {{ now()->translatedFormat('d M Y') }}</h2>

    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Paket</th>
                <th>Orang Tua</th>
                <th>No HP</th>
                <th>Status</th>
                <th class="text-right">Total Tagihan</th>
                <th class="text-right">Terbayar</th>
                <th class="text-right">Sisa</th>
            </tr>
        </thead>

        <tbody>
            @forelse($students as $student)
                <tr>
                    <td>{{ $student->name }}</td>
                    <td>{{ $student->class_type === 'regular' ? 'Reguler' : 'Private' }}</td>
                    <td>{{ $student->package->name ?? '-' }}</td>
                    <td>{{ $student->parent_name }}</td>
                    <td>{{ $student->parent_phone }}</td>
                    <td>
                        {{ match ($student->status) {
                            'active' => 'Aktif',
                            'inactive' => 'Nonaktif',
                            'cuti' => 'Cuti',
                            default => ucfirst($student->status),
                        } }}
                    </td>
                    <td class="text-right">Rp {{ number_format($student->invoices_sum_amount ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($student->invoices_sum_paid_amount ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="text-right">Rp
                        {{ number_format($student->invoices_sum_remaining_balance ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">Tidak ada data siswa.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
