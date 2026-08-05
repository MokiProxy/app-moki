<?php

namespace App\Http\Middleware;

use App\Models\FormitApproval;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsApprover
{
    public function handle(Request $request, Closure $next): Response
    {
        $employeeId = auth()->user()->employee_id;
        $isApprover = FormitApproval::where('approver_id', $employeeId)->exists();

        if (!$isApprover) {
            abort(403, 'Anda tidak memiliki akses ke halaman approval.');
        }

        return $next($request);
    }
}
