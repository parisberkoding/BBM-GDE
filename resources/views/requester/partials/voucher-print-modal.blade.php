<!-- Modal Print Voucher -->
<div class="modal fade" id="voucherModal{{ $slotNum }}" tabindex="-1" aria-labelledby="voucherModalLabel{{ $slotNum }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="voucherModalLabel{{ $slotNum }}">
                    <i class="bi bi-ticket-perforated"></i> Voucher BBM
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="voucherContent{{ $slotNum }}">
                <!-- Logo & Header -->
                <div class="text-center mb-4">
                    <img src="https://jojo-static-files-klfm7hjb.s3.ap-southeast-3.amazonaws.com/logo-geodipa-officeless.png"
                         style="width:80px;" alt="Logo Geodipa">
                    <h5 class="mt-2 mb-0">PT. Geo Dipa Energi (Persero)</h5>
                    <p class="mb-0">Unit Patuha</p>
                </div>

                <!-- Voucher Box -->
                <div class="voucher-box mb-3">
                    <div class="mb-2"><i class="bi bi-ticket-perforated"></i> KODE VOUCHER BBM</div>
                    <div class="voucher-code">{{ $request->approvedRequest->voucher_code ?? 'N/A' }}</div>
                </div>

                <!-- Details Table -->
                <table class="table table-bordered">
                    <tr>
                        <th width="180">No. Permohonan</th>
                        <td><code>{{ $request->request_number }}</code></td>
                    </tr>
                    <tr>
                        <th>Pemohon</th>
                        <td>{{ $request->requester->nama_lengkap ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Kendaraan/Alat</th>
                        <td>
                            {{ $request->vehicle->consumerial_code ?? 'N/A' }}<br>
                            <small class="text-muted">{{ $request->vehicle->consumerial_name ?? '' }} ({{ $request->vehicle->consumerial_type ?? '' }})</small>
                        </td>
                    </tr>
                    <tr>
                        <th>Jenis BBM</th>
                        <td><span class="badge bg-secondary">{{ $request->gasoline_type }}</span></td>
                    </tr>
                    <tr>
                        <th>Nominal</th>
                        <td><strong class="text-success">Rp {{ number_format($request->bill_amounts, 0, ',', '.') }}</strong></td>
                    </tr>
                    <tr>
                        <th>Tanggal Disetujui</th>
                        <td>{{ $request->authorization_date ? $request->authorization_date->format('d M Y H:i') : '-' }}</td>
                    </tr>
                </table>

                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i> Pilih metode cetak di bawah ini
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-outline-primary" onclick="printVoucherPDF({{ $slotNum }})">
                    <i class="bi bi-file-pdf"></i> Cetak PDF
                </button>
                <button type="button" class="btn btn-info" onclick="printViaEpson({{ $slotNum }})">
                    <i class="bi bi-printer"></i> Epson Thermal
                </button>
                <button type="button" class="btn btn-success" onclick="printViaThermer({{ $slotNum }})">
                    <i class="bi bi-phone"></i> Thermer App
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Epson Printer Selection Modal -->
<div class="modal fade" id="epsonPrinterModal{{ $slotNum }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-printer"></i> Pilih Printer Epson</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="epsonPrinterList{{ $slotNum }}" class="mb-3">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Mencari printer...</span>
                        </div>
                        <p class="mt-2 text-muted">Mencari printer Epson di jaringan...</p>
                    </div>
                </div>

                <div class="alert alert-info">
                    <strong>Pastikan:</strong>
                    <ul class="mb-0">
                        <li>Printer Epson sudah terhubung ke jaringan yang sama</li>
                        <li>Printer dalam keadaan menyala</li>
                        <li>Browser mendukung Web API (Chrome/Edge)</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="searchEpsonPrinters({{ $slotNum }})">
                    <i class="bi bi-arrow-clockwise"></i> Cari Ulang
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.voucher-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.voucher-code {
    font-size: 1.5rem;
    font-weight: bold;
    letter-spacing: 3px;
    font-family: 'Courier New', monospace;
    padding: 10px;
    background: rgba(255,255,255,0.2);
    border-radius: 8px;
    margin: 10px 0;
}

@media print {
    .modal-header, .modal-footer { display: none; }
    .voucher-box { box-shadow: none; }
}
</style>

<script>
// Store voucher data for slot {{ $slotNum }}
window.voucherData{{ $slotNum }} = {
    requestNumber: "{{ $request->request_number }}",
    voucherCode: "{{ $request->approvedRequest->voucher_code ?? 'N/A' }}",
    requesterName: "{{ $request->requester->nama_lengkap ?? 'N/A' }}",
    vehicleCode: "{{ $request->vehicle->consumerial_code ?? 'N/A' }}",
    vehicleName: "{{ $request->vehicle->consumerial_name ?? '' }}",
    vehicleType: "{{ $request->vehicle->consumerial_type ?? '' }}",
    gasolineType: "{{ $request->gasoline_type }}",
    billAmount: {{ $request->bill_amounts }},
    approvalDate: "{{ $request->authorization_date ? $request->authorization_date->format('d M Y H:i') : '-' }}"
};
</script>
