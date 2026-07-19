<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Models\TicketCategory;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class TicketCategoryController extends Controller
{
    public function index()
    {
        $ticketCategories = TicketCategory::all();
        return view('helpdesk.ticket-categories.index', compact('ticketCategories'));
    }

    // Main Data Table
    public function datatable(Request $request)
    {
        $data = TicketCategory::all();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', function ($row) {
                return $row->name;
            })
            ->addColumn('description', function ($row) {
                return $row->description ?? '-';
            })
            ->addColumn('action', function ($row) {
                // Pastikan atribut data-id dan atribut lainnya lengkap untuk JS
                return '
                <div class="btn-group">
                    <button class="btn btn-sm btn-primary btn-edit"
                        data-id="' . $row->id . '"
                        title="Edit"><i class="mdi mdi-pencil"></i></button>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="' . $row->id . '" title="Hapus"><i class="mdi mdi-trash-can"></i></button>
                </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required'
        ]);

        TicketCategory::create($request->all());
        return response()->json(['success' => true, 'message' => 'Kategori Tiket berhasil ditambahkan!']);
    }

    public function show($id)
    {
        $ticketCategory = TicketCategory::find($id);
        return response()->json(['success' => true, 'data' => $ticketCategory]);
    }

    public function update(Request $request, $id)
    {
        $ticketCategory = TicketCategory::findOrFail($id);
        $ticketCategory->update($request->all());
        return response()->json(['success' => true, 'message' => 'Data Kategori Tiket berhasil diperbarui!']);
    }

    public function destroy($id)
    {
        TicketCategory::destroy($id);
        return response()->json(['success' => true, 'message' => 'Kategori Tiket dihapus!']);
    }
}
