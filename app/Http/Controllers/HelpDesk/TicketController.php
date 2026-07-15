<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Ticket as ModelsTicket;
use App\Models\TicketAttachment;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view("helpdesk.tickets.index");
    }
    public function me()
    {
        return view("helpdesk.tickets.index");
    }

    // Data Table
    public function datatable(Request $request)
    {
        $data = Ticket::with(["requester.employee.division"])->latest();
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('ticket_number', function ($row) {
                $ticketNumber = $row->ticket_number;
                return "<p class='fw-bold m-0 p-0'>$ticketNumber</p>";
            })
            ->addColumn('requester_name', function ($row) {
                return $row->requester->employee->name . " - " . $row->requester->employee->division->name ?? '-';
            })
            ->addColumn('assigned_to_name', function ($row) {
                return $row->assignedTo->name ?? 'Belum Ditugaskan';
            })
            ->addColumn('ticket_category_name', function ($row) {
                return $row->ticketCategory->name ?? '-';
            })
            ->addColumn('ticket_priority_name', function ($row) {
                $color = $row->ticketPriority->color;
                $name = $row->ticketPriority->name;
                return "<div style='display: flex; align-items: center; gap: 5px;'><div style='width: 13px; height: 13px; background-color: $color; border-radius: 100%;'></div>" . ucfirst($name) . "</span>" ?? '-';
            })
            ->addColumn('title', function ($row) {
                return $row->title ?? '-';
            })
            ->addColumn('due_time', function ($row) {
                return $row->due_time ?? '-';
            })
            ->addColumn('status', function ($row) {
                return "<p class='m-0 bg-success text-center p-1 rounded fw-bold text-white'>" . ucfirst($row->status) . "</p>" ?? '-';
            })
            ->addColumn('action', function ($row) {
                // Pastikan atribut data-id dan atribut lainnya lengkap untuk JS
                return '
                <div class="btn-group">
                    <button class="btn btn-sm btn-info btn-view" data-id="' . $row->id . '" title="Detail"><i class="mdi mdi-eye"></i></button>
                </div>';
            })
            ->rawColumns(['ticket_number', 'assigned_to_name', 'ticket_priority_name', 'status', 'action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $ticketCategories = TicketCategory::all();
        $ticketPriorities = TicketPriority::all();
        return view("helpdesk.tickets.create", compact("ticketCategories", "ticketPriorities"));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function generateTicketNumber()
    {
        $latestTicket = Ticket::latest()->first();
        if ($latestTicket == null) {
            return "TIX-" . date("Ymd") . "-0001";
        } else {
            $lastTicket =  explode("-", $latestTicket["ticket_number"])[2] ?? "0000";
            $lastTicketNum = (int)$lastTicket;
            $nowTicketNum = sprintf('%04d', $lastTicketNum + 1);
            $ticketNumber = "TIX-" . date("Ymd");
            return $ticketNumber . "-" . $nowTicketNum;
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => '',
            'ticket_category_id' => 'required',
            'ticket_priority_id' => 'required',
            'sla' => 'required',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:5120|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar',
        ]);

        try {
            $ticketNumber = $this->generateTicketNumber();
            $timestamp = strtotime("+$request->sla hours");
            $ticket = Ticket::create([
                "ticket_number" => $ticketNumber,
                "requester_id" => auth()->user()->id,
                "ticket_category_id" => $request->ticket_category_id,
                "ticket_priority_id" => $request->ticket_priority_id,
                "title" => $request->title,
                "description" => $request->description,
                "sla" => $request->sla,
                "due_time" =>  date('Y-m-d H:i:s', $timestamp),
                "status" => "OPEN"
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $filePath = $file->store('ticket-attachments', 'public');
                    TicketAttachment::create([
                        'ticket_id'   => $ticket->id,
                        'uploaded_by' => auth()->id(),
                        'file_name'   => $file->getClientOriginalName(),
                        'file_path'   => $filePath,
                        'mime_type'   => $file->getMimeType(),
                        'file_size'   => $file->getSize(),
                    ]);
                }
            }

            return response()->json(['success' => true, 'message' => 'Tiket berhasil dibuat!']);
        } catch (Exception $err) {
            return response()->json(["success" => false, "message" => $err->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $ticket = ModelsTicket::with(["requester.employee.division", "assignedTo", "ticketCategory", "ticketPriority", "attachments"])->find($id);
        return response()->json(['success' => true, 'data' => $ticket]);
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

    public function getTeknisi()
    {
        $teknisi = User::where('role_id', User::ROLE_TEKNISI)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json(['success' => true, 'data' => $teknisi]);
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
        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        try {
            $ticket = Ticket::findOrFail($id);
            $ticket->update([
                'assigned_to' => $request->assigned_to,
                'status' => 'ASSIGNED',
            ]);

            return response()->json(['success' => true, 'message' => 'Teknisi berhasil ditugaskan!']);
        } catch (Exception $err) {
            return response()->json(['success' => false, 'message' => $err->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
