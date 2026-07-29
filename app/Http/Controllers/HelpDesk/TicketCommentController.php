<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use Exception;
use Illuminate\Http\Request;

class TicketCommentController extends Controller
{
    public function index($ticketId, Request $request)
    {
        $query = TicketComment::where('ticket_id', $ticketId)
            ->with('user', 'attachment')
            ->orderBy('created_at', 'ASC');

        if ($request->filled('since')) {
            $query->where('created_at', '>', $request->since);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function store(Request $request, $ticketId)
    {
        $request->validate([
            'comment' => 'required_without:attachment|string|max:5000',
            'attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar',
        ]);

        try {
            $ticket = Ticket::findOrFail($ticketId);
            $authUserId = auth()->id();

            $attachmentId = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filePath = $file->store('ticket-chat-attachments', 'public');
                $attachment = TicketAttachment::create([
                    'ticket_id'   => $ticketId,
                    'uploaded_by' => $authUserId,
                    'file_name'   => $file->getClientOriginalName(),
                    'file_path'   => $filePath,
                    'mime_type'   => $file->getMimeType(),
                    'file_size'   => $file->getSize(),
                ]);
                $attachmentId = $attachment->id;
            }

            $comment = TicketComment::create([
                'ticket_id'             => $ticketId,
                'user_id'               => $authUserId,
                'comment'               => $request->input('comment', ''),
                'ticket_attachment_id'  => $attachmentId,
            ]);

            $comment->load('user', 'attachment');

            return response()->json(['success' => true, 'data' => $comment]);
        } catch (Exception $err) {
            return response()->json(['success' => false, 'message' => $err->getMessage()]);
        }
    }
}
