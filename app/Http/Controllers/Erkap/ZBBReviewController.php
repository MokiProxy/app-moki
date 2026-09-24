<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\Erkap\UpdateZBBReviewRequest;
use App\Models\Division;
use App\Models\Erkap\RKAP;
use App\Models\Erkap\ZBBReview;
use App\Services\ErkapAccess;
use App\Services\Erkap\ZBBReviewService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ZBBReviewController extends Controller
{
    public function index(Request $request)
    {
        $pageName = 'Zero Based Budgeting (ZBB)';
        $rkapList = RKAP::orderByDesc('year')->get();

        $divisionQuery = Division::query()
            ->when(ErkapAccess::isDivisionScoped(), function ($query) {
                $query->where('id', ErkapAccess::divisionId());
            });
        $divisions = $divisionQuery->get();

        $query = ZBBReview::with(['rkap', 'division', 'reviewer']);

        if ($request->filled('erkap_rkap_id')) {
            $query->where('erkap_rkap_id', $request->integer('erkap_rkap_id'));
        }

        if ($request->filled('division_id')) {
            $query->where('division_id', $request->integer('division_id'));
        } elseif (ErkapAccess::isDivisionScoped() && ErkapAccess::divisionId()) {
            $query->where('division_id', ErkapAccess::divisionId());
        }

        if ($request->filled('zbb_status')) {
            $query->where('zbb_status', $request->input('zbb_status'));
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->input('subject_type'));
        }

        $reviews = $query->orderByDesc('erkap_rkap_id')->latest('id')->paginate(15)->appends($request->query());

        $filtered = $query->get();
        $summary = [
            'total' => $filtered->count(),
            'skipped' => $filtered->where('zbb_status', 'skipped')->count(),
            'pending' => $filtered->where('zbb_status', 'pending')->count(),
            'approved' => $filtered->where('zbb_status', 'approved')->count(),
            'rejected' => $filtered->where('zbb_status', 'rejected')->count(),
            'blocking' => $filtered->filter(fn (ZBBReview $review) => $review->blocksConsolidation())->count(),
        ];

        $blockingReviews = $filtered->filter(fn (ZBBReview $review) => $review->blocksConsolidation())->take(5)->values();

        $statuses = ZBBReview::STATUS_LABELS;
        $subjectTypes = [
            'routine_cost' => 'Biaya Rutin (OPEX)',
            'investment_plan' => 'Rencana Investasi (CAPEX)',
            'revenue_plan' => 'Rencana Pendapatan',
            'work_program' => 'Program Kerja',
        ];

        return view('erkap.zbb-review.index', compact(
            'pageName', 'rkapList', 'divisions', 'reviews', 'summary', 'blockingReviews', 'statuses', 'subjectTypes'
        ));
    }

    public function build(Request $request)
    {
        try {
            $request->validate([
                'erkap_rkap_id' => ['required', 'integer', 'exists:erkap_rkap,id'],
            ]);

            $rkap = RKAP::findOrFail($request->integer('erkap_rkap_id'));
            $result = ZBBReviewService::buildReviews($rkap);

            $message = "Review ZBB untuk RKAP {$rkap->year} berhasil dibangun: {$result['total']} pos anggaran "
                . "({$result['increase']} kenaikan, {$result['skipped']} auto-skip).";

            if ($result['blocking'] > 0) {
                $message .= " {$result['blocking']} pos kenaikan masih menunggu justifikasi/persetujuan.";
            }

            return redirect()->route('erkap.zbb-reviews.index', ['erkap_rkap_id' => $rkap->id])
                ->with('success', $message);
        } catch (ValidationException $err) {
            return redirect()->route('erkap.zbb-reviews.index')
                ->with('error', collect($err->errors())->flatten()->first());
        } catch (Exception $err) {
            return redirect()->route('erkap.zbb-reviews.index')
                ->with('error', $err->getMessage());
        }
    }

    public function show(ZBBReview $review)
    {
        $pageName = 'Detail Review ZBB';

        $review->load(['rkap', 'division', 'reviewer']);

        return view('erkap.zbb-review.show', compact('pageName', 'review'));
    }

    public function update(UpdateZBBReviewRequest $request, ZBBReview $review)
    {
        try {
            $rkap = RKAP::findOrFail($review->erkap_rkap_id);

            ZBBReviewService::review($rkap, $review->id, Auth::user(), $request->validated());

            return redirect()->route('erkap.zbb-reviews.show', $review->id)
                ->with('success', 'Review ZBB berhasil disimpan.');
        } catch (ValidationException $err) {
            return redirect()->route('erkap.zbb-reviews.show', $review->id)
                ->withErrors($err->errors())
                ->withInput();
        } catch (Exception $err) {
            return redirect()->route('erkap.zbb-reviews.show', $review->id)
                ->with('error', $err->getMessage());
        }
    }
}