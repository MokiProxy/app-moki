<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Exports\TicketExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

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
        return view("helpdesk.reports.index");
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */


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
            ->rawColumns(['ticket_number', 'assigned_to_name', 'ticket_priority_name', 'status'])
            ->make(true);
    }

    public function generatePdf()
    {
        $tickets = Ticket::with(['requester.employee.division', 'assignedTo', 'ticketCategory', 'ticketPriority'])
            ->latest()
            ->get();

        $pdf = Pdf::loadView('helpdesk.reports.pdf', compact('tickets'));
        $pdf->setOption('isRemoteEnabled', true);
        return $pdf->download('laporan-tiket-' . date('Y-m-d') . '.pdf');
    }

    public function generateExcel()
    {
        $tickets = Ticket::with(['requester.employee.division', 'assignedTo', 'ticketCategory', 'ticketPriority'])
            ->latest()
            ->get();

        return Excel::download(new TicketExport($tickets), 'laporan-tiket-' . date('Y-m-d') . '.xlsx');
    }

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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        //
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
