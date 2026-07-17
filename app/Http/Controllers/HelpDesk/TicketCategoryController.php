<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Models\TicketCategory;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class TicketCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function checkIfNotRole($roles)
    {
        $roleId = auth()->user()->role_id;
        if (!in_array($roleId, $roles)) {
            return redirect()->route("helpdesk.tickets.index");
        }
    }

    public function index()
    {
        $this->checkIfNotRole([1, 5]);
        $ticketCategories = TicketCategory::all();
        return view('helpdesk.ticket-categories.index', compact('ticketCategories'));
    }

    // Main Data Table
    public function datatable(Request $request)
    {
        $this->checkIfNotRole([1, 5]);
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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->checkIfNotRole([1, 5]);
        $request->validate([
            'name' => 'required',
            'description' => 'required'
        ]);

        TicketCategory::create($request->all());
        return response()->json(['success' => true, 'message' => 'Kategori Tiket berhasil ditambahkan!']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $this->checkIfNotRole([1, 5]);
        $ticketCategory = TicketCategory::find($id);
        return response()->json(['success' => true, 'data' => $ticketCategory]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->checkIfNotRole([1, 5]);

        $ticketCategory = TicketCategory::findOrFail($id);
        $ticketCategory->update($request->all());
        return response()->json(['success' => true, 'message' => 'Data Kategori Tiket berhasil diperbarui!']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->checkIfNotRole([1, 5]);

        TicketCategory::destroy($id);
        return response()->json(['success' => true, 'message' => 'Kategori Tiket dihapus!']);
    }
}
