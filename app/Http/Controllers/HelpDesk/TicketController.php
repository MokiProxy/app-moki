<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketCategory;
use App\Models\TicketHistory;
use App\Models\TicketPriority;
use App\Models\User;
use App\Services\TicketHistoryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;

class TicketController extends Controller
{
    protected $historyService;

    public function __construct(TicketHistoryService $historyService)
    {
        $this->historyService = $historyService;
    }

    public function formatTicketStatus($status)
    {
        if (in_array($status, ["OPEN", "ASSIGNED", "PENDING", "RESOLVED", "CLOSED", "REJECTED"])) {
            return ucfirst(strtolower($status));
        } else {
            return "In Progress";
        }
    }

    public function setStatusBackground($status)
    {
        if ($status == "OPEN") {
            $color = "primary";
        } else if ($status == "ASSIGNED") {
            $color = "warning";
        } else if ($status == "PENDING") {
            $color = "warning";
        } else if ($status == "IN_PROGRESS") {
            $color = "warning";
        } else if ($status == "RESOLVED") {
            $color = "success";
        } else if ($status == "CLOSED") {
            $color = "success";
        } else if ($status == "REJECTED") {
            $color = "danger";
        }

        return "bg-" . $color;
    }

    public function index()
    {
        return view("helpdesk.tickets.index");
    }

