<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Permohonan;
use App\Models\VehicleAndToolsConsumers;
use App\Models\GasolineType;
use App\Models\Approved_Request;
use App\Models\Transaction_Proofs;
use Carbon\Carbon;

class RequestManagementController extends Controller
{
    /**
     * =========================================
     * Controller bagian Requester
     * =========================================
     */

    public function requester_index()
    {
        $title = "Dashboard Requester";
        $user = Auth::user();

        // Get active requests (pending + approved)
        $activeRequests = Permohonan::where('requester_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->get();

        $activeCount = $activeRequests->count();

        // Get stats
        $pendingCount = Permohonan::where('requester_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $approvedCount = Permohonan::where('requester_id', $user->id)
            ->where('status', 'approved')
            ->count();

        $completedCount = Permohonan::where('requester_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $rejectedCount = Permohonan::where('requester_id', $user->id)
            ->where('status', 'rejected')
            ->count();

        $totalRequests = Permohonan::where('requester_id', $user->id)->count();

        // Get active slots (max 5)
        $activeSlots = [];
        foreach ($activeRequests->take(5) as $request) {
            $activeSlots[] = $request;
        }

        // Get all vehicles and tools
        $vehicles = VehicleAndToolsConsumers::orderBy('consumerial_type')
            ->orderBy('consumerial_name')
            ->get();

        // Get gasoline types
        $gasolineTypes = GasolineType::all();

        return view('requester.view_request_bbm', compact(
            'title',
            'activeRequests',
            'activeCount',
            'pendingCount',
            'approvedCount',
            'completedCount',
            'rejectedCount',
            'totalRequests',
            'activeSlots',
            'vehicles',
            'gasolineTypes'
        ));
    }

    public function requester_create(Request $request)
    {
        $user = Auth::user();

        // Validasi: Max 5 active requests
        $activeCount = Permohonan::where('requester_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->count();

        if ($activeCount >= 5) {
            return back()->with('error', 'Anda sudah memiliki 5 permohonan aktif. Selesaikan permohonan terlebih dahulu sebelum membuat yang baru.');
        }

        // Validasi input
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicle_and_tools_consumers,id',
            'gasoline_type' => 'required|string',
            'bill_payment' => 'required|numeric|min:150000|max:850000',
            'requester_notes' => 'nullable|string|max:500'
        ], [
            'vehicle_id.required' => 'Kendaraan/Alat harus dipilih',
            'vehicle_id.exists' => 'Kendaraan/Alat tidak valid',
            'gasoline_type.required' => 'Jenis BBM harus dipilih',
            'bill_payment.required' => 'Nominal harus dipilih',
            'bill_payment.min' => 'Nominal minimal Rp 150.000',
            'bill_payment.max' => 'Nominal maksimal Rp 850.000',
        ]);

        try {
            DB::beginTransaction();

            // Generate request number
            $requestNumber = $this->generateRequestNumber();

            // Create permohonan
            $permohonan = Permohonan::create([
                'request_number' => $requestNumber,
                'requester_id' => $user->id,
                'request_date' => now(),
                'gasoline_type' => $validated['gasoline_type'],
                'bill_amounts' => $validated['bill_payment'],
                'status' => 'pending',
                'requester_notes' => $validated['requester_notes'] ?? null,
                'consumerial_tools_id' => $validated['vehicle_id']
            ]);

            DB::commit();

            return redirect()->route('requester-index')
                ->with('success', 'Permohonan BBM berhasil diajukan dengan nomor: ' . $requestNumber);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Gagal mengajukan permohonan: ' . $e->getMessage());
        }
    }

    /**
     * Generate unique request number
     * Format: REQ-DDMMYYYY-RANDOM6
     */
    private function generateRequestNumber()
    {
        $date = now()->format('dmY');
        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        return "REQ-{$date}-{$random}";
    }

    /**
     * Show history of all requests
     */
    public function requester_history()
    {
        $title = "Riwayat Permohonan BBM";
        $user = Auth::user();

        // Get all requests with relationships
        $history = Permohonan::where('requester_id', $user->id)
            ->with(['vehicle', 'authorizer', 'approvedRequest', 'transactionProof'])
            ->orderBy('request_date', 'desc')
            ->paginate(20);

        return view('requester.history_request', compact('title', 'history'));
    }

    /**
 * Submit transaction report (laporan pembelian BBM)
 */
/**
 * Submit transaction report (laporan pembelian BBM)
 */
public function requester_submit_report(Request $request, $id)
{
    $permohonan = Permohonan::with('vehicle')
        ->where('requester_id', Auth::id())
        ->where('status', 'approved')
        ->findOrFail($id);

    // Cek apakah sudah pernah submit report
    if ($permohonan->transactionProof) {
        return back()->with('error', 'Laporan untuk permohonan ini sudah pernah dikirim.');
    }

    // Tentukan apakah ini kendaraan atau alat
    $isVehicle = $permohonan->vehicle && $permohonan->vehicle->consumerial_type === 'Kendaraan';

    // Validasi dinamis berdasarkan tipe
    $rules = [
        'purchase_date' => 'required|date|before_or_equal:today',
        'volume_bbm' => 'required|numeric|min:0.01',
        'foto_struk' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB
    ];

    $messages = [
        'purchase_date.required' => 'Tanggal pengisian harus diisi',
        'purchase_date.date' => 'Format tanggal tidak valid',
        'purchase_date.before_or_equal' => 'Tanggal pengisian tidak boleh melebihi hari ini',
        'volume_bbm.required' => 'Volume pengisian harus diisi',
        'volume_bbm.numeric' => 'Volume harus berupa angka',
        'volume_bbm.min' => 'Volume minimal 0.01 liter',
        'foto_struk.required' => 'Foto struk BBM harus diupload',
        'foto_struk.image' => 'File harus berupa gambar',
        'foto_struk.mimes' => 'Format gambar harus jpeg, png, atau jpg',
        'foto_struk.max' => 'Ukuran file maksimal 5MB',
    ];

    // Jika kendaraan, wajib isi KM dan foto odometer
    if ($isVehicle) {
        $rules['km_akhir'] = 'required|numeric|min:0';
        $rules['foto_km'] = 'required|image|mimes:jpeg,png,jpg|max:5120';

        $messages['km_akhir.required'] = 'KM akhir harus diisi untuk kendaraan';
        $messages['km_akhir.numeric'] = 'KM akhir harus berupa angka';
        $messages['km_akhir.min'] = 'KM akhir tidak boleh negatif';
        $messages['foto_km.required'] = 'Foto odometer harus diupload untuk kendaraan';
        $messages['foto_km.image'] = 'File harus berupa gambar';
        $messages['foto_km.mimes'] = 'Format gambar harus jpeg, png, atau jpg';
        $messages['foto_km.max'] = 'Ukuran file maksimal 5MB';
    } else {
        // Untuk alat, KM dan foto odometer opsional (akan di-set 0 dan null)
        $rules['km_akhir'] = 'nullable|numeric|min:0';
        $rules['foto_km'] = 'nullable|image|mimes:jpeg,png,jpg|max:5120';
    }

    // Validasi request
    $validated = $request->validate($rules, $messages);

    try {
        DB::beginTransaction();

        // Generate nama file dengan format: STRUK-REQUESTNUMBER-DDMMYYYY
        $dateFormat = now()->format('dmY');
        $requestNumber = $permohonan->request_number;

        // Upload foto struk BBM (wajib untuk semua)
        $strukFile = $request->file('foto_struk');
        $strukExtension = $strukFile->getClientOriginalExtension();
        $strukFileName = "STRUK-{$requestNumber}-{$dateFormat}.{$strukExtension}";
        $strukPath = $strukFile->storeAs('transaction_proofs/struk', $strukFileName, 'public');

        // Upload foto odometer (hanya jika ada)
        $odoPath = null;
        if ($request->hasFile('foto_km')) {
            $odoFile = $request->file('foto_km');
            $odoExtension = $odoFile->getClientOriginalExtension();
            $odoFileName = "ODO-{$requestNumber}-{$dateFormat}.{$odoExtension}";
            $odoPath = $odoFile->storeAs('transaction_proofs/odometer', $odoFileName, 'public');
        }

        // Get voucher code from approved_requests
        $approvedRequest = Approved_Request::where('permohonan_id', $permohonan->id)->first();

        // Generate transaction ID
        $transactionId = 'TRX-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -6));

        // Gabungkan tanggal dengan waktu sekarang untuk purchase_datetime
        $purchaseDatetime = $validated['purchase_date'] . ' ' . now()->format('H:i:s');

        // Set KM akhir: jika alat dan tidak diisi, set 0
        $kmAkhir = 0;
        if ($isVehicle) {
            $kmAkhir = $validated['km_akhir'];
        } elseif (isset($validated['km_akhir'])) {
            $kmAkhir = $validated['km_akhir'];
        }

        // Create transaction proof
        Transaction_Proofs::create([
            'transaction_id' => $transactionId,
            'req_id' => $permohonan->id,
            'requester_id' => Auth::id(),
            'voucher_number' => $approvedRequest ? $approvedRequest->voucher_code : null,
            'purchase_datetime' => $purchaseDatetime,
            'fuel_volume' => $validated['volume_bbm'],
            'km_terakhir' => $kmAkhir,
            'struk_bbm_path' => $strukPath,
            'odometer_photo_path' => $odoPath
        ]);

        // Update status permohonan menjadi completed
        $permohonan->update(['status' => 'completed']);

        // Update status approved_request menjadi completed
        if ($approvedRequest) {
            $approvedRequest->update(['status' => 'completed']);
        }

        DB::commit();

        return redirect()->route('requester-index')
            ->with('success', 'Laporan pembelian BBM berhasil dikirim. Permohonan selesai.');

    } catch (\Exception $e) {
        DB::rollBack();

        // Hapus file yang sudah terupload jika ada error
        if (isset($strukPath) && Storage::disk('public')->exists($strukPath)) {
            Storage::disk('public')->delete($strukPath);
        }
        if (isset($odoPath) && Storage::disk('public')->exists($odoPath)) {
            Storage::disk('public')->delete($odoPath);
        }

        return back()
            ->withInput()
            ->with('error', 'Gagal mengirim laporan: ' . $e->getMessage());
    }
}


    /**
     * Show detail request
     */
    // public function requester_show($id)
    // {
    //     $title = "Detail Permohonan";
    //     $request = Permohonan::with(['vehicle', 'authorizer', 'approvedRequest', 'transactionProof'])
    //         ->where('requester_id', Auth::id())
    //         ->findOrFail($id);

    //     return view('requester.detail_request', compact('title', 'request'));
    // }

    /**
     * Upload bukti transaksi (Transaction Proof)
     */
    public function requester_upload_proof(Request $request, $id)
    {
        $permohonan = Permohonan::where('requester_id', Auth::id())
            ->where('status', 'approved')
            ->findOrFail($id);

        // Validasi
        $validated = $request->validate([
            'purchase_datetime' => 'required|date',
            'fuel_volume' => 'required|numeric|min:1',
            'km_terakhir' => 'nullable|numeric',
            'struk_bbm' => 'required|image|max:2048',
            'odometer_photo' => 'nullable|image|max:2048'
        ], [
            'purchase_datetime.required' => 'Tanggal pengisian harus diisi',
            'fuel_volume.required' => 'Volume pengisian harus diisi',
            'struk_bbm.required' => 'Foto struk BBM harus diupload',
            'struk_bbm.image' => 'File harus berupa gambar',
            'struk_bbm.max' => 'Ukuran file maksimal 2MB'
        ]);

        try {
            DB::beginTransaction();

            // Upload files
            $strukPath = $request->file('struk_bbm')->store('transaction_proofs/struk', 'public');
            $odoPath = $request->hasFile('odometer_photo')
                ? $request->file('odometer_photo')->store('transaction_proofs/odometer', 'public')
                : null;

            // Get voucher code from approved_requests
            $approvedRequest = Approved_Request::where('permohonan_id', $permohonan->id)->first();

            // Generate transaction ID
            $transactionId = 'TRX-' . now()->format('YmdHis') . '-' . strtoupper(substr(uniqid(), -6));

            // Create transaction proof
            Transaction_Proofs::create([
                'transaction_id' => $transactionId,
                'req_id' => $permohonan->id,
                'requester_id' => Auth::id(),
                'voucher_number' => $approvedRequest ? $approvedRequest->voucher_code : null,
                'purchase_datetime' => $validated['purchase_datetime'],
                'fuel_volume' => $validated['fuel_volume'],
                'km_terakhir' => $validated['km_terakhir'] ?? 0,
                'struk_bbm_path' => $strukPath,
                'odometer_photo_path' => $odoPath
            ]);

            // Update status permohonan menjadi completed
            $permohonan->update(['status' => 'completed']);

            DB::commit();

            return redirect()->route('requester-index')
                ->with('success', 'Bukti transaksi berhasil diupload. Permohonan selesai.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Gagal upload bukti: ' . $e->getMessage());
        }
    }

    /**
     * =========================================
     * Controller bagian Admin
     * =========================================
     */

    public function admin_index()
{
    $title = "Dashboard Admin";

    // Get statistics
    $stats = [
        'pending' => Permohonan::where('status', 'pending')->count(),
        'approved' => Permohonan::where('status', 'approved')->count(),
        'completed' => Permohonan::where('status', 'completed')->count(),
        'rejected' => Permohonan::where('status', 'rejected')->count(),
        'total' => Permohonan::count(),
    ];

    // ✅ Load relasi requester dan consumerialTool
    $pendingRequests = Permohonan::with(['requester', 'vehicle'])
        ->where('status', 'pending')
        ->orderBy('request_date', 'desc')
        ->get();

    // ✅ Filter hanya approved/rejected/completed, load relasi
    $myApprovals = Permohonan::with(['requester', 'vehicle', 'authorizer', 'approvedRequest'])
        ->whereIn('status', ['approved', 'rejected', 'completed'])
        ->where('authorizer_id', Auth::id())
        ->orderBy('authorization_date', 'desc')
        ->get();

    // ✅ Load semua relasi, sort by date
    $allRequests = Permohonan::with(['requester', 'vehicle', 'authorizer', 'approvedRequest'])
        ->orderBy('request_date', 'desc')
        ->get();

    // ✅ Load transaction reports
    $reports = Transaction_Proofs::with(['permohonan.requester', 'permohonan.vehicle'])
        ->orderBy('created_at', 'desc')
        ->get();

    return view('admin.index', compact(
        'title',
        'stats',
        'pendingRequests',
        'myApprovals',
        'allRequests',
        'reports'
    ));
}

    public function admin_approval()
    {
        $title = "Approval Permohonan BBM";

        // Get pending requests
        $pendingRequests = Permohonan::with(['requester', 'vehicle'])
            ->where('status', 'pending')
            ->orderBy('request_date', 'desc')
            ->get();

        // Get statistics
        $stats = [
            'pending' => Permohonan::where('status', 'pending')->count(),
            'approved' => Permohonan::where('status', 'approved')->count(),
            'completed' => Permohonan::where('status', 'completed')->count(),
            'rejected' => Permohonan::where('status', 'rejected')->count(),
            'total' => Permohonan::count(),
        ];

        // Get all requests for history
        $allRequests = Permohonan::with(['requester', 'vehicle', 'authorizer'])
            ->orderBy('request_date', 'desc')
            ->paginate(20);

        return view('admin.myapproval', compact('title', 'pendingRequests', 'stats', 'allRequests'));
    }

    /**
     * Approve atau reject request
     */
    public function admin_process_request(Request $request, $id)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'authorizer_notes' => 'nullable|string|max:500'
        ]);

