<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Models\TicketPriority;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class TicketPriorityController extends Controller
{
    public function index()
    {
        $ticketPriorities = TicketPriority::all();
        return view('helpdesk.ticket-priorities.index', compact('ticketPriorities'));
    }

    // Data Table
    public function datatable(Request $request)
    {
        $data = TicketPriority::all();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('name', function ($row) {
                return $row->name;
            })
            ->addColumn('level', function ($row) {
                return $row->level ?? '-';
            })
            ->addColumn('color', function ($row) {
                return "<div style='display: flex; align-items: center; gap: 5px;'><div style='width: 15px; height: 15px; background-color: $row->color; border-radius: 100%;'></div>" . ucfirst($row->color) . "</span>" ?? '-';
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
            ->rawColumns(['color', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'level' => 'required|unique:ticket_priorities,level',
            'color' => 'required'
        ]);

        TicketPriority::create($request->all());
        return response()->json(['success' => true, 'message' => 'Prioritas Tiket berhasil ditambahkan!']);
    }

    public function show($id)
    {
        $ticketPriority = TicketPriority::find($id);
        return response()->json(['success' => true, 'data' => $ticketPriority]);
    }

    public function update(Request $request, $id)
    {
        $ticketPriority = TicketPriority::findOrFail($id);
        $ticketPriority->update($request->all());
        return response()->json(['success' => true, 'message' => 'Data Prioritas Tiket berhasil diperbarui!']);
    }

    public function destroy($id)
    {
        TicketPriority::destroy($id);
        return response()->json(['success' => true, 'message' => 'Prioritas Tiket dihapus!']);
    }
}
