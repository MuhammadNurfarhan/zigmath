<div class="space-y-3">
    @forelse($payments as $payment)
        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">
                        Rp {{ number_format($payment->amount, 0, ',', '.') }}
                    </p>
                    <p class="text-sm text-gray-500">
                        {{ $payment->paid_at->format('d M Y, H:i') }}
                    </p>
                </div>
                <span class="px-2 py-1 text-xs font-medium rounded-full
                    {{ match($payment->method) {
                        'cash' => 'bg-green-100 text-green-800',
                        'transfer' => 'bg-blue-100 text-blue-800',
                        'qris' => 'bg-purple-100 text-purple-800',
                        default => 'bg-gray-100 text-gray-800',
                    } }}">
                    {{ strtoupper($payment->method) }}
                </span>
            </div>
            @if($payment->reference_number)
                <p class="text-xs text-gray-400 mt-2">Ref: {{ $payment->reference_number }}</p>
            @endif
            @if($payment->notes)
                <p class="text-xs text-gray-400 mt-1">📝 {{ $payment->notes }}</p>
            @endif
            @if($payment->proof_path)
                <a href="{{ asset('storage/' . $payment->proof_path) }}" target="_blank"
                   class="text-xs text-indigo-600 hover:underline mt-1 inline-block">
                    📎 Lihat Bukti
                </a>
            @endif
        </div>
    @empty
        <p class="text-center text-gray-500 py-8">Belum ada pembayaran tercatat.</p>
    @endforelse
</div>
