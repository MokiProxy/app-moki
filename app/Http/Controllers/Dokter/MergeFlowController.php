<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use App\Models\DocumentMergeGroup;
use App\Models\DocumentType;
use App\Models\MergeFlow;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MergeFlowController extends Controller
{
    public function index()
    {
        $pageName = 'Alur Birokrasi';
        $flows = MergeFlow::with('steps.documentType')->get();
        $pendingGroups = DocumentMergeGroup::where('status', 0)
            ->with(['mergeFlow', 'items.documentType'])
            ->latest()
            ->paginate(20)
            ->withQueryString();
        $completeGroups = DocumentMergeGroup::where('status', 1)
            ->with(['mergeFlow', 'items.documentType'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('dokter.merge-flow.index', compact('pageName', 'flows', 'pendingGroups', 'completeGroups'));
    }

    public function create()
    {
        $pageName = 'Buat Alur Birokrasi';
        $documentTypes = DocumentType::orderBy('name')->get();

        return view('dokter.merge-flow.create', compact('pageName', 'documentTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'steps' => 'required|array|min:2',
            'steps.*.document_type_id' => 'required|exists:document_types,id',
            'steps.*.link_regex' => 'nullable|string',
            'steps.*.link_label' => 'nullable|string',
            'steps.*.link_field' => 'nullable|string|max:255',
        ]);

        try {
            $flow = MergeFlow::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'description' => $validated['description'] ?? null,
            ]);

            foreach ($validated['steps'] as $index => $step) {
                $flow->steps()->create([
                    'document_type_id' => $step['document_type_id'],
                    'order' => $index + 1,
                    'link_regex' => $step['link_regex'] ?? null,
                    'link_label' => $step['link_label'] ?? null,
                    'link_field' => $step['link_field'] ?? null,
                ]);
            }

            return redirect()->route('dokter.merge-flows.index')->with('success', 'Alur birokrasi berhasil dibuat!');
        } catch (Exception $err) {
            return redirect()->route('dokter.merge-flows.index')->with('error', $err->getMessage());
        }
    }

    public function edit(MergeFlow $mergeFlow)
    {
        $pageName = 'Edit Alur Birokrasi';
        $mergeFlow->load('steps.documentType');
        $documentTypes = DocumentType::orderBy('name')->get();

        return view('dokter.merge-flow.edit', compact('pageName', 'mergeFlow', 'documentTypes'));
    }

    public function update(Request $request, MergeFlow $mergeFlow)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'steps' => 'required|array|min:2',
            'steps.*.document_type_id' => 'required|exists:document_types,id',
            'steps.*.link_regex' => 'nullable|string',
            'steps.*.link_label' => 'nullable|string',
            'steps.*.link_field' => 'nullable|string|max:255',
        ]);

        try {
            $mergeFlow->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $mergeFlow->steps()->delete();
            foreach ($validated['steps'] as $index => $step) {
                $mergeFlow->steps()->create([
                    'document_type_id' => $step['document_type_id'],
                    'order' => $index + 1,
                    'link_regex' => $step['link_regex'] ?? null,
                    'link_label' => $step['link_label'] ?? null,
                    'link_field' => $step['link_field'] ?? null,
                ]);
            }

            return redirect()->route('dokter.merge-flows.index')->with('success', 'Alur birokrasi berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('dokter.merge-flows.index')->with('error', $err->getMessage());
        }
    }

    public function destroy(MergeFlow $mergeFlow)
    {
        try {
            $mergeFlow->delete();

            return redirect()->route('dokter.merge-flows.index')->with('success', 'Alur birokrasi berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('dokter.merge-flows.index')->with('error', $err->getMessage());
        }
    }

    public function groups(Request $request)
    {
        $pageName = 'Grup Penggabungan';
        $query = DocumentMergeGroup::with(['mergeFlow', 'items.documentType', 'items.scanLog']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('vendor_name')) {
            $query->where('vendor_name', 'like', "%{$request->vendor_name}%");
        }

        $groups = $query->latest()->paginate(20)->withQueryString();

        return view('dokter.merge-flow.groups', compact('pageName', 'groups'));
    }
}
