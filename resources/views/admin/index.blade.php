@extends('templates.main')

@section('title', 'Admin Dashboard')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet">
<style>
  .stat-card {
    padding: 20px;
    border-radius: 10px;
    background: #fff;
    border-left: 4px solid;
    box-shadow: 0px 0 20px rgba(1, 41, 112, 0.08);
    transition: transform 0.3s ease;
    cursor: pointer;
  }

  .stat-card:hover {
    transform: translateY(-5px);
  }

  .stat-card h2 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 5px;
  }

  .stat-pending { border-left-color: #ffbb2c; }
  .stat-pending h2 { color: #ffbb2c; }

  .stat-approved { border-left-color: #4154f1; }
  .stat-approved h2 { color: #4154f1; }

  .stat-rejected { border-left-color: #ff771d; }
  .stat-rejected h2 { color: #ff771d; }

  .stat-total { border-left-color: #2eca6a; }
  .stat-total h2 { color: #2eca6a; }

  .bulk-action-bar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 15px;
    display: none;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
  }

  .bulk-action-bar.show {
    display: block;
    animation: slideDown 0.3s ease;
  }

  @keyframes slideDown {
    from {
      opacity: 0;
      transform: translateY(-10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .action-checkbox-group {
    display: flex;
    gap: 15px;
    align-items: center;
  }

  .form-check-label {
    font-size: 0.9rem;
    margin-left: 5px;
    cursor: pointer;
  }

  .nav-tabs .nav-link {
    color: #899bbd;
    border: none;
    border-bottom: 2px solid transparent;
  }

  .nav-tabs .nav-link.active {
    color: #4154f1;
    border-bottom: 2px solid #4154f1;
    background: transparent;
  }
</style>
@endpush

@section('content')
<div class="container-fluid">
  <!-- Alert Messages -->
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <strong>Berhasil!</strong> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <strong>Error!</strong> {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <!-- Statistics Cards -->
  <div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
      <div class="stat-card stat-pending">
        <h2>{{ $stats['pending'] }}</h2>
        <div class="text-muted">Pending</div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
      <div class="stat-card stat-approved">
        <h2>{{ $stats['approved'] }}</h2>
        <div class="text-muted">Approved</div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
      <div class="stat-card stat-rejected">
        <h2>{{ $stats['rejected'] }}</h2>
        <div class="text-muted">Rejected</div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3">
      <div class="stat-card stat-total">
        <h2>{{ $stats['total'] }}</h2>
        <div class="text-muted">Total Permohonan</div>
      </div>
    </div>
  </div>

  <!-- Main Card with Tabs -->
  <div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <span><i class="bi bi-list-ul"></i> Permohonan BBM</span>
      <button class="btn btn-light btn-sm" onclick="window.location.reload()">
        <i class="bi bi-arrow-clockwise"></i> Refresh
      </button>
    </div>
    <div class="card-body">
      <!-- Navigation Tabs -->
      <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-pane" type="button">
            <i class="bi bi-hourglass-split"></i> Permohonan Pending ({{ $stats['pending'] }})
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="myapproval-tab" data-bs-toggle="tab" data-bs-target="#myapproval-pane" type="button">
            <i class="bi bi-person-check"></i> Riwayat Approval Saya
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-pane" type="button">
            <i class="bi bi-clock-history"></i> Semua History & Laporan
          </button>
        </li>
      </ul>

      <div class="tab-content">

<!-- Tab 1: Pending Requests (Main - Bulk Approval) -->
<div class="tab-pane fade show active" id="pending-pane">
  {{-- Form untuk Bulk Action --}}
  <form method="POST" action="{{ route('admin-bulk-process') }}" id="bulkApprovalForm">
    @csrf

    {{-- Hidden input untuk menyimpan decisions dalam format JSON --}}
    <input type="hidden" name="decisions" id="decisionsInput" value="">

    {{-- Bulk Action Bar --}}
    <div class="bulk-action-bar" id="bulkActionBar">
      <div class="d-flex justify-content-between align-items-center">
        <span class="text-secondary">
          <i class="bi bi-check-square-fill"></i>
          <strong><span id="selectedCount">0</span></strong> permohonan dipilih
          <small class="ms-2">
            (<span id="approveCount" class="text-success">0</span> approve,
            <span id="rejectCount" class="text-danger">0</span> reject)
          </small>
        </span>
        <button class="btn btn-light" type="button" onclick="window.saveBulkActions()">
          <i class="bi bi-save"></i> Simpan Keputusan
        </button>
      </div>
    </div>

    {{-- Pending Requests Table --}}
    <div class="table-responsive">
      <table class="table table-hover" id="pendingTable">
        <thead>
          <tr>
            <th style="width: 120px;">Request ID</th>
            <th>Pemohon</th>
            <th>Tanggal</th>
            <th>Kendaraan</th>
            <th>BBM</th>
            <th>Jumlah</th>
            <th>Catatan</th>
            <th>Catatan Admin</th>
            <th style="width: 220px;">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($pendingRequests as $req)
            <tr>
              <td><small class="text-muted">{{ $req->request_number }}</small></td>
              <td><strong>{{ $req->requester->nama_lengkap }}</strong></td>
              <td data-order="{{ $req->request_date->timestamp }}">
                <small>{{ $req->request_date->format('d/m/Y H:i') }}</small>
              </td>
              <td>
                <div><strong>{{ $req->vehicle->consumerial_name }}</strong></div>
                <small class="text-muted">{{ $req->vehicle->consumerial_type }}</small>
              </td>
              <td><span class="badge badge-sm bg-info">{{ $req->gasoline_type }}</span></td>
              <td data-order="{{ $req->bill_amounts }}">
                <strong>Rp {{ number_format($req->bill_amounts, 0, ',', '.') }}</strong>
              </td>
              <td><small class="text-muted">{{ $req->requester_notes ?? '-' }}</small></td>
              <td>
                <input type="text" name="catatan_admin[]" id="catatan_admin_{{ $req->id }}" class="form-control" placeholder="Catatan Admin (opsional)">
              </td>
              <td>
                <div class="action-checkbox-group">
                  <div class="form-check">
                    <input class="form-check-input decision-checkbox" type="checkbox"
                           data-id="{{ $req->id }}"
                           data-action="approve"
                           id="approve_{{ $req->id }}">
                    <label class="form-check-label text-success" for="approve_{{ $req->id }}">
                      <i class="bi bi-check-circle"></i> Approve
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input decision-checkbox" type="checkbox"
                           data-id="{{ $req->id }}"
                           data-action="reject"
                           id="reject_{{ $req->id }}">
                    <label class="form-check-label text-danger" for="reject_{{ $req->id }}">
                      <i class="bi bi-x-circle"></i> Reject
                    </label>
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8">
                <div class="text-center py-4 text-muted">
                  <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.5;"></i>
                  <div class="mt-2">Tidak ada permohonan pending</div>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </form>
</div>

        <!-- Tab 2: My Approval History -->
        <div class="tab-pane fade" id="myapproval-pane">
          @include('admin.myapproval')
        </div>

        <!-- Tab 3: All History & Reports -->
        <div class="tab-pane fade" id="history-pane">
          @include('admin.requester_reports')
        </div>
      </div>
    </div>
  </div>
</div>


<script>
  // ✅ Global object untuk menyimpan keputusan
  // Format: { "123": "approve", "124": "reject" }
  const bulkDecisions = {};
  let pendingDataTable = null;

  // Initialize everything when DOM is ready
  document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initializing Admin Dashboard...');
    initializeDataTable();
    initializeCheckboxes();
    console.log('✅ Dashboard initialized successfully');
  });

  // Initialize DataTable
  function initializeDataTable() {
    const pendingTable = document.getElementById('pendingTable');
    if (pendingTable && pendingTable.querySelector('tbody tr td:not([colspan])')) {
      try {
        pendingDataTable = new simpleDatatables.DataTable(pendingTable, {
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
            { select: 2, sort: "desc" },
            { select: 7, sortable: false }
          ]
        });

        setTimeout(() => {
          initializeCheckboxes();
          console.log('✅ Checkbox listeners re-attached after DataTable');
        }, 100);

        console.log('✅ DataTable initialized');
      } catch (error) {
        console.error('❌ DataTable initialization error:', error);
      }
    }
  }

  // Initialize checkbox event listeners
  function initializeCheckboxes() {
    const checkboxes = document.querySelectorAll('.decision-checkbox');
    console.log(`📋 Found ${checkboxes.length} checkboxes`);

    checkboxes.forEach(checkbox => {
      checkbox.addEventListener('change', function() {
        const requestId = this.dataset.id;
        const action = this.dataset.action;
        handleCheckboxChange(requestId, action, this.checked);
      });
    });

    console.log('✅ Checkbox listeners attached');
  }

  // Handle checkbox change
  function handleCheckboxChange(requestId, action, isChecked) {
    const approveCheckbox = document.getElementById(`approve_${requestId}`);
    const rejectCheckbox = document.getElementById(`reject_${requestId}`);

    console.log(`🔄 Checkbox: ID=${requestId}, Action=${action}, Checked=${isChecked}`);

    if (action === 'approve') {
      if (isChecked) {
        rejectCheckbox.checked = false;
        bulkDecisions[requestId] = 'approve';
      } else {
        delete bulkDecisions[requestId];
      }
    }

    if (action === 'reject') {
      if (isChecked) {
        approveCheckbox.checked = false;
        bulkDecisions[requestId] = 'reject';
      } else {
        delete bulkDecisions[requestId];
      }
    }

    console.log('📊 Current decisions:', bulkDecisions);
    updateBulkActionBar();
  }

  // Update bulk action bar
  function updateBulkActionBar() {
    const totalSelected = Object.keys(bulkDecisions).length;
    const approveCount = Object.values(bulkDecisions).filter(v => v === 'approve').length;
    const rejectCount = Object.values(bulkDecisions).filter(v => v === 'reject').length;

    document.getElementById('selectedCount').textContent = totalSelected;
    document.getElementById('approveCount').textContent = approveCount;
    document.getElementById('rejectCount').textContent = rejectCount;

    const bulkActionBar = document.getElementById('bulkActionBar');
    if (totalSelected > 0) {
      bulkActionBar.classList.add('show');
    } else {
      bulkActionBar.classList.remove('show');
    }
  }

  // Save bulk actions
  window.saveBulkActions = function() {
    console.log('💾 saveBulkActions called!');

    const totalSelected = Object.keys(bulkDecisions).length;
    if (totalSelected === 0) {
      alert('❌ Tidak ada permohonan yang dipilih');
      return;
    }

    // ✅ Convert decisions object ke array dengan struktur yang diminta
    // Format: [{ "request_id": 123, "status": "approve" }, ...]
    const decisionsArray = [];
    for (const [requestId, status] of Object.entries(bulkDecisions)) {
      decisionsArray.push({
        request_id: parseInt(requestId),
        status: status
      });
    }

    console.log('📋 Decisions to send:', decisionsArray);

    const approveCount = decisionsArray.filter(d => d.status === 'approve').length;
    const rejectCount = decisionsArray.filter(d => d.status === 'reject').length;

    // Confirm
    const message = `Simpan keputusan?\n\n` +
                    `✅ Approve: ${approveCount} permohonan\n` +
                    `❌ Reject: ${rejectCount} permohonan`;

    if (!confirm(message)) {
      console.log('❌ User cancelled');
      return;
    }

    // ✅ Set hidden input dengan JSON string
    const decisionsInput = document.getElementById('decisionsInput');
    decisionsInput.value = JSON.stringify(decisionsArray);

    console.log('📤 Submitting form with decisions:', decisionsInput.value);

    // Submit form
    document.getElementById('bulkApprovalForm').submit();
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest"></script>

@endsection
