{{-- Sub-tabs untuk History dan Reports --}}
<ul class="nav nav-pills mb-3" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="all-requests-subtab" data-bs-toggle="tab" data-bs-target="#all-requests-pane" type="button">
      <i class="bi bi-list-check"></i> Semua Permohonan
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="reports-subtab" data-bs-toggle="tab" data-bs-target="#reports-pane" type="button">
      <i class="bi bi-file-earmark-check"></i> Laporan Transaksi
    </button>
  </li>
</ul>

<div class="tab-content">
  {{-- Sub-Tab 1: All Requests --}}
  <div class="tab-pane fade show active" id="all-requests-pane">
    <div class="table-responsive">
      <table class="table table-hover" id="allRequestsTable">
        <thead>
          <tr>
            <th>Request ID</th>
            <th>Driver</th>
            <th>Tanggal</th>
            <th>Kendaraan</th>
            <th>BBM</th>
            <th>Jumlah</th>
            <th>Status</th>
            <th>Voucher</th>
            <th>Admin</th>
          </tr>
        </thead>
        <tbody>
          @forelse($allRequests as $req)
            <tr>
              <td><small class="text-muted">{{ $req->request_number }}</small></td>
              <td><strong>{{ $req->requester->name }}</strong></td>
              <td data-order="{{ $req->request_date->timestamp }}">
                <small>{{ $req->request_date->format('d/m/Y H:i') }}</small>
              </td>
              <td>
                <div><strong>{{ $req->vehicle->consumerial_name }}</strong></div>
                <small class="text-muted">{{ $req->vehicle->consumerial_type }}</small>
              </td>
              <td><span class="badge bg-info">{{ $req->gasoline_type }}</span></td>
              <td data-order="{{ $req->bill_amounts }}">
                <strong>Rp {{ number_format($req->bill_amounts, 0, ',', '.') }}</strong>
              </td>
              <td>
                @php
                  $statusClass = $req->status == 'pending' ? 'bg-warning text-dark' :
                               ($req->status == 'approved' ? 'bg-success' :
                               ($req->status == 'completed' ? 'bg-primary' : 'bg-danger'));
                  $statusIcon = $req->status == 'pending' ? 'bi-hourglass-split' :
                              ($req->status == 'approved' ? 'bi-check-circle-fill' :
                              ($req->status == 'completed' ? 'bi-check-all' : 'bi-x-circle-fill'));
                @endphp
                <span class="badge {{ $statusClass }}">
                  <i class="bi {{ $statusIcon }}"></i> {{ strtoupper($req->status) }}
                </span>
              </td>
              <td>
                @if($req->approvedRequest)
                  <span class="badge bg-light text-dark" style="font-family: 'Courier New', monospace;">
                    {{ $req->approvedRequest->voucher_code }}
                  </span>
                @else
                  <span class="text-muted">-</span>
                @endif
              </td>
              <td>
                <small class="text-muted">
                  {{ $req->authorizer ? $req->authorizer->nama_lengkap : '-' }}
                </small>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9">
                <div class="text-center py-4 text-muted">
                  <i class="bi bi-file-earmark-text" style="font-size: 2rem; opacity: 0.5;"></i>
                  <div class="mt-2">Belum ada permohonan</div>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Sub-Tab 2: Transaction Reports --}}
  <div class="tab-pane fade" id="reports-pane">
    <div class="table-responsive">
      <table class="table table-hover" id="reportsTable">
        <thead>
          <tr>
            <th>Transaction ID</th>
            <th>Request ID</th>
            <th>Pemohon</th>
            <th>Kendaraan</th>
            <th>Voucher</th>
            <th>Tanggal Pengisian</th>
            <th>Volume (L)</th>
            <th>KM Akhir</th>
            <th>Foto</th>
            <th>Tanggal Lapor</th>
          </tr>
        </thead>
        <tbody>
          @forelse($reports as $report)
            <tr>
              <td><small class="text-muted">{{ $report->transaction_id }}</small></td>
              <td><small class="text-muted">{{ $report->permohonan->request_number }}</small></td>
              <td><strong>{{ $report->permohonan->requester->nama_lengkap }}</strong></td>
              <td>
                <small>{{ $report->permohonan->vehicle->consumerial_name }}</small>
              </td>
              <td>
                <span class="badge bg-light text-dark" style="font-family: 'Courier New', monospace; font-size: 0.75rem;">
                  {{ $report->voucher_number }}
                </span>
              </td>
              <td data-order="{{ $report->purchase_datetime ? strtotime($report->purchase_datetime) : 0 }}">
                <small>{{ $report->purchase_datetime ? date('d/m/Y H:i', strtotime($report->purchase_datetime)) : '-' }}</small>
              </td>
              <td><strong>{{ $report->fuel_volume }} L</strong></td>
              <td><strong>{{ number_format($report->km_terakhir, 0, ',', '.') }}</strong></td>
              <td>
                <div class="btn-group btn-group-sm" role="group">
                  @if($report->odometer_photo_path)
                    <button class="btn btn-outline-primary btn-sm" onclick="showPhoto('{{ asset($report->odometer_photo_path) }}', 'Foto Odometer')" title="Lihat Foto KM">
                      <i class="bi bi-speedometer2"></i>
                    </button>
                  @endif
                  @if($report->struk_bbm_path)
                    <button class="btn btn-outline-success btn-sm" onclick="showPhoto('{{ asset('storage/'.$report->struk_bbm_path.'') }}', 'Foto Struk BBM')" title="Lihat Foto Struk">
                      <i class="bi bi-receipt"></i>
                    </button>
                  @endif
                </div>
              </td>
              <td data-order="{{ $report->created_at->timestamp }}">
                <small>{{ $report->created_at->format('d/m/Y H:i') }}</small>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10">
                <div class="text-center py-4 text-muted">
                  <i class="bi bi-file-earmark-x" style="font-size: 2rem; opacity: 0.5;"></i>
                  <div class="mt-2">Belum ada laporan transaksi</div>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- Modal Preview Foto --}}
