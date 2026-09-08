<?php

namespace App\Http\Controllers\Erkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRatingCriteriaRequest;
use App\Http\Requests\UpdateRatingCriteriaRequest;
use App\Models\Erkap\RatingCriteria;
use Exception;

class RatingCriteriaController extends Controller
{
    public function index()
    {
        $pageName = 'Rating Criteria';
        $criterias = RatingCriteria::paginate(10);

        return view('erkap.rating-criteria.index', compact('pageName', 'criterias'));
    }

    public function create()
    {
        $pageName = 'Buat Rating Criteria';

        return view('erkap.rating-criteria.create', compact('pageName'));
    }

    public function store(StoreRatingCriteriaRequest $request)
    {
        try {
            RatingCriteria::create($request->validated());

            return redirect()->route('erkap.rating-criterias.index')
                ->with('success', 'Rating criteria baru berhasil disimpan!');
        } catch (Exception $err) {
            return redirect()->route('erkap.rating-criterias.create')
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function edit(RatingCriteria $ratingCriteria)
    {
        $pageName = 'Edit Rating Criteria';

        return view('erkap.rating-criteria.edit', compact('pageName', 'ratingCriteria'));
    }

    public function update(UpdateRatingCriteriaRequest $request, RatingCriteria $ratingCriteria)
    {
        try {
            $ratingCriteria->update($request->validated());

            return redirect()->route('erkap.rating-criterias.index')
                ->with('success', 'Rating criteria berhasil diperbarui!');
        } catch (Exception $err) {
            return redirect()->route('erkap.rating-criterias.edit', $ratingCriteria->id)
                ->withInput()
                ->with('error', $err->getMessage())
                ->with('error_detail', [
                    'file' => $err->getFile(),
                    'line' => $err->getLine(),
                    'trace' => $err->getTraceAsString(),
                ]);
        }
    }

    public function destroy(RatingCriteria $ratingCriteria)
    {
        try {
            $ratingCriteria->delete();

            return redirect()->route('erkap.rating-criterias.index')
                ->with('success', 'Rating criteria berhasil dihapus!');
        } catch (Exception $err) {
            return redirect()->route('erkap.rating-criterias.index')->with('error', $err->getMessage());
        }
    }
}