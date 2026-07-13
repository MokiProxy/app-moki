<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Company;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class MonitorController extends Controller
{
    public function asset()
    {
        return view('components.monitor-asset');
    }

    public function assetDatatable(Request $request)
    {
        if ($request->ajax()) {
            // Eager loading sangat penting agar query tidak berat
            $details = Asset::with(['category', 'transaction_detail.transaction.employee']);

            return DataTables::eloquent($details)
                ->addIndexColumn()
                ->addColumn('nomor_asset_bast', function ($row) {
                    // Berdasarkan screenshot Anda, ini harus menampilkan UID Asset jika transaksi kosong
                    $lastTransDetail = $row->transaction_detail->sortByDesc('id')->first();
                    
                    if ($lastTransDetail && $lastTransDetail->transaction) {
                        return $lastTransDetail->transaction->order_number ?? $lastTransDetail->transaction->nomor ?? $row->uid;
                    }
                    return $row->uid ?? '-'; // Kembalikan UID jika belum ada riwayat transaksi
                })
                ->addColumn('last_employee_id', function ($row) {
                    $last = $row->transaction_detail->sortByDesc('id')->first();
                    return $last->transaction->employee->employee_id ?? '-';
                })
                ->addColumn('last_employee_name', function ($row) {
                    $last = $row->transaction_detail->sortByDesc('id')->first();
                    return $last->transaction->employee->name ?? '-';
                })
                ->editColumn('_status', function ($row) {
                    return $row->status == 0 
                        ? '<span class="badge bg-success">Standby</span>' 
                        : '<span class="badge bg-danger">Not Standby</span>';
                })
                ->addColumn('action', function ($row) {
                    // Pastikan data-nomor_asset_bast menggunakan UID agar header modal terisi
                    return '<ul class="list-unstyled hstack gap-1 mb-0 justify-content-center">
                                <li>
                                    <a href="#" class="btn btn-sm btn-soft-primary btn-history" 
                                        data-id="' . $row->id . '" 
                                        data-uid="' . ($row->uid ?? '-') . '" 
                                        data-category="' . ($row->category->name ?? '-') . '" 
                                        data-specification="' . ($row->specification ?? '-') . '" 
                                        data-status="' . $row->status . '">
                                        <i class="mdi mdi-history fs-6"></i> History
                                    </a>
                                </li>
                            </ul>';
                })
                ->rawColumns(['action', '_status'])
                ->make(true);
        }
    }

    public function assetTransaction($id)
    {
        try {
            // Mengambil history dan memetakan datanya agar rata (flat) sesuai kebutuhan JavaScript
            $history = TransactionDetail::with(['transaction.employee', 'transaction.division'])
                ->where('asset_id', $id)
                ->get()
                ->sortByDesc('id');

            // Format data agar sesuai dengan loop $.each(response.data, ...) di Blade
            $mappedData = $history->map(function($item) {
                return [
                    'history_emp_id'   => $item->transaction->employee->employee_id ?? '-',
                    'history_emp_name' => $item->transaction->employee->name ?? '-',
                    'history_division' => $item->transaction->division->name ?? '-',
                    'history_type'     => $item->transaction->type ?? '-',
                    'history_date'     => $item->transaction->created_at ? $item->transaction->created_at->format('d M Y') : '-',
                    'history_note'     => $item->transaction->note ?? '-'
                ];
            })->values();
                
            return response()->json([
                'success' => true,
                'data'    => $mappedData
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}