<div class="modal fade" id="photoModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="photoModalTitle">Preview Foto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <img id="photoModalImage" src="" alt="Preview" style="max-width: 100%; height: auto; border-radius: 8px;">
      </div>
      <div class="modal-footer">
        <a id="photoModalLink" href="" target="_blank" class="btn btn-primary">
          <i class="bi bi-box-arrow-up-right"></i> Buka di Tab Baru
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
<script>
  // Show photo in modal
  function showPhoto(photoUrl, title) {
    if (!photoUrl || photoUrl === '') {
      alert('Foto tidak tersedia');
      return;
    }

    document.getElementById('photoModalTitle').textContent = title;
    document.getElementById('photoModalImage').src = photoUrl;
    document.getElementById('photoModalLink').href = photoUrl;

    const modal = new bootstrap.Modal(document.getElementById('photoModal'));
    modal.show();
  }

  document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable for All Requests when history tab is shown
    document.getElementById('history-tab').addEventListener('shown.bs.tab', function () {
      const allRequestsTable = document.getElementById('allRequestsTable');
      if (allRequestsTable && allRequestsTable.querySelector('tbody tr td:not([colspan])')) {
        if (!window.allRequestsDataTable) {
          window.allRequestsDataTable = new simpleDatatables.DataTable(allRequestsTable, {
            searchable: true,
            fixedHeight: false,
            perPage: 10,
            perPageSelect: [5, 10, 25, 50, 100],
            labels: {
              placeholder: "Cari permohonan...",
              perPage: "Per halaman:",
              noRows: "Tidak ada data",
              info: "Menampilkan {start} sampai {end} dari {rows} data"
            },
            columns: [
              { select: 2, sort: "desc" } // Sort by date
            ]
          });
        }
      }
    });

    // Initialize DataTable for Reports when reports sub-tab is shown
    document.getElementById('reports-subtab').addEventListener('shown.bs.tab', function () {
      const reportsTable = document.getElementById('reportsTable');
      if (reportsTable && reportsTable.querySelector('tbody tr td:not([colspan])')) {
        if (!window.reportsDataTable) {
          window.reportsDataTable = new simpleDatatables.DataTable(reportsTable, {
            searchable: true,
            fixedHeight: false,
            perPage: 10,
            perPageSelect: [5, 10, 25, 50, 100],
            labels: {
              placeholder: "Cari laporan...",
              perPage: "Per halaman:",
              noRows: "Tidak ada data",
              info: "Menampilkan {start} sampai {end} dari {rows} data"
            },
            columns: [
              { select: 9, sort: "desc" }, // Sort by report date
              { select: 8, sortable: false } // Disable sort on photo column
            ]
          });
        }
      }
    });
  });
</script>