        $permohonan = Permohonan::where('status', 'pending')->findOrFail($id);

        try {
            DB::beginTransaction();

            $action = $validated['action'];
            $newStatus = $action === 'approve' ? 'approved' : 'rejected';

            // Update permohonan
            $permohonan->update([
                'status' => $newStatus,
                'authorizer_id' => Auth::id(),
                'authorization_date' => now(),
                'authorizer_notes' => $validated['authorizer_notes'] ?? null
            ]);

            // Jika approved, buat approved_request dan voucher
            if ($newStatus === 'approved') {
                $voucherCode = $this->generateVoucherCode();

                Approved_Request::create([
                    'permohonan_id' => $permohonan->id,
                    'approval_date' => now(),
                    'voucher_code' => $voucherCode,
                    'valid_until' => now()->addDays(30),
                    'approved_amount' => $permohonan->bill_amounts,
                    'authorizer_id' => Auth::id(),
                    'approval_notes' => $validated['authorizer_notes'] ?? null,
                    'status' => 'active'
                ]);
            }

            DB::commit();

            $message = $newStatus === 'approved'
                ? 'Permohonan berhasil disetujui'
                : 'Permohonan berhasil ditolak';

            return redirect()->route('admin-approval')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses permohonan: ' . $e->getMessage());
        }
    }

    /**
 * Bulk approve/reject - FIXED VERSION
 */
