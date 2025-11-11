@extends('templates.main')

@section('content')
<div class="pagetitle">
    <h1>Dashboard Permohonan BBM</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active">Permohonan BBM</li>
        </ol>
    </nav>
</div>

<section class="section dashboard">
    <!-- Alert Container -->
    <div id="alertContainer">
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
    </div>

    <!-- Welcome Card -->
    <div class="row">
        <div class="col-12">
            <div class="card info-card">
                <div class="card-body">
                    <h5 class="card-title">Selamat Datang, {{ Auth::user()->nama_lengkap }}! 👋</h5>
                    <p class="mb-0">Anda dapat mengajukan permohonan BBM dan melacak status permohonan Anda di sini.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row">
        <div class="col-xxl-3 col-md-6">
            <div class="card info-card sales-card">
                <div class="card-body">
                    <h5 class="card-title">Slot Tersedia</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <div class="ps-3">
                            <h6 id="availableSlots">{{ 5 - $activeRequests }}</h6>
                            <span class="text-muted small pt-1">dari 5 slot</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card revenue-card">
                <div class="card-body">
                    <h5 class="card-title">Pending</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $pendingCount }}</h6>
                            <span class="text-warning small pt-1">Menunggu Approval</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card customers-card">
                <div class="card-body">
                    <h5 class="card-title">Disetujui</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $approvedCount }}</h6>
                            <span class="text-success small pt-1">Siap Digunakan</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card info-card">
                <div class="card-body">
                    <h5 class="card-title">Total Permohonan</h5>
                    <div class="d-flex align-items-center">
                        <div class="card-icon rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div class="ps-3">
                            <h6>{{ $totalRequests }}</h6>
                            <span class="text-muted small pt-1">Semua Status</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Permohonan BBM -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Form Permohonan BBM Baru</h5>
                </div>
                <div class="card-body">
                    @if($activeRequests >= 5)
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        Slot permohonan Anda sudah penuh (5/5). Silakan selesaikan permohonan yang ada terlebih dahulu.
                    </div>
                    @else
                    <form action="" method="POST" id="requestForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="vehicle_id" class="form-label">Kendaraan / Alat <span class="text-danger">*</span></label>
                                <select class="form-select @error('vehicle_id') is-invalid @enderror"
                                        id="vehicle_id" name="vehicle_id" required>
                                    <option value="">Pilih Kendaraan/Alat</option>
                                    @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                        {{ $vehicle->plat_nomer }} - {{ $vehicle->merk }} ({{ $vehicle->jenis }})
                                    </option>
                                    @endforeach
                                </select>
                                @error('vehicle_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="gasoline_type" class="form-label">Jenis BBM <span class="text-danger">*</span></label>
                                <select class="form-select @error('gasoline_type') is-invalid @enderror"
                                        id="gasoline_type" name="gasoline_type" required>
                                    <option value="">Pilih Jenis BBM</option>
                                    @foreach($gasolineTypes as $gasoline)
                                    <option value="{{ $gasoline->name }}" {{ old('gasoline_type') == $gasoline->name ? 'selected' : '' }}>
                                        {{ $gasoline->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('gasoline_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="bill_payment" class="form-label">Nominal (Rp) <span class="text-danger">*</span></label>
                                <select class="form-select @error('bill_payment') is-invalid @enderror"
                                        id="bill_payment" name="bill_payment" required>
                                    <option value="">-- Pilih Nominal --</option>
                                    @for($i = 150000; $i <= 850000; $i += 50000)
                                    <option value="{{ $i }}" {{ old('bill_payment') == $i ? 'selected' : '' }}>
                                        Rp {{ number_format($i, 0, ',', '.') }}
                                    </option>
                                    @endfor
                                </select>
                                @error('bill_payment')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="driver_note" class="form-label">Catatan Driver (Opsional)</label>
                            <textarea class="form-control @error('driver_note') is-invalid @enderror"
                                      id="driver_note" name="driver_note" rows="3"
                                      placeholder="Catatan tambahan...">{{ old('driver_note') }}</textarea>
                            @error('driver_note')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="bi bi-send"></i> Ajukan Permohonan Baru
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Slot Permohonan Aktif -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-grid-3x3-gap"></i> Slot Permohonan Aktif ({{ $activeRequests }}/5)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @for($i = 0; $i < 5; $i++)
                            @if(isset($activeSlots[$i]))
                                @php $request = $activeSlots[$i]; @endphp
                                <div class="col-md-6 col-lg-4 mb-3">
                                    @if($request->status === 'pending')
                                        @include('requester.partials.slot-pending', ['request' => $request, 'slotNum' => $i + 1])
                                    @elseif($request->status === 'approved')
                                        @include('requester.partials.slot-approved', ['request' => $request, 'slotNum' => $i + 1])
                                    @endif
                                </div>
                            @else
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card border-dashed" style="min-height: 300px;">
                                        <div class="card-body text-center d-flex flex-column justify-content-center">
                                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                            <p class="text-muted mt-3 mb-0">Slot {{ $i + 1 }} - Kosong</p>
                                            <small class="text-muted">Siap untuk permohonan baru</small>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Riwayat Permohonan -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Riwayat Permohonan</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>Kode Request</th>
                                    <th>Tanggal</th>
                                    <th>Kendaraan</th>
                                    <th>Jenis BBM</th>
                                    <th>Nominal</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- @forelse($history as $item)
                                <tr>
                                    <td><code>{{ $item->request_id }}</code></td>
                                    <td>{{ $item->created_at->format('d M Y H:i') }}</td>
                                    <td>
                                        {{ $item->vehicle->plat_nomer ?? 'N/A' }}<br>
                                        <small class="text-muted">{{ $item->vehicle->jenis ?? '' }}</small>
                                    </td>
                                    <td><span class="badge bg-secondary">{{ $item->gasoline_type }}</span></td>
                                    <td><strong>Rp {{ number_format($item->bill_payment, 0, ',', '.') }}</strong></td>
                                    <td>
                                        @if($item->status === 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                        @elseif($item->status === 'approved')
                                        <span class="badge bg-success">Approved</span>
                                        @elseif($item->status === 'completed')
                                        <span class="badge bg-info">Completed</span>
                                        @elseif($item->status === 'rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary"
                                                onclick="showDetail('{{ $item->request_id }}')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada riwayat permohonan</td>
                                </tr>
                                @endforelse --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.border-dashed {
    border: 2px dashed #dee2e6;
}

.info-card {
    border: none;
    box-shadow: 0px 0 30px rgba(1, 41, 112, 0.1);
}

.card-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #4154f1 0%, #2c3cdd 100%);
}

.card-icon i {
    font-size: 32px;
    color: white;
}
</style>

<script>
function showDetail(requestId) {
    // Implementasi modal detail
    alert('Detail untuk request: ' + requestId);
}
</script>
@endsection
