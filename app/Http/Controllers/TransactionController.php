<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Employee;
use App\Models\Division;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Whatsapp; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables; 
use Barryvdh\DomPDF\Facade\Pdf;
use GuzzleHttp\Client;
use Exception;

class TransactionController extends Controller
{
    public function index() 
    { 
        return view('components.transaction'); 
    }

    public function datatable(Request $request)
    {
        try {
            $data = Transaction::with(['employee.regional', 'division', 'details.asset.category'])
                ->select('transactions.*')
                ->orderBy('created_at', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('transaction_number', function($row) {
                    return $row->order_number ?? '-';
                })
                ->addColumn('employee_id_display', function($row) {
                    return optional($row->employee)->employee_id ?? '-';
                })
                ->addColumn('employee_name', function($row) {
                    return optional($row->employee)->name ?? '-';
                })
                ->addColumn('jabatan', function($row) {
                    return optional($row->employee)->jabatan ?? '-';
                })
                ->addColumn('division_name', function($row) {
                    return optional($row->division)->name ?? '-';
                })
                ->addColumn('regional_name', function($row) {
                    return optional(optional($row->employee)->regional)->name ?? '-';
                })
                ->addColumn('status_type', function($row) {
                    return $row->type ?? '-'; 
                })
                ->addColumn('status_approved', function($row) {
                    if ($row->status == 2) {
                        return '<span class="badge bg-success"><i class="mdi mdi-check-circle me-1"></i>Approved</span>';
                    } elseif ($row->status == 3) {
                        return '<span class="badge bg-danger"><i class="mdi mdi-close-circle me-1"></i>Ditolak</span>';
                    }
                    return '<span class="badge bg-warning text-dark"><i class="mdi mdi-clock me-1"></i>Pending</span>';
                })
                ->addColumn('category', function($row) {
                    $categories = $row->details->map(function($detail) {
                        return optional(optional($detail->asset)->category)->name;
                    })->filter()->unique();
                    return $categories->isNotEmpty() ? $categories->implode(', ') : '-';
                })
                ->addColumn('_asset_count', function($row) {
                    return $row->details->count() . " Item"; 
                })
                ->addColumn('action', function($row) {
                    $btnView = '<button type="button" class="btn btn-sm btn-info btn-view me-1" data-id="'.$row->id.'" title="Detail"><i class="mdi mdi-eye"></i></button>';
                    $userRole = auth()->user()->role_id;

                    $btnStatus = '';
                    if($row->status == 1) {
                        $btnStatus = '<button type="button" class="btn btn-sm btn-outline-dark btn-approval me-1" data-id="'.$row->id.'" title="Proses Approval"><i class="mdi mdi-check-decagram"></i></button>';
                    } elseif($row->status == 2) {
                        $btnStatus = '<button type="button" class="btn btn-sm btn-soft-success me-1" disabled title="Sudah Disetujui"><i class="mdi mdi-check-decagram text-success"></i></button>';
                    } else {
                        $btnStatus = '<button type="button" class="btn btn-sm btn-soft-danger me-1" disabled title="Sudah Ditolak"><i class="mdi mdi-check-decagram text-danger"></i></button>';
                    }

                    $btnPdf = '';
                    $btnDelete = '';

                    if ($userRole != 3) {
                        $btnPdf = '<button type="button" class="btn btn-sm btn-warning btn-pdf me-1" data-id="'.$row->id.'" title="Cetak PDF"><i class="mdi mdi-file-pdf-box"></i></button>';
                        $btnDelete = '<button type="button" class="btn btn-sm btn-danger btn-delete" data-id="'.$row->id.'" title="Hapus"><i class="mdi mdi-trash-can"></i></button>';
                    }

                    return '<div class="text-center d-flex justify-content-center">' . $btnView . $btnStatus . $btnPdf . $btnDelete . '</div>';
                })
                ->rawColumns(['action', 'status_type', 'status_approved'])
                ->make(true);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function create() 
    { 
        $employees = Employee::with(['division', 'regional'])->get(); 
        $divisions = Division::all(); 
        return view('components.create-transaction', compact('divisions', 'employees')); 
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id'    => 'required|exists:employees,id',
            'asset_id'       => 'required|array|min:1',
            'status'         => 'required|in:IN,OUT',
            'generated_uids' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 200); 
        }

        DB::beginTransaction(); 
        try {
            $bastNumber = "BAST-" . date('YmdHis');

            $transaction = Transaction::create([
                'order_number' => $bastNumber,
                'employee_id'  => $request->employee_id,
                'division_id'  => $request->division_id,
                'note'         => $request->notes, 
                'status'       => 1, 
                'type'         => $request->status 
            ]);

            foreach ($request->asset_id as $key => $assetId) {
                if (empty($assetId)) continue; 
                TransactionDetail::create([
                    'transaction_id' => $transaction->id, 
                    'asset_id'       => $assetId,
                    'new_uid'        => $request->generated_uids[$key] ?? $bastNumber 
                ]);
            }

            DB::commit();

            // --- TRIGGER NOTIFIKASI WA ---
            $this->sendWhatsappNotification($transaction);

            return response()->json(['success' => true, 'message' => 'Transaksi diajukan (Pending): ' . $bastNumber]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
    }

    private function sendWhatsappNotification($transaction)
    {
        try {
            // Gunakan backslash global agar mencari fungsi di App/Helpers
            $token = \fonnte_api();

            if (!$token) {
                \Log::error("WA Gagal: Token tidak ditemukan (key: fonnte_api)");
                return;
            }

            // Cari Manager MSI
            $manager = Employee::whereRaw('LOWER(jabatan) = ?', ['manajer msi'])->first();
            
            if (!$manager || empty($manager->hp)) {
                \Log::warning("WA Gagal: Manager MSI tidak ditemukan atau nomor HP kosong.");
                return;
            }

            // Format Nomor HP (08 -> 62)
            $target = $manager->hp;
            if (substr($target, 0, 1) === '0') {
                $target = '62' . substr($target, 1);
            }

            $type = ($transaction->type == 'OUT') ? 'Pemberian/Pinjam' : 'Pengembalian';
            $message = "*PERMINTAAN PERSETUJUAN ASET*\n"
                     . "--------------------------\n"
                     . "Yth. Pak " . $manager->name . ",\n\n"
                     . "Terdapat pengajuan *" . $type . "* baru:\n"
                     . "No. BAST: *" . $transaction->order_number . "*\n"
                     . "Karyawan: " . optional($transaction->employee)->name . "\n\n"
                     . "Mohon segera login ke sistem AMS untuk memproses persetujuan.\n"
                     . "Terima kasih.";

            $client = new Client(['verify' => false, 'timeout' => 10]);
            
            $client->post(env('FONNTE_API_URL', 'https://api.fonnte.com/send'), [
                'headers' => [
                    'Authorization' => $token,
                ],
                'form_params' => [
                    'target'  => $target,
                    'message' => $message,
                ],
            ]);

            \Log::info("WA Terkirim ke " . $target . " untuk BAST: " . $transaction->order_number);

        } catch (Exception $e) {
            \Log::error("Error WA: " . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $transaction = Transaction::with('details')->findOrFail($id);
            $newStatus = $request->status; 

            if ($newStatus == 2) { 
                foreach ($transaction->details as $detail) {
                    $asset = Asset::find($detail->asset_id);
                    if ($asset) {
                        $physicalStatus = ($transaction->type == 'OUT') ? 1 : 0;
                        $asset->update(['status' => $physicalStatus]);
                    }
                }
            }

            $transaction->update(['status' => $newStatus]);
            DB::commit();
            
            $msg = $newStatus == 2 ? 'Transaksi Berhasil Disetujui' : 'Transaksi Berhasil Ditolak';
            return response()->json(['success' => true, 'message' => $msg]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        try {
            $transaction = Transaction::with(['employee.division', 'employee.regional', 'division', 'details.asset.category'])->findOrFail($id);
            return response()->json(['success' => true, 'data' => $transaction]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $transaction = Transaction::with('details')->findOrFail($id);
            if ($transaction->status == 2) {
                foreach ($transaction->details as $detail) {
                    $asset = Asset::find($detail->asset_id);
                    if ($asset) {
                        $reversedStatus = ($transaction->type == 'OUT') ? 0 : 1;
                        $asset->update(['status' => $reversedStatus]);
                    }
                }
            }
            $transaction->details()->delete();
            $transaction->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus.']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
        }
    }

    public function exportPDF($id)
    {
        $transaction = Transaction::with(['employee.division', 'employee.regional', 'division', 'details.asset.category'])->findOrFail($id);
        $pdf = Pdf::loadView('transaction.pdf', compact('transaction'));
        return $pdf->stream('BAST-' . $transaction->order_number . '.pdf');
    }

    public function selectAsset(Request $request, $id = null)
    {
        if ($id) {
            $asset = Asset::with('category')->find($id);
            $employee = Employee::with('division')->find($request->employee_id);
            if (!$employee) return response()->json(['message' => 'Pilih karyawan'], 422);

            $generatedUid = sprintf('%03d', $asset->category_id ?? 0) . "-" . sprintf('%03d', $asset->regional_id ?? 0) . "-" . ($asset->cost_center ?? '0000') . "-" . sprintf('%03d', $employee->id ?? 0) . "-" . (optional($employee->division)->code ?? 'XXXX') . "-" . date('Y');

            return response()->json([
                'id' => $asset->id,
                'brand' => $asset->brand ?? '-',
                'category_name' => optional($asset->category)->name ?? '-',
                'serial_number' => $asset->serial_number ?? '-',
                'generated_uid' => $generatedUid,
                'coa_code' => $asset->cost_center ?? '0000',
            ]);
        }

        $search = $request->q;
        $status_type = $request->status;
        $query = Asset::query();
        $query->where('status', ($status_type == 'IN' ? 1 : 0));

        $assets = $query->when($search, function($q) use ($search) {
                return $q->where('uid', 'LIKE', "%$search%")->orWhere('brand', 'LIKE', "%$search%")->orWhere('serial_number', 'LIKE', "%$search%");
            })->limit(20)->get();

        $response = [];
        foreach ($assets as $asset) {
            $response[] = ["id" => $asset->id, "text" => ($asset->uid ?? 'No UID') . ' - ' . ($asset->brand ?? 'No Brand')];
        }
        return response()->json($response);
    }
}