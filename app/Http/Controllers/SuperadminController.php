<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ConsumerialTool;
use App\Models\Permohonan;
use App\Models\Transaction_Proofs;
use App\Models\Approved_Request;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SuperadminController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware(['auth', 'role:superadmin']);
    // }

    // ==================== DASHBOARD ====================

    public function index()
    {
        $title = "Superadmin Dashboard";
        return view('superadmin.index', compact('title'));
    }

    public function getDashboardStats()
    {
        try {
            $stats = [
                'totalExpense' => Permohonan::whereIn('status', ['completed', 'approved'])
                    ->sum('bill_amounts'),
                'totalRequests' => Permohonan::count(),
                'completedRequests' => Permohonan::where('status', 'completed')->count(),

                // Gasoline Distribution
                'gasolineDistribution' => Permohonan::whereIn('status', ['completed', 'approved'])
                    ->selectRaw('gasoline_type, SUM(bill_amounts) as total')
                    ->groupBy('gasoline_type')
                    ->pluck('total', 'gasoline_type')
                    ->toArray(),

                // Daily Expense (last 30 days)
                'dailyExpense' => Permohonan::whereIn('status', ['completed', 'approved'])
                    ->where('request_date', '>=', Carbon::now()->subDays(30))
                    ->selectRaw('DATE(request_date) as date, SUM(bill_amounts) as total')
                    ->groupBy('date')
                    ->orderBy('date', 'asc')
                    ->pluck('total', 'date')
                    ->toArray(),

                // Vehicle Expense (top 10)
                'vehicleExpense' => Permohonan::with('consumerialTool')
                    ->whereIn('status', ['completed', 'approved'])
                    ->selectRaw('consumerial_tools_id, SUM(bill_amounts) as total')
                    ->groupBy('consumerial_tools_id')
                    ->orderBy('total', 'desc')
                    ->limit(10)
                    ->get()
                    ->mapWithKeys(function ($item) {
                        $vehicle = $item->consumerialTool;
                        $vehicleName = $vehicle
                            ? "{$vehicle->plat_nomer} - {$vehicle->merk}"
                            : 'Unknown';
                        return [$vehicleName => $item->total];
                    })
                    ->toArray(),

                // Driver Expense (top 10)
                'driverExpense' => Permohonan::with('requester')
                    ->whereIn('status', ['completed', 'approved'])
                    ->selectRaw('requester_id, SUM(bill_amounts) as total')
                    ->groupBy('requester_id')
                    ->orderBy('total', 'desc')
                    ->limit(10)
                    ->get()
                    ->mapWithKeys(function ($item) {
                        $driverName = $item->requester->fullname ?? 'Unknown';
                        return [$driverName => $item->total];
                    })
                    ->toArray(),

                // Driver Request Count
                'driverRequestCount' => Permohonan::with('requester')
                    ->selectRaw('requester_id, COUNT(*) as count')
                    ->groupBy('requester_id')
                    ->orderBy('count', 'desc')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        $driverName = $item->requester->fullname ?? 'Unknown';
                        return [$driverName => $item->count];
                    })
                    ->toArray(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat dashboard: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDetailedDriverStats()
    {
        try {
            $requests = Permohonan::with(['requester', 'transactionProof'])
                ->get();

            $driverStats = [];

            foreach ($requests as $req) {
                $driverName = $req->requester->fullname ?? 'Unknown';

                if (!isset($driverStats[$driverName])) {
                    $driverStats[$driverName] = [
                        'total' => 0,
                        'rejected' => 0,
                        'completed' => 0,
                        'approved' => 0,
                        'pending' => 0,
                        'nominals' => [],
                    ];
                }

                $driverStats[$driverName]['total']++;
                $driverStats[$driverName][$req->status]++;

                if ($req->bill_amounts) {
                    $driverStats[$driverName]['nominals'][] = $req->bill_amounts;
                }
            }

            $result = [];
            foreach ($driverStats as $driver => $stats) {
                $reportRate = $stats['total'] > 0
                    ? round(($stats['completed'] / $stats['total']) * 100, 1)
                    : 0;

                $avgNominal = !empty($stats['nominals'])
                    ? array_sum($stats['nominals']) / count($stats['nominals'])
                    : 0;

                $result[] = [
                    'driver' => $driver,
                    'total' => $stats['total'],
                    'rejected' => $stats['rejected'],
                    'completed' => $stats['completed'],
                    'approved' => $stats['approved'],
                    'pending' => $stats['pending'],
                    'reportRate' => $reportRate,
                    'avgNominal' => $avgNominal,
                    'maxNominal' => !empty($stats['nominals']) ? max($stats['nominals']) : 0,
                    'minNominal' => !empty($stats['nominals']) ? min($stats['nominals']) : 0,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat statistik driver: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== USER MANAGEMENT ====================

    public function getAllUsers()
    {
        try {
            $users = User::select('id', 'username', 'fullname', 'role', 'email', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data user: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|unique:users,username|max:255',
            'password' => 'required|min:6',
            'fullname' => 'required|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:driver,admin,super admin',
        ]);

        try {
            $user = User::create([
                'username' => $validated['username'],
                'password' => Crypt::make($validated['password']),
                'fullname' => $validated['fullname'],
                'email' => $validated['email'],
                'role' => $validated['role'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User berhasil ditambahkan',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambah user: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'username' => 'required|max:255|unique:users,username,' . $id,
            'fullname' => 'required|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|in:driver,admin,super admin',
        ]);

        try {
            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'User berhasil diupdate',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal update user: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);

            // Check if user has related data
            if ($user->permohonans()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak dapat dihapus karena memiliki data permohonan'
                ], 400);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal hapus user: ' . $e->getMessage()
            ], 500);
        }
    }

    public function resetPassword(Request $request, $id)
    {
        $validated = $request->validate([
            'new_password' => 'required|min:6',
        ]);

        try {
            $user = User::findOrFail($id);
            $user->password = Hash::make($validated['new_password']);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password berhasil direset'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal reset password: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== VEHICLE MANAGEMENT ====================

    public function getAllVehicles()
    {
        try {
            $vehicles = ConsumerialTool::orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $vehicles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data kendaraan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeVehicle(Request $request)
    {
        $validated = $request->validate([
            'plat_nomer' => 'required|unique:consumerial_tools,plat_nomer|max:255',
            'merk' => 'required|max:255',
            'jenis' => 'required|in:Kendaraan,Alat',
        ]);

        try {
            $vehicle = ConsumerialTool::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Kendaraan berhasil ditambahkan',
                'data' => $vehicle
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambah kendaraan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateVehicle(Request $request, $id)
    {
        $vehicle = ConsumerialTool::findOrFail($id);

        $validated = $request->validate([
            'plat_nomer' => 'required|max:255|unique:consumerial_tools,plat_nomer,' . $id,
            'merk' => 'required|max:255',
            'jenis' => 'required|in:Kendaraan,Alat',
        ]);

        try {
            $vehicle->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Kendaraan berhasil diupdate',
                'data' => $vehicle
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal update kendaraan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteVehicle($id)
    {
        try {
            $vehicle = ConsumerialTool::findOrFail($id);

            // Check if vehicle has related data
            if ($vehicle->permohonans()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kendaraan tidak dapat dihapus karena memiliki data permohonan'
                ], 400);
            }

            $vehicle->delete();

            return response()->json([
                'success' => true,
                'message' => 'Kendaraan berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal hapus kendaraan: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== REQUEST MANAGEMENT ====================

    public function getAllRequests(Request $request)
    {
        try {
            $query = Permohonan::with(['requester', 'authorizer', 'consumerialTool', 'transactionProof']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('start_date')) {
                $query->whereDate('request_date', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('request_date', '<=', $request->end_date);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('request_number', 'like', "%{$search}%")
                      ->orWhereHas('requester', function($q2) use ($search) {
                          $q2->where('fullname', 'like', "%{$search}%");
                      });
                });
            }

            $requests = $query->orderBy('request_date', 'desc')->get();

            // Format data
            $formattedRequests = $requests->map(function ($req) {
                $vehicle = $req->consumerialTool;
                $vehicleDisplay = $vehicle
                    ? "{$vehicle->plat_nomer} - {$vehicle->merk}"
                    : ($req->consumerial_tools_id ?? '');

                return [
                    'requestId' => $req->id,
                    'request_number' => $req->request_number,
                    'driverName' => $req->requester->fullname ?? 'Unknown',
                    'vehicleDisplay' => $vehicleDisplay,
                    'vehiclePlatNumber' => $vehicle->plat_nomer ?? '',
                    'vehicleMerk' => $vehicle->merk ?? '',
                    'vehicleJenis' => $vehicle->jenis ?? '',
                    'gasolineType' => $req->gasoline_type,
                    'billPayment' => $req->bill_amounts,
                    'requestAt' => $req->request_date->toISOString(),
                    'status' => $req->status,
                    'authorizerName' => $req->authorizer->fullname ?? '',
                    'authorizerComment' => $req->authorizer_notes ?? '',
                    'driverNote' => $req->requester_notes ?? '',
                    'hasProof' => $req->transactionProof ? true : false,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedRequests
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data permohonan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateRequest(Request $request, $id)
    {
        $permohonan = Permohonan::findOrFail($id);

        $validated = $request->validate([
            'consumerial_tools_id' => 'nullable|exists:consumerial_tools,id',
            'gasoline_type' => 'nullable|string',
            'bill_amounts' => 'nullable|numeric',
            'status' => 'nullable|in:pending,approved,rejected,completed',
            'authorizer_notes' => 'nullable|string',
            'requester_notes' => 'nullable|string',
        ]);

        try {
            $permohonan->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Permohonan berhasil diupdate',
                'data' => $permohonan
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal update permohonan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteRequest($id)
    {
        try {
            $permohonan = Permohonan::findOrFail($id);

            // Delete related transaction proof if exists
            if ($permohonan->transactionProof) {
                // Delete files
                if ($permohonan->transactionProof->struk_bbm_path) {
                    Storage::disk('public')->delete($permohonan->transactionProof->struk_bbm_path);
                }
                if ($permohonan->transactionProof->odometer_photo_path) {
                    Storage::disk('public')->delete($permohonan->transactionProof->odometer_photo_path);
                }

                $permohonan->transactionProof->delete();
            }

            // Delete approved request if exists
            if ($permohonan->approvedRequest) {
                $permohonan->approvedRequest->delete();
            }

            $permohonan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Permohonan berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal hapus permohonan: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== TRANSACTION PROOF MANAGEMENT ====================

    public function getAllTransactionProofs()
    {
        try {
            $proofs = Transaction_Proofs::with(['permohonan', 'requester'])
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedProofs = $proofs->map(function ($proof) {
                return [
                    'proofId' => $proof->id,
                    'transactionId' => $proof->transaction_id,
                    'requestNumber' => $proof->permohonan->request_number ?? '',
                    'voucherCode' => $proof->voucher_number,
                    'driverName' => $proof->requester->fullname ?? 'Unknown',
                    'kmTerakhir' => $proof->km_terakhir,
                    'fotoKmTerakhir' => $proof->odometer_photo_path
                        ? asset('storage/' . $proof->odometer_photo_path)
                        : '',
                    'fotoStrukBbm' => $proof->struk_bbm_path
                        ? asset('storage/' . $proof->struk_bbm_path)
                        : '',
                    'tanggalPengisian' => $proof->purchase_datetime->toISOString(),
                    'volumePengisian' => $proof->fuel_volume,
                    'createdAt' => $proof->created_at->toISOString(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedProofs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data laporan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateTransactionProof(Request $request, $id)
    {
        $proof = Transaction_Proofs::findOrFail($id);

        $validated = $request->validate([
            'km_terakhir' => 'nullable|numeric',
            'purchase_datetime' => 'nullable|date',
            'fuel_volume' => 'nullable|numeric',
            'foto_km' => 'nullable|image|max:5120', // 5MB
            'foto_struk' => 'nullable|image|max:5120',
        ]);

        try {
            DB::beginTransaction();

            // Update basic fields
            if (isset($validated['km_terakhir'])) {
                $proof->km_terakhir = $validated['km_terakhir'];
            }
            if (isset($validated['purchase_datetime'])) {
                $proof->purchase_datetime = $validated['purchase_datetime'];
            }
            if (isset($validated['fuel_volume'])) {
                $proof->fuel_volume = $validated['fuel_volume'];
            }

            // Handle foto KM
            if ($request->hasFile('foto_km')) {
                // Delete old file
                if ($proof->odometer_photo_path) {
                    Storage::disk('public')->delete($proof->odometer_photo_path);
                }

                $path = $request->file('foto_km')->store('transaction_proofs/odometer', 'public');
                $proof->odometer_photo_path = $path;
            }

            // Handle foto struk
            if ($request->hasFile('foto_struk')) {
                // Delete old file
                if ($proof->struk_bbm_path) {
                    Storage::disk('public')->delete($proof->struk_bbm_path);
                }

                $path = $request->file('foto_struk')->store('transaction_proofs/struk', 'public');
                $proof->struk_bbm_path = $path;
            }

            $proof->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil diupdate',
                'data' => $proof
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal update laporan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteTransactionProof($id)
    {
        try {
            $proof = Transaction_Proofs::findOrFail($id);

            // Delete files from storage
            if ($proof->struk_bbm_path) {
                Storage::disk('public')->delete($proof->struk_bbm_path);
            }
            if ($proof->odometer_photo_path) {
                Storage::disk('public')->delete($proof->odometer_photo_path);
            }

            $proof->delete();

            return response()->json([
                'success' => true,
                'message' => 'Laporan berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal hapus laporan: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== ADVANCED ANALYTICS ====================

    public function getVehicleFuelConsumption(Request $request)
    {
        try {
            $query = Transaction_Proofs::with(['permohonan.consumerialTool', 'permohonan.requester'])
                ->whereHas('permohonan', function($q) {
                    $q->where('status', 'completed');
                });

            // Apply filters
            if ($request->has('start_date')) {
                $query->whereDate('purchase_datetime', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('purchase_datetime', '<=', $request->end_date);
            }

            if ($request->has('min_volume')) {
                $query->where('fuel_volume', '>=', $request->min_volume);
            }

            if ($request->has('max_volume')) {
                $query->where('fuel_volume', '<=', $request->max_volume);
            }

            if ($request->has('min_nominal')) {
                $query->whereHas('permohonan', function($q) use ($request) {
                    $q->where('bill_amounts', '>=', $request->min_nominal);
                });
            }

            $proofs = $query->orderBy('purchase_datetime', 'desc')->get();

            $result = $proofs->map(function ($proof) {
                $permohonan = $proof->permohonan;
                $vehicle = $permohonan->consumerialTool;

                return [
                    'requestNumber' => $permohonan->request_number,
                    'vehicleId' => $vehicle->id ?? '',
                    'vehicleDisplay' => $vehicle
                        ? "{$vehicle->plat_nomer} - {$vehicle->merk}"
                        : '',
                    'platNumber' => $vehicle->plat_nomer ?? '',
                    'merk' => $vehicle->merk ?? '',
                    'jenis' => $vehicle->jenis ?? '',
                    'driverName' => $permohonan->requester->fullname ?? 'Unknown',
                    'gasolineType' => $permohonan->gasoline_type,
                    'volume' => $proof->fuel_volume,
                    'nominal' => $permohonan->bill_amounts,
                    'tanggalPengisian' => $proof->purchase_datetime->toISOString(),
                    'requestAt' => $permohonan->request_date->toISOString(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat konsumsi BBM: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getVehicleKmPerformance(Request $request)
    {
        try {
            $query = Transaction_Proofs::with(['permohonan.consumerialTool'])
                ->whereHas('permohonan.consumerialTool', function($q) {
                    $q->where('jenis', 'Kendaraan'); // Only vehicles, not tools
                })
                ->where('km_terakhir', '>', 0)
                ->orderBy('purchase_datetime', 'asc');

            // Apply date filters
            if ($request->has('start_date')) {
                $query->whereDate('purchase_datetime', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('purchase_datetime', '<=', $request->end_date);
            }

            $proofs = $query->get();

            // Group by vehicle
            $vehicleKmData = [];

            foreach ($proofs as $proof) {
                $vehicleId = $proof->permohonan->consumerial_tools_id;

                if (!isset($vehicleKmData[$vehicleId])) {
                    $vehicleKmData[$vehicleId] = [];
                }

                $vehicleKmData[$vehicleId][] = [
                    'km' => $proof->km_terakhir,
                    'date' => $proof->purchase_datetime,
                ];
            }

            // Calculate KM differences
            $result = [];

            foreach ($vehicleKmData as $vehicleId => $records) {
                if (count($records) < 2) continue; // Need at least 2 records

                // Sort by date
                usort($records, function($a, $b) {
                    return $a['date']->timestamp - $b['date']->timestamp;
                });

                $kmDifferences = [];

                for ($i = 1; $i < count($records); $i++) {
                    $diff = $records[$i]['km'] - $records[$i-1]['km'];

                    // Sanity check: positive difference and less than 10k km
                    if ($diff > 0 && $diff < 10000) {
                        $kmDifferences[] = $diff;
                    }
                }

                if (empty($kmDifferences)) continue;

                $vehicle = ConsumerialTool::find($vehicleId);
                if (!$vehicle) continue;

                $avgKmDiff = array_sum($kmDifferences) / count($kmDifferences);

                $result[] = [
                    'vehicleId' => $vehicleId,
                    'vehicleDisplay' => "{$vehicle->plat_nomer} - {$vehicle->merk}",
                    'platNumber' => $vehicle->plat_nomer,
                    'merk' => $vehicle->merk,
                    'avgKmDiff' => round($avgKmDiff),
                    'totalTrips' => count($kmDifferences),
                    'maxKm' => max($kmDifferences),
                    'minKm' => min($kmDifferences),
                    'lastUpdate' => $records[count($records) - 1]['date']->toISOString(),
                    'latestOdo' => $records[count($records) - 1]['km'],
                ];
            }

            // Sort by avgKmDiff descending
            usort($result, function($a, $b) {
                return $b['avgKmDiff'] - $a['avgKmDiff'];
            });

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat performa KM: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getCompletePivotTable(Request $request)
    {
        try {
            $query = Permohonan::with([
                'requester',
                'authorizer',
                'consumerialTool',
                'transactionProof',
                'approvedRequest'
            ]);

            // Apply status filter (default: approved and completed)
            if ($request->has('status_filter')) {
                $statuses = $request->status_filter;
                if (is_string($statuses)) {
                    $statuses = explode(',', $statuses);
                }
                $query->whereIn('status', $statuses);
            } else {
                $query->whereIn('status', ['approved', 'completed']);
            }

            // Apply date filters
            if ($request->has('start_date')) {
                $query->whereDate('request_date', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('request_date', '<=', $request->end_date);
            }

            // Apply search filter
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('request_number', 'like', "%{$search}%")
                      ->orWhereHas('requester', function($q2) use ($search) {
                          $q2->where('fullname', 'like', "%{$search}%");
                      })
                      ->orWhereHas('consumerialTool', function($q2) use ($search) {
                          $q2->where('plat_nomer', 'like', "%{$search}%");
                      })
                      ->orWhereHas('transactionProof', function($q2) use ($search) {
                          $q2->where('transaction_id', 'like', "%{$search}%");
                      });
                });
            }

            $permohonans = $query->orderBy('request_date', 'desc')->get();

            $result = $permohonans->map(function ($permohonan) {
                $vehicle = $permohonan->consumerialTool;
                $proof = $permohonan->transactionProof;
                $approved = $permohonan->approvedRequest;

                return [
                    'requestId' => $permohonan->id,
                    'requestNumber' => $permohonan->request_number,
                    'driverName' => $permohonan->requester->fullname ?? 'Unknown',
                    'vehicleDisplay' => $vehicle
                        ? "{$vehicle->plat_nomer} - {$vehicle->merk}"
                        : '',
                    'platNumber' => $vehicle->plat_nomer ?? '',
                    'merk' => $vehicle->merk ?? '',
                    'jenis' => $vehicle->jenis ?? '',
                    'requestAt' => $permohonan->request_date->toISOString(),
                    'nominal' => $permohonan->bill_amounts,
                    'gasolineType' => $permohonan->gasoline_type,
                    'status' => $permohonan->status,
                    'voucherCode' => $approved->voucher_code ?? '',
                    'proofId' => $proof->transaction_id ?? '',
                    'tanggalPengisian' => $proof
                        ? $proof->purchase_datetime->toISOString()
                        : '',
                    'volumePengisian' => $proof->fuel_volume ?? 0,
                    'kmTerakhir' => $proof->km_terakhir ?? 0,
                    'tanggalLaporan' => $proof
                        ? $proof->created_at->toISOString()
                        : '',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat tabel pivot: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== ACTIVITY LOG ====================

    public function getActivityLog()
    {
        try {
            // Get recent login activities from sessions table
            // Alternative: buat table login_logs sendiri untuk tracking lebih detail

            $activities = DB::table('sessions')
                ->join('users', 'sessions.user_id', '=', 'users.id')
                ->select(
                    'users.id as user_id',
                    'users.username',
                    'users.fullname',
                    'users.role',
                    'sessions.last_activity',
                    'sessions.ip_address',
                    'sessions.user_agent'
                )
                ->whereNotNull('sessions.user_id')
                ->orderBy('sessions.last_activity', 'desc')
                ->limit(100)
                ->get();

            $formattedActivities = $activities->map(function ($activity) {
                return [
                    'userId' => $activity->user_id,
                    'username' => $activity->username,
                    'fullname' => $activity->fullname,
                    'role' => $activity->role,
                    'lastActivity' => Carbon::createFromTimestamp($activity->last_activity)->toISOString(),
                    'ipAddress' => $activity->ip_address,
                    'userAgent' => $activity->user_agent,
                    'isActive' => (time() - $activity->last_activity) < 3600, // Active jika < 1 jam
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedActivities
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat log aktivitas: ' . $e->getMessage()
            ], 500);
        }
    }


}
