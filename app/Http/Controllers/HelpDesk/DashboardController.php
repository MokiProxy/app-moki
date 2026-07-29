<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{

    private function getTicketQuery(): Builder
    {
        $user = auth()->user();

        if ($user->hasRole('staff')) {
            $divisionId = $user->employee->division_id;
            return Ticket::whereHas('requester.employee', function ($q) use ($divisionId) {
                $q->where('employees.division_id', $divisionId);
            });
        }

        return Ticket::query();
    }

    public function index()
    {
        $query = $this->getTicketQuery();

        $totalTicket = (clone $query)->count();
        $openTicket = (clone $query)->where('status', 'OPEN')->count();
        $inProgressTicket = (clone $query)->where('status', 'IN_PROGRESS')->count();
        $closedTicket = (clone $query)->whereIn('status', ['RESOLVED', 'CLOSED'])->count();

        $statusCounts = (clone $query)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        $categoryCounts = (clone $query)
            ->select('ticket_category_id', 'ticket_categories.name', DB::raw('count(*) as total'))
            ->join('ticket_categories', 'tickets.ticket_category_id', '=', 'ticket_categories.id')
            ->groupBy('ticket_category_id', 'ticket_categories.name')
            ->pluck('total', 'name');

        $user = auth()->user();
        $divisionCounts = collect();
        if ($user->hasRole('super-admin')) {
            $divisionCounts = Ticket::select('divisions.name', DB::raw('count(*) as total'))
                ->join('users', 'tickets.requester_id', '=', 'users.id')
                ->join('employees', 'users.employee_id', '=', 'employees.employee_id')
                ->join('divisions', 'employees.division_id', '=', 'divisions.id')
                ->groupBy('divisions.name')
                ->pluck('total', 'name');
        }

        $recentTickets = $this->getTicketQuery()
            ->with(['requester.employee.division', 'ticketCategory', 'ticketPriority'])
            ->latest()
            ->limit(5)
            ->get();

        $recentActivitiesQuery = TicketHistory::with(['ticket', 'user'])
            ->latest()
            ->limit(5);
        if ($user->hasRole('staff')) {
            $divisionId = $user->employee->division_id;
            $recentActivitiesQuery->whereHas('ticket.requester.employee', function ($q) use ($divisionId) {
                $q->where('employees.division_id', $divisionId);
            });
        }
        $recentActivities = $recentActivitiesQuery->get();

        return view('helpdesk.dashboard.index', compact(
            'totalTicket',
            'openTicket',
            'inProgressTicket',
            'closedTicket',
            'statusCounts',
            'categoryCounts',
            'divisionCounts',
            'recentTickets',
            'recentActivities'
        ));
    }

    public function chartData(Request $request)
    {
        $filter = $request->get('filter', '7d');
        $now = Carbon::now();

        switch ($filter) {
            case '7d':
                $startDate = $now->copy()->subDays(7);
                $format = 'd M';
                break;
            case '30d':
                $startDate = $now->copy()->subDays(30);
                $format = 'd M';
                break;
            case '3m':
                $startDate = $now->copy()->subMonths(3);
                $format = 'M Y';
                break;
            case '1y':
                $startDate = $now->copy()->subYear();
                $format = 'M Y';
                break;
            default:
                $startDate = $now->copy()->subDays(7);
                $format = 'd M';
                break;
        }

        $tickets = $this->getTicketQuery()
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'labels' => $tickets->pluck('date')->map(fn($d) => Carbon::parse($d)->format($format)),
            'data' => $tickets->pluck('total'),
        ]);
    }
}
