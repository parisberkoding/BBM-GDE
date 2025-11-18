<div class="card border-warning">
    <div class="card-header bg-warning text-white">
        <h6 class="mb-0">
            <i class="bi bi-hourglass-split"></i> Slot {{ $slotNum }} - Pending
        </h6>
    </div>
    <div class="card-body">
        <p class="mb-1"><strong>Nomor:</strong> <code>{{ $request->request_number }}</code></p>
        <p class="mb-1"><strong>Kendaraan/Alat:</strong><br>
            <small>{{ $request->vehicle->consumerial_code ?? 'N/A' }} - {{ $request->vehicle->consumerial_name ?? 'N/A' }}</small>
        </p>
        <p class="mb-1"><strong>Tipe:</strong> <span class="badge bg-secondary">{{ $request->vehicle->consumerial_type ?? 'N/A' }}</span></p>
        <p class="mb-1"><strong>BBM:</strong> {{ $request->gasoline_type }}</p>
        <p class="mb-1"><strong>Nominal:</strong> Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}</p>
        <p class="mb-1 text-muted small">
            <i class="bi bi-clock"></i> {{ $request->request_date->diffForHumans() }}
        </p>
        <hr>
        <span class="badge bg-warning w-100">Menunggu Approval</span>
    </div>
</div>