public function admin_bulk_process(Request $request)
{
    // ✅ Step 1: Cek data mentah
    // dd($request->all());

    // Validasi: decisions harus ada dan berupa string JSON
    $validated = $request->validate([
        'decisions' => 'required|string'
    ]);

    // ✅ Step 2: Cek hasil validasi
    // dd($validated);

    // Parse JSON decisions
    $decisions = json_decode($validated['decisions'], true);

    // ✅ Step 3: Cek hasil parsing JSON
    // dd($decisions);

    // Validasi hasil parsing
    if (!is_array($decisions) || empty($decisions)) {
        return back()->with('error', 'Format data tidak valid');
    }

    try {
        DB::beginTransaction();

        $approvedCount = 0;
        $rejectedCount = 0;
        $skippedCount = 0;

        foreach ($decisions as $decision) {
            // Validasi struktur decision
            if (!isset($decision['request_id']) || !isset($decision['status'])) {
                $skippedCount++;
                \Log::warning("Decision tidak valid: ", $decision);
                continue;
            }

            $requestId = $decision['request_id'];
            $status = $decision['status']; // 'approve' or 'reject'

            // Cari permohonan yang masih pending
            $permohonan = Permohonan::where('status', 'pending')->find($requestId);

            if (!$permohonan) {
                $skippedCount++;
                \Log::warning("Permohonan ID {$requestId} tidak ditemukan atau bukan pending");
                continue;
            }

            $newStatus = $status === 'approve' ? 'approved' : 'rejected';

            // Update permohonan
            $permohonan->update([
                'status' => $newStatus,
                'authorizer_id' => Auth::id(),
                'authorization_date' => now(),
                'authorizer_notes' => null
            ]);

            // ✅ Jika APPROVED, buat voucher di approved_requests
            if ($newStatus === 'approved') {
                $voucherCode = $this->generateVoucherCode();

                Approved_Request::create([
                    'permohonan_id' => $permohonan->id,
                    'approval_date' => now(),
                    'voucher_code' => $voucherCode,
                    'valid_until' => now()->addDays(30), // Voucher berlaku 30 hari
                    'approved_amount' => $permohonan->bill_amounts,
                    'authorizer_id' => Auth::id(),
                    'status' => 'approved',
                    'approval_notes' => null
                ]);

                $approvedCount++;
                \Log::info("✅ Approved: Request #{$requestId} | Voucher: {$voucherCode}");
            }
            // ✅ Jika REJECTED, tidak perlu insert ke approved_requests
            else {
                $rejectedCount++;
                \Log::info("❌ Rejected: Request #{$requestId}");
            }
        }

        DB::commit();

        // Build success message
        $message = [];
        if ($approvedCount > 0) {
            $message[] = "✅ {$approvedCount} permohonan disetujui";
        }
        if ($rejectedCount > 0) {
            $message[] = "❌ {$rejectedCount} permohonan ditolak";
        }
        if ($skippedCount > 0) {
            $message[] = "⚠️ {$skippedCount} dilewati (tidak valid/sudah diproses)";
        }

        $finalMessage = implode(' | ', $message);
        \Log::info("Bulk Process Summary: {$finalMessage}");

        return redirect()->route('admin-dashboard')
            ->with('success', $finalMessage);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Bulk process error: ' . $e->getMessage());
        \Log::error($e->getTraceAsString());

        return back()->with('error', 'Gagal memproses bulk action: ' . $e->getMessage());
    }
}
    /**
     * Generate voucher code
     * Format: BBMGDE-RANDOM6-DDMMYY
     */
    private function generateVoucherCode()
    {
        $date = now()->format('dmy');
        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        return "BBMGDE-{$random}-{$date}";
    }

    /**
     * bagian controller untuk cetak ke printer thermal
     *
     * */

    /**
     * Handle Thermer app print request
     * Endpoint untuk aplikasi Thermer (Bluetooth Print)
     */
    public function thermerPrint(Request $request)
    {
        try {
            // Get print data from query parameter
            $encodedData = $request->query('data');

            if (!$encodedData) {
                return response()->json([
                    'success' => false,
                    'message' => 'No print data provided'
                ], 400);
            }

            // Decode the JSON data
            $printData = json_decode(urldecode($encodedData), true);

            if (!$printData || !is_array($printData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid print data format'
                ], 400);
            }

            // Log for debugging
            Log::info('Thermer Print Request', [
                'data_count' => count($printData),
                'first_item' => $printData[0] ?? null
            ]);

            // Return the print data in Thermer format
            return response()->json([
                'success' => true,
                'data' => $printData,
                'message' => 'Print data ready'
            ]);

        } catch (\Exception $e) {
            Log::error('Thermer Print Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error processing print data: ' . $e->getMessage()
            ], 500);
        }
    }
}
