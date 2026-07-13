<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction; // Pastikan namespace Model Anda benar
use Illuminate\Support\Facades\DB;

class ApprovedController extends Controller
{
    /**
     * Menampilkan Halaman Utama Approval
     * Sinkron dengan: @forelse($pending ...) dan @foreach($history ...)
     */
    public function index()
    {
        // 1. Data Pending (Status 1)
        $pending = Transaction::with(['employee', 'division'])
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        // 2. Data Riwayat (Status 2 = Approved, Status 3 = Rejected)
        $history = Transaction::with(['employee', 'division'])
            ->whereIn('status', [2, 3])
            ->latest()
            ->limit(100)
            ->get();

        // Mengarah ke folder components sesuai struktur Anda
        return view('components.approved', compact('pending', 'history'));
    }

    /**
     * Mengambil Detail Transaksi untuk Modal
     * Sinkron dengan: $.get("{{ url('transaction/detail') }}/" + id)
     */
    public function detail($id)
    {
        try {
            $transaction = Transaction::with([
                'employee.regional', 
                'division', 
                'details.asset'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $transaction
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Memproses Approve/Reject
     * Sinkron dengan: url('settings/approve/action')
     */
    public function process(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $transaction = Transaction::findOrFail($id);
            $action = $request->action;

            if ($action == 'approve') {
                $transaction->update([
                    'status' => 2,
                    'approved_atasan_at' => now(),
                    'approved_admin_at' => now(), // Langsung set keduanya jika satu pintu
                ]);
                $message = "BAST " . $transaction->order_number . " telah disetujui.";
            } else {
                $transaction->update(['status' => 3]);
                $message = "BAST telah ditolak.";
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses: ' . $e->getMessage()
            ], 500);
        }
    }
}