{{-- resources/views/requester/partials/slot-pending.blade.php --}}

<div class="card border-start border-warning border-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h6 class="card-title mb-0">
                <i class="bi bi-file-earmark-text text-warning"></i> Slot {{ $slotNum }}
            </h6>
            <span class="badge bg-warning">PENDING</span>
        </div>

        <table class="table table-sm table-borderless">
            <tr>
                <td width="140"><strong>Kode Request:</strong></td>
                <td><code>{{ $request->request_id }}</code></td>
            </tr>
            <tr>
                <td><strong>Tanggal Pengajuan:</strong></td>
                <td>{{ $request->created_at->format('d M Y H:i') }}</td>
            </tr>
            <tr>
                <td><strong>Kendaraan/Alat:</strong></td>
                <td>
                    {{ $request->vehicle->plat_nomer ?? 'N/A' }} -
                    {{ $request->vehicle->jenis ?? '' }}
                </td>
            </tr>
            <tr>
                <td><strong>Jenis BBM:</strong></td>
                <td><span class="badge bg-secondary">{{ $request->gasoline_type }}</span></td>
            </tr>
            <tr>
                <td><strong>Nominal:</strong></td>
                <td><strong class="text-primary">Rp {{ number_format($request->bill_payment, 0, ',', '.') }}</strong></td>
            </tr>
        </table>

        @if($request->driver_note)
        <div class="mb-2">
            <small class="text-muted"><strong>Catatan:</strong></small>
            <p class="mb-0 small">{{ $request->driver_note }}</p>
        </div>
        @endif

        <div class="alert alert-warning mb-0 mt-3">
            <i class="bi bi-hourglass-split"></i> Menunggu persetujuan admin...
        </div>
    </div>
</div>
