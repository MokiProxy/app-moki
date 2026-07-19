<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketAttachmentController extends Controller
{
    public function download($id)
    {
        $attachment = TicketAttachment::findOrFail($id);
        $filePath = Storage::disk('public')->path($attachment->file_path);

        if (!file_exists($filePath)) {
            abort(404, 'File tidak ditemukan');
        }

        $mime = $attachment->mime_type;
        $isViewable = in_array($mime, [
            'application/pdf',
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        ]);

        if ($isViewable) {
            return response()->file($filePath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . $attachment->file_name . '"'
            ]);
        }

        return response()->download($filePath, $attachment->file_name);
    }
}
