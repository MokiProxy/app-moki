<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Regional;
use Milon\Barcode\DNS1D;
use Illuminate\Http\Request;
use App\Imports\AssetImport;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class AssetController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        $suppliers = Supplier::all();
        $regionals = Regional::all();

        return view('components.asset', compact('categories', 'suppliers', 'regionals'));
    }

    public function datatable(Request $request)
    {
        if ($request->ajax()) {
            $assets = Asset::with(['category', 'supplier', 'regional'])->select('assets.*');

            return DataTables::eloquent($assets)
                ->addIndexColumn()
                ->addColumn('category_name', function ($row) {
                    return $row->category->name ?? '-';
                })
                ->addColumn('regional_name', function ($row) {
                    return $row->regional->name ?? '-';
                })
                ->addColumn('_status', function ($row) {
                    if ($row->status == 0) return '<span class="badge bg-success">Standby</span>';
                    if ($row->status == 1) return '<span class="badge bg-primary">Assigned</span>';
                    return '<span class="badge bg-danger">Broken</span>';
                })
                ->addColumn('_barcode', function ($row) {
                    $uid = (string) $row->uid;
                    try {
                        return '<img style="height: 30px; width: 100%; max-width: 150px;" src="data:image/png;base64,' . DNS1D::getBarcodePNG($uid, 'C128', 1, 33, array(1, 1, 1), true) . '" alt="barcode" /><br><small>'.$uid.'</small>';
                    } catch (\Exception $e) {
                        return '<span class="text-danger">Invalid Format</span>';
                    }
                })
                ->addColumn('action', function ($row) {
                    return '<ul class="list-unstyled hstack gap-1 mb-0">
                                <li><button class="btn btn-sm btn-soft-primary btn-view" data-id="' . $row->id . '"><i class="mdi mdi-eye-outline"></i></button></li>
                                <li><button class="btn btn-sm btn-soft-info btn-edit" data-id="' . $row->id . '"><i class="mdi mdi-pencil-outline"></i></button></li>
                                <li><button data-id="' . $row->id . '" class="btn btn-sm btn-soft-danger btn-delete"><i class="mdi mdi-delete-outline"></i></button></li>
                            </ul>';
                })
                ->editColumn('purchase_price', function($row){
                    return number_format($row->purchase_price, 0, ',', '.');
                })
                // Menampilkan Data Center & COA Code di Datatables
                ->addColumn('cost_center', function($row){
                    return $row->cost_center ?? '-';
                })
                ->addColumn('coa_code', function($row){
                    return $row->coa_code ?? '-';
                })
                ->rawColumns(['action', '_status', '_barcode']) 
                ->make(true);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id'    => 'required',
            'supplier_id'    => 'required',
            'regional_id'    => 'required',
            'brand'          => 'required|string|max:255',
            'serial_number'  => 'nullable|string|max:255',
            'specification'  => 'required',
            'purchase_date'  => 'required|date',
            'purchase_price' => 'required|numeric',
            'condition'      => 'required',
            'cost_center'    => 'nullable|string|max:255', 
            'coa_code'       => 'nullable|string|max:255', // Validasi COA Code
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal!',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $categoryId = $request->category_id;
            if (!is_numeric($categoryId)) {
                $cat = Category::firstOrCreate(['name' => $categoryId]);
                $categoryId = $cat->id;
            }

            $supplierId = $request->supplier_id;
            if (!is_numeric($supplierId)) {
                $sup = Supplier::firstOrCreate(['name' => $supplierId]);
                $supplierId = $sup->id;
            }

            $existingAsset = $request->id ? Asset::find($request->id) : null;
            
            $uid = $existingAsset ? $existingAsset->uid : 'AST-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
            $status = $existingAsset ? $existingAsset->status : 0;

            $asset = Asset::updateOrCreate(
                ['id' => $request->id],
                [
                    'category_id'     => $categoryId,
                    'supplier_id'     => $supplierId,
                    'regional_id'     => $request->regional_id,
                    'brand'           => $request->brand,
                    'serial_number'   => $request->serial_number,
                    'uid'             => $uid,
                    'specification'   => $request->specification,
                    'production_year' => $request->production_year,
                    'purchase_date'   => $request->purchase_date,
                    'purchase_price'  => $request->purchase_price,
                    'condition'       => $request->condition,
                    'status'          => $status,
                    'cost_center'     => $request->cost_center, 
                    'coa_code'        => $request->coa_code, // Simpan COA Code ke Database
                ]
            );

            return response()->json([
                'success' => true,
                'message' => $request->id ? 'Asset berhasil diperbarui' : 'Asset berhasil ditambahkan',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $asset = Asset::with(['category', 'regional', 'supplier'])->find($id);
        
        if (!$asset) {
            return response()->json(['success' => false, 'message' => 'Asset tidak ditemukan'], 404);
        }

        return response()->json([
            'success'         => true,
            'id'              => $asset->id,
            'uid'             => $asset->uid,
            'brand'           => $asset->brand ?? '-',
            'serial_number'   => $asset->serial_number ?? '-',
            'category_id'     => $asset->category_id,
            'category_name'   => $asset->category->name ?? '-',
            'supplier_id'     => $asset->supplier_id,
            'supplier_name'   => $asset->supplier->name ?? '-',
            'regional_id'     => $asset->regional_id,
            'regional_name'   => $asset->regional->name ?? '-',
            'specification'   => $asset->specification,
            'production_year' => $asset->production_year,
            'purchase_date'   => $asset->purchase_date,
            'purchase_price'  => $asset->purchase_price,
            'condition'       => $asset->condition,
            'status'          => $asset->status,
            'cost_center'     => $asset->cost_center ?? '-', 
            'coa_code'        => $asset->coa_code ?? '-', // Tampilkan COA Code saat edit/view
        ]);
    }

    public function destroy($id)
    {
        try {
            $asset = Asset::findOrFail($id);
            $asset->delete();
            return response()->json(['success' => true, 'message' => 'Asset berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus: ' . $e->getMessage()], 500);
        }
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);
        try {
            Excel::import(new AssetImport, $request->file('file'));
            return response()->json(['success' => true, 'message' => 'Data asset berhasil diimport!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal import: ' . $e->getMessage()], 500);
        }
    }

    public function downloadTemplate()
    {
        $directory = public_path('templates');
        $filename = 'template_asset.csv';
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (!file_exists($path)) {
            $columns = [
                'category_id', 'supplier_id', 'regional_id', 'brand', 
                'serial_number', 'specification', 'production_year', 
                'purchase_price', 'purchase_date', 'condition', 'cost_center', 'coa_code'
            ];
            
            $file = fopen($path, 'w');
            fputcsv($file, $columns);
            fputcsv($file, ['1', '1', '1', 'HP', 'SN123', 'Spek', '2025', '20000000', '2025-02-27', '1', '8086', 'COA-999']);
            fclose($file);
        }

        return response()->download($path, 'Template_Asset_Import.csv');
    }
}