    public function datatable(Request $request)
    {
        $role = session('user_role');
        $authUserId = auth()->user()->id;
        if ($role == 4) {
            $data = Ticket::with(["requester.employee.division"])->where('assigned_to', "=", $authUserId)->get();
        } else if ($role == 3) {
            $data = Ticket::with(["requester.employee.division"])->where('requester_id', "=", $authUserId)->get();
        } else {
            $data = Ticket::with(["requester.employee.division"])->latest();
        }
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
                $bgColor = $this->setStatusBackground($row->status);
                return $row->rating ? "<div class='d-flex flex-column gap-2 align-items-center'><p class='m-0 $bgColor text-center p-1 ps-3 pe-3 rounded-pill fw-bold text-white w-auto'>" . $this->formatTicketStatus($row->status) . "</p><p class='text-center m-0 $bgColor text-center p-1 rounded fw-bold text-white'><i class='bx bx-star'></i>" . $row->rating . "/5</p></div>" : "<div class='d-flex flex-column align-items-center gap-2'><p class='m-0 $bgColor text-center p-1 ps-3 pe-3 rounded-pill fw-bold text-white w-auto'>" . $this->formatTicketStatus($row->status) . "</p></div>";
            })
            ->addColumn('action', function ($row) {
                $role = session('user_role');
                if ($role == 4) {
                    if ($row->status == "ASSIGNED") {
                        return '
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info btn-view" data-id="' . $row->id . '" title="Detail"><i class="mdi mdi-eye"></i></button>
                            <button class="btn btn-sm btn-success btn-approve" data-id="' . $row->id . '" title="Approve"><i class="mdi mdi-check-decagram"></i></button>
                        </div>';
                    } elseif ($row->status == "IN_PROGRESS") {
                        return '
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info btn-view" data-id="' . $row->id . '" title="Detail"><i class="mdi mdi-eye"></i></button>
                            <button class="btn btn-sm btn-success btn-resolved" data-id="' . $row->id . '" title="Resolved"><i class="mdi mdi-check-all"></i></button>
                        </div>';
                    } else {
                        return '
                        <div class="btn-group">
                            <button class="btn btn-sm btn-info btn-view" data-id="' . $row->id . '" title="Detail"><i class="mdi mdi-eye"></i></button>
                        </div>';
                    }
                } elseif ($role == 3) {
                    $reopenable = in_array($row->status, ['RESOLVED', 'CLOSED', 'REJECTED']);
                    $buttons = '<button class="btn btn-sm btn-info btn-view" data-id="' . $row->id . '" title="Detail"><i class="mdi mdi-eye"></i></button>';
                    if ($row->status == "OPEN") {
                        $buttons .= '<a href="' . url('helpdesk/tickets/' . $row->id . '/edit') . '" class="btn btn-sm btn-primary" title="Edit"><i class="mdi mdi-pencil"></i></a>';
                        $buttons .= '<button class="btn btn-sm btn-danger btn-delete" data-id="' . $row->id . '" title="Hapus"><i class="mdi mdi-trash-can"></i></button>';
                    }
                    if ($row->status == "RESOLVED") {
                        $buttons .= '<button class="btn btn-sm btn-primary btn-confirm" data-id="' . $row->id . '" title="Confirm"><i class="mdi mdi-check-circle"></i></button>';
                    }
                    if ($reopenable) {
                        $buttons .= '<button class="btn btn-sm btn-warning btn-reopen" data-id="' . $row->id . '" title="Reopen"><i class="mdi mdi-refresh"></i></button>';
                    }
                    return '<div class="btn-group">' . $buttons . '</div>';
                } else {
                    $buttons = '<button class="btn btn-sm btn-info btn-view" data-id="' . $row->id . '" title="Detail"><i class="mdi mdi-eye"></i></button>';
                    if ($row->status == "OPEN") {
                        $buttons .= '<a href="' . url('helpdesk/tickets/' . $row->id . '/edit') . '" class="btn btn-sm btn-primary" title="Edit"><i class="mdi mdi-pencil"></i></a>';
                        $buttons .= '<button class="btn btn-sm btn-danger btn-delete" data-id="' . $row->id . '" title="Hapus"><i class="mdi mdi-trash-can"></i></button>';
                    }
                    return '<div class="btn-group">' . $buttons . '</div>';
                }
            })
            ->rawColumns(['ticket_number', 'assigned_to_name', 'ticket_priority_name', 'status', 'action'])
            ->make(true);
    }

    public function create()
    {
        $ticketCategories = TicketCategory::all();
        $ticketPriorities = TicketPriority::all();
        return view("helpdesk.tickets.create", compact("ticketCategories", "ticketPriorities"));
    }

    public function edit($id)
    {
        $ticket = Ticket::with('attachments')->findOrFail($id);

        if ($ticket->requester_id !== auth()->id() || $ticket->status !== 'OPEN') {
            abort(403, 'Unauthorized.');
        }

        $ticketCategories = TicketCategory::all();
        $ticketPriorities = TicketPriority::all();
        return view("helpdesk.tickets.create", compact("ticketCategories", "ticketPriorities", "ticket"));
    }

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

        DB::beginTransaction();
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

            $this->historyService->created($ticket);

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
                    $this->historyService->attachmentUploaded($ticket, $file->getClientOriginalName());
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Tiket berhasil dibuat!']);
        } catch (Exception $err) {
            DB::rollBack();
            return response()->json(["success" => false, "message" => $err->getMessage()]);
        }
    }

    public function show($id)
    {
        $ticket = Ticket::with(["requester.employee.division", "assignedTo", "ticketCategory", "ticketPriority", "attachments"])->find($id);
        return response()->json(['success' => true, 'data' => $ticket]);
    }

    public function timeline($id)
    {
        $histories = TicketHistory::where('ticket_id', $id)
            ->with('user')
            ->orderBy('created_at', 'ASC')
            ->get();

        return response()->json(['success' => true, 'data' => $histories]);
    }

    public function getTeknisi()
    {
        $teknisi = User::where('role_id', User::ROLE_TEKNISI)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json(['success' => true, 'data' => $teknisi]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            $ticket = Ticket::findOrFail($id);
            $oldAgent = $ticket->assigned_to;

            $ticket->update([
                'assigned_to' => $request->assigned_to,
                'status' => 'ASSIGNED',
            ]);

            $this->historyService->assigned($ticket, $oldAgent, $request->assigned_to);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Teknisi berhasil ditugaskan!']);
        } catch (Exception $err) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $err->getMessage()]);
        }
    }

    public function assignTeknisi(Request $request, $id)
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            $ticket = Ticket::findOrFail($id);
            $oldAgent = $ticket->assigned_to;

            $ticket->update([
                'assigned_to' => $request->assigned_to,
                'status' => 'ASSIGNED',
            ]);

            $this->historyService->assigned($ticket, $oldAgent, $request->assigned_to);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Teknisi berhasil ditugaskan!']);
        } catch (Exception $err) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $err->getMessage()]);
        }
    }

    public function approve($id)
    {
        DB::beginTransaction();
        try {
            $ticket = Ticket::findOrFail($id);
            $oldStatus = $ticket->status;

            $ticket->update([
                'status' => 'IN_PROGRESS',
            ]);

            $this->historyService->statusChanged($ticket, $oldStatus, 'IN_PROGRESS');

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Tiket Berhasil Disetujui!']);
        } catch (Exception $err) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $err->getMessage()]);
        }
    }

    public function resolved($id)
    {
        DB::beginTransaction();
        try {
            $ticket = Ticket::findOrFail($id);
            $oldStatus = $ticket->status;

            $ticket->update([
                'status' => 'RESOLVED',
                'resolved_at' => now(),
            ]);

            $this->historyService->statusChanged($ticket, $oldStatus, 'RESOLVED');

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Tiket berhasil diselesaikan!']);
        } catch (Exception $err) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $err->getMessage()]);
        }
    }

    public function reopen($id)
    {
        DB::beginTransaction();
        try {
            $ticket = Ticket::findOrFail($id);
            $oldStatus = $ticket->status;

            $ticket->update([
                'status' => 'OPEN',
                'assigned_to' => null,
                'resolved_at' => null,
                'closed_at' => null,
            ]);

            $this->historyService->statusChanged($ticket, $oldStatus, 'OPEN');

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Tiket berhasil dibuka kembali!']);
        } catch (Exception $err) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $err->getMessage()]);
        }
    }

    public function updateContent(Request $request, $id)
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

        DB::beginTransaction();
        try {
            $ticket = Ticket::findOrFail($id);

            if ($ticket->requester_id !== auth()->id() || $ticket->status !== 'OPEN') {
                abort(403, 'Unauthorized.');
            }

            $timestamp = strtotime("+$request->sla hours");

            $ticket->update([
                'title' => $request->title,
                'description' => $request->description,
                'ticket_category_id' => $request->ticket_category_id,
                'ticket_priority_id' => $request->ticket_priority_id,
                'sla' => $request->sla,
                'due_time' => date('Y-m-d H:i:s', $timestamp),
            ]);

            $this->historyService->statusChanged($ticket, 'OPEN', 'OPEN');

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
                    $this->historyService->attachmentUploaded($ticket, $file->getClientOriginalName());
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Tiket berhasil diupdate!']);
        } catch (Exception $err) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $err->getMessage()]);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $ticket = Ticket::findOrFail($id);

            if ($ticket->requester_id !== auth()->id() || $ticket->status !== 'OPEN') {
                abort(403, 'Unauthorized.');
            }

            $ticket->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Tiket berhasil dihapus!']);
        } catch (Exception $err) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $err->getMessage()]);
        }
    }
}
