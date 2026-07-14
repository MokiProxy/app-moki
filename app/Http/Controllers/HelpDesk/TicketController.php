<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        if($latestTicket == null) {
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
