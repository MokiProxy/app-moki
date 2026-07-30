<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentTypeRequest;
use App\Http\Requests\UpdateDocumentTypeRequest;
use App\Models\DocumentType;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentTypeController extends Controller
{
    public function index()
    {
        $pageName = 'Jenis Dokumen';
        $documentTypes = DocumentType::paginate(10);

        return view('dokter.document-type.index', compact('pageName', 'documentTypes'));
    }

    public function create()
    {
        $pageName = 'Buat Jenis Dokumen';

        return view('dokter.document-type.create', compact('pageName'));
    }

    public function store(StoreDocumentTypeRequest $request)
    {
        try {
            $documentType = DocumentType::create($request->validated());
            Storage::disk('ftp_final')->makeDirectory("{$documentType->name}");
            $failedFolder = $documentType->attributes['ftp_failed_folder'] ?? 'FAILED';
            Storage::disk('ftp_final')->makeDirectory("{$failedFolder}");

            return redirect()->route('dokter.document-types.index')->with('success', 'Jenis dokumen baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('dokter.document-types.index')->with('error', $err->getMessage());
        }
    }

    public function edit(DocumentType $documentType)
    {
        $pageName = 'Edit Jenis Dokumen';

        return view('dokter.document-type.edit', compact('pageName', 'documentType'));
    }

    public function update(UpdateDocumentTypeRequest $request, DocumentType $documentType)
    {
        try {
            $oldPath = $documentType->name;
            $newPath = $request->name;

            if ($oldPath !== $newPath) {
                Storage::disk('ftp_final')->move($oldPath, $newPath);
            }

            $documentType->update($request->validated());

            return redirect()->route('dokter.document-types.index')->with('success', 'Jenis dokumen berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('dokter.document-types.index')->with('error', $err->getMessage());
        }
    }

    public function destroy(DocumentType $documentType)
    {
        try {
            Storage::disk('ftp_final')->deleteDirectory("{$documentType->name}");
            $documentType->delete();

            return redirect()->route('dokter.document-types.index')->with('success', 'Jenis dokumen berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('dokter.document-types.index')->with('error', $err->getMessage());
        }
    }
}
