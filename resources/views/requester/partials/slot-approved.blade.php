{{-- resources/views/requester/partials/slot-approved.blade.php --}}

<div class="card border-start border-success border-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <h6 class="card-title mb-0">
                <i class="bi bi-check-circle text-success"></i> Slot {{ $slotNum }}
            </h6>
            <span class="badge bg-success">APPROVED</span>
        </div>

        <!-- Voucher Box -->
        <div class="voucher-box mb-3">
            <div class="text-white text-center">
                <small><i class="bi bi-ticket-perforated"></i> KODE VOUCHER BBM</small>
                <div class="voucher-code">{{ $request->voucher_code }}</div>
            </div>
        </div>

        <table class="table table-sm table-borderless">
            <tr>
                <td width="140"><strong>Tanggal Disetujui:</strong></td>
                <td>{{ $request->approved_at ? \Carbon\Carbon::parse($request->approved_at)->format('d M Y H:i') : '-' }}</td>
            </tr>
            <tr>
                <td><strong>Kendaraan:</strong></td>
                <td>
                    {{ $request->vehicle->plat_nomer ?? 'N/A' }} -
                    {{ $request->vehicle->jenis ?? '' }}
                </td>
            </tr>
            @if($request->authorizer_comment)
            <tr>
                <td><strong>Komentar Admin:</strong></td>
                <td><em>{{ $request->authorizer_comment }}</em></td>
            </tr>
            @endif
        </table>

        <button class="btn btn-success btn-sm w-100 mb-2"
                onclick="printVoucher('{{ $request->request_id }}')">
            <i class="bi bi-printer"></i> Cetak Struk BBM
        </button>

        <hr>

        <h6 class="mb-3"><i class="bi bi-cloud-upload"></i> Laporan Pembelian</h6>
        <form action="{{ route('requester.submit-report', $request->request_id) }}"
              method="POST"
              enctype="multipart/form-data"
              id="reportForm{{ $slotNum }}">
            @csrf

            <div class="mb-2">
                <label class="form-label">Tanggal Pengisian <span class="text-danger">*</span></label>
                <input type="date"
                       class="form-control form-control-sm"
                       name="purchase_date"
                       required
                       max="{{ date('Y-m-d') }}">
                <small class="text-muted">Tanggal transaksi pengisian BBM ke SPBU</small>
            </div>

            <div class="mb-2">
                <label class="form-label">Volume Pengisian (Liter) <span class="text-danger">*</span></label>
                <input type="number"
                       class="form-control form-control-sm"
                       name="volume_bbm"
                       required
                       min="0"
                       step="0.01">
                <small class="text-muted">Lihat dari "Volume" pada struk BBM</small>
            </div>

            <div class="mb-2">
                <label class="form-label">KM Akhir <span class="text-danger">*</span></label>
                <input type="number"
                       class="form-control form-control-sm"
                       name="km_akhir"
                       required
                       min="0">
                <small class="text-muted">Isi dengan 0 jika untuk peralatan</small>
            </div>

            <div class="mb-2">
                <label class="form-label">Foto KM Terakhir <span class="text-danger">*</span></label>
                <input type="file"
                       class="form-control form-control-sm"
                       name="foto_km"
                       accept="image/*"
                       required
                       onchange="previewImage(this, 'previewKm{{ $slotNum }}')">
                <img id="previewKm{{ $slotNum }}"
                     class="img-thumbnail mt-2"
                     style="max-height: 150px; display: none;">
            </div>

            <div class="mb-3">
                <label class="form-label">Foto Struk BBM <span class="text-danger">*</span></label>
                <input type="file"
                       class="form-control form-control-sm"
                       name="foto_struk"
                       accept="image/*"
                       required
                       onchange="previewImage(this, 'previewStruk{{ $slotNum }}')">
                <img id="previewStruk{{ $slotNum }}"
                     class="img-thumbnail mt-2"
                     style="max-height: 150px; display: none;">
            </div>

            <button type="submit"
                    class="btn btn-primary btn-sm w-100"
                    id="submitReport{{ $slotNum }}">
                <i class="bi bi-send"></i> Kirim Laporan
            </button>
        </form>
    </div>
</div>

<style>
.voucher-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
    border-radius: 8px;
}

.voucher-code {
    font-size: 1.2rem;
    font-weight: bold;
    letter-spacing: 2px;
    font-family: 'Courier New', monospace;
    padding: 8px;
    background: rgba(255,255,255,0.2);
    border-radius: 6px;
    margin-top: 5px;
}
</style>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        // Check file size (max 5MB)
        if (input.files[0].size > 5 * 1024 * 1024) {
            alert('Ukuran file terlalu besar! Maksimal 5MB');
            input.value = '';
            preview.style.display = 'none';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// function printVoucher(requestId) {
//     window.open('{{ route("requester.print-voucher", ":id") }}'.replace(':id', requestId), '_blank');
// }
</script>
