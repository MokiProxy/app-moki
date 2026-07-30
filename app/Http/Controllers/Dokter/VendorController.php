<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
use App\Models\DocumentType;
use App\Models\Vendor;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VendorController extends Controller
{
    public function index()
    {
        $pageName = 'Vendor';
        $vendors = Vendor::with('documentTypes')->paginate(10);

        return view('dokter.vendor.index', compact('pageName', 'vendors'));
    }

    public function create()
    {
        $pageName = 'Buat Vendor';
        $documentTypes = DocumentType::all();

        return view('dokter.vendor.create', compact('pageName', 'documentTypes'));
    }

    public function store(StoreVendorRequest $request)
    {
        try {
            $vendor = Vendor::create($request->validated());
            $vendor->documentTypes()->attach($request->document_type_ids);

            foreach ($vendor->documentTypes as $documentType) {
                Storage::disk('ftp_final')->makeDirectory("{$documentType->name}/{$vendor->name}");
            }

            return redirect()->route('dokter.vendors.index')->with('success', 'Vendor baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('dokter.vendors.index')->with('error', $err->getMessage());
        }
    }

    public function edit(Vendor $vendor)
    {
        $pageName = 'Edit Vendor';
        $documentTypes = DocumentType::all();
        $vendor->load('documentTypes');

        return view('dokter.vendor.edit', compact('pageName', 'vendor', 'documentTypes'));
    }

    public function update(UpdateVendorRequest $request, Vendor $vendor)
    {
        try {
            $oldVendorName = $vendor->name;
            $oldDocTypeIds = $vendor->documentTypes->pluck('id')->toArray();

            $vendor->update($request->validated());
            $vendor->documentTypes()->sync($request->document_type_ids);

            $newDocTypeIds = $request->document_type_ids;
            $addedIds = array_diff($newDocTypeIds, $oldDocTypeIds);
            $removedIds = array_diff($oldDocTypeIds, $newDocTypeIds);

            foreach ($removedIds as $id) {
                $dt = DocumentType::find($id);
                if ($dt) {
                    Storage::disk('ftp_final')->deleteDirectory("{$dt->name}/{$oldVendorName}");
                }
            }

            foreach ($addedIds as $id) {
                $dt = DocumentType::find($id);
                if ($dt) {
                    Storage::disk('ftp_final')->makeDirectory("{$dt->name}/{$vendor->name}");
                }
            }

            if ($oldVendorName !== $vendor->name) {
                $keptIds = array_intersect($oldDocTypeIds, $newDocTypeIds);
                foreach ($keptIds as $id) {
                    $dt = DocumentType::find($id);
                    if ($dt) {
                        Storage::disk('ftp_final')->move("{$dt->name}/{$oldVendorName}", "{$dt->name}/{$vendor->name}");
                    }
                }
            }

            return redirect()->route('dokter.vendors.index')->with('success', 'Vendor berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('dokter.vendors.index')->with('error', $err->getMessage());
        }
    }

    public function destroy(Vendor $vendor)
    {
        try {
            foreach ($vendor->documentTypes as $documentType) {
                Storage::disk('ftp_final')->deleteDirectory("{$documentType->name}/{$vendor->name}");
            }

            $vendor->delete();

            return redirect()->route('dokter.vendors.index')->with('success', 'Vendor berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('dokter.vendors.index')->with('error', $err->getMessage());
        }
    }
}
