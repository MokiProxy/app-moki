<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// Pastikan ketiga model ini sudah dibuat dengan perintah: php artisan make:model NamaModel
use App\Models\AssetAssignment; 
use App\Models\Asset;
use App\Models\Employee;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AssetAssignmentController extends Controller
{
  public function index()
{
    $employees = Employee::with('division')->get();
    
    // Ambil semua aset tanpa filter status
    $assets = Asset::all(); 

    $locations = DB::table('regionals')->select('name')->get(); 

    return view('pages.asset-assignment.index', compact('employees', 'assets', 'locations'));
}

   public function datatable(Request $request)
{
    $data = DB::table('asset_assignments as aa')
        ->leftJoin('assets as a', 'aa.asset_id', '=', 'a.id')
        ->leftJoin('employees as e', 'aa.employee_id', '=', 'e.id')
        ->select([
            'aa.id',
            'aa.employee_id', // ID untuk relasi
            'aa.asset_id',    // ID untuk relasi
            'aa.assignment_date',
            'aa.condition',
            'aa.location',
            'aa.document_path',
            'aa.remarks',
            'aa.asset_no',
            'e.employee_id as employee_nik', 
            'aa.employee_name', 
            'aa.job_title',
            'aa.department as department_name',
            'a.brand as asset_brand',
            'a.serial_number as asset_sn',
            // Ambil spesifikasi langsung dari tabel assets agar selalu up-to-date
            DB::raw('COALESCE(a.specification, aa.specification) as specification')
        ])
        ->orderBy('aa.id', 'desc');

    return DataTables::of($data)
        ->addIndexColumn()
        ->make(true);
}

 public function store(Request $request)
{
    // Validasi data...
    
    $data = [
        'employee_id'     => $request->employee_id,
        'asset_id'        => $request->asset_id,
        'asset_no'        => $request->asset_no,
        'assignment_date' => $request->assignment_date,
        'location'        => $request->location,
        'condition'       => $request->condition,
        'remarks'         => $request->remarks,
    ];

    // Cek jika ada file yang diupload
    if ($request->hasFile('document')) {
        $data['document_path'] = $request->file('document')->store('documents', 'public');
    }

    // LOGIKA UTAMA: Jika ID ada maka Update, jika tidak maka Create
    Assignment::updateOrCreate(
        ['id' => $request->id], // Cari berdasarkan ID ini
        $data                   // Update/Insert data ini
    );

    return response()->json(['success' => true, 'message' => 'Data berhasil disimpan!']);
}

public function update(Request $request, $id)
{
    try {
        $assignment = AssetAssignment::findOrFail($id);
        
        // 1. Ambil data Employee & Asset terbaru untuk sinkronisasi kolom tambahan
        $employee = Employee::with('division')->find($request->employee_id);
        $asset = Asset::find($request->asset_id);

        if (!$employee || !$asset) {
            return response()->json(['success' => false, 'message' => 'Data Karyawan atau Asset tidak ditemukan!'], 404);
        }

        // 2. Update data utama
        $assignment->employee_id     = $request->employee_id;
        $assignment->asset_id        = $request->asset_id;
        $assignment->asset_no        = $request->asset_no;
        $assignment->assignment_date = $request->assignment_date;
        $assignment->location        = $request->location;
        $assignment->condition       = $request->condition;
        $assignment->remarks         = $request->remarks;

        // 3. PENTING: Sinkronisasi kolom spesifikasi dan data karyawan
        // Pastikan nama kolom di DB Anda sesuai (aa.specification, aa.employee_name, dll)
        $assignment->specification   = $asset->specification; 
        $assignment->employee_name   = $employee->name;
        $assignment->job_title       = $employee->jabatan;
        $assignment->department      = $employee->division ? $employee->division->name : '-';

        // 4. Handle File
        if ($request->hasFile('document')) {
            if ($assignment->document_path) {
                Storage::disk('public')->delete($assignment->document_path);
            }
            $assignment->document_path = $request->file('document')->store('documents', 'public');
        }

        $assignment->save();

        return response()->json([
            'success' => true, 
            'message' => 'Data berhasil diperbarui!'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false, 
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ], 500);
    }
}
    public function destroy($id)
    {
        try {
            $assign = AssetAssignment::findOrFail($id);
            
            // Kembalikan status aset menjadi tersedia (status 1)
            Asset::where('id', $assign->asset_id)->update(['status' => 1]);
            
            // Hapus file fisik
            if ($assign->document_path) {
                Storage::disk('public')->delete($assign->document_path);
            }

            $assign->delete();
            return response()->json(['success' => true, 'message' => 'Data berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus: ' . $e->getMessage()], 500);
        }
    }
}