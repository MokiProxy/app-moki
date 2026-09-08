<?php

use App\Http\Controllers\Erkap\CostElementCategoryController;
use App\Http\Controllers\Erkap\CostElementController;
use App\Http\Controllers\Erkap\DashboardController as ErkapDashboardController;
use App\Http\Controllers\Erkap\InvestattionCategoryController;
use App\Http\Controllers\Erkap\InvestationCriteriaController;
use App\Http\Controllers\Erkap\InvestationTypeController;
use App\Http\Controllers\Erkap\RatingCriteriaController;
use App\Http\Controllers\Erkap\RKAPController;
use App\Http\Controllers\Erkap\RiskAppetiteController;
use App\Http\Controllers\Erkap\RiskImpactController;
use App\Http\Controllers\Erkap\RiskProbabilityController;
use App\Http\Controllers\Erkap\RiskScaleController;
use App\Http\Controllers\Erkap\RiskScoreLevelController;
use App\Http\Controllers\Erkap\RiskTaxonomyController;
use App\Http\Controllers\Erkap\RiskTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix("erkap")->name("erkap.")->group(function () {
    Route::get("/", [ErkapDashboardController::class, 'index'])->name("index");

    Route::prefix('cost-element-categories')->name('cost-element-categories.')->group(function () {
        Route::get('/', [CostElementCategoryController::class, 'index'])->name('index');
        Route::get('/create', [CostElementCategoryController::class, 'create'])->name('create');
        Route::post('/', [CostElementCategoryController::class, 'store'])->name('store');
        Route::get('/{costElementCategory}/edit', [CostElementCategoryController::class, 'edit'])->name('edit');
        Route::put('/{costElementCategory}', [CostElementCategoryController::class, 'update'])->name('update');
        Route::delete('/{costElementCategory}', [CostElementCategoryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('cost-elements')->name('cost-elements.')->group(function () {
        Route::get('/', [CostElementController::class, 'index'])->name('index');
        Route::get('/create', [CostElementController::class, 'create'])->name('create');
        Route::post('/', [CostElementController::class, 'store'])->name('store');
        Route::get('/{costElement}/edit', [CostElementController::class, 'edit'])->name('edit');
        Route::put('/{costElement}', [CostElementController::class, 'update'])->name('update');
        Route::delete('/{costElement}', [CostElementController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('risk-appetites')->name('risk-appetites.')->group(function () {
        Route::get('/', [RiskAppetiteController::class, 'index'])->name('index');
        Route::get('/create', [RiskAppetiteController::class, 'create'])->name('create');
        Route::post('/', [RiskAppetiteController::class, 'store'])->name('store');
        Route::get('/{riskAppetite}/edit', [RiskAppetiteController::class, 'edit'])->name('edit');
        Route::put('/{riskAppetite}', [RiskAppetiteController::class, 'update'])->name('update');
        Route::delete('/{riskAppetite}', [RiskAppetiteController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('risk-taxonomies')->name('risk-taxonomies.')->group(function () {
        Route::get('/', [RiskTaxonomyController::class, 'index'])->name('index');
        Route::get('/create', [RiskTaxonomyController::class, 'create'])->name('create');
        Route::post('/', [RiskTaxonomyController::class, 'store'])->name('store');
        Route::get('/{riskTaxonomy}/edit', [RiskTaxonomyController::class, 'edit'])->name('edit');
        Route::put('/{riskTaxonomy}', [RiskTaxonomyController::class, 'update'])->name('update');
        Route::delete('/{riskTaxonomy}', [RiskTaxonomyController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('risk-types')->name('risk-types.')->group(function () {
        Route::get('/', [RiskTypeController::class, 'index'])->name('index');
        Route::get('/create', [RiskTypeController::class, 'create'])->name('create');
        Route::post('/', [RiskTypeController::class, 'store'])->name('store');
        Route::get('/{riskType}/edit', [RiskTypeController::class, 'edit'])->name('edit');
        Route::put('/{riskType}', [RiskTypeController::class, 'update'])->name('update');
        Route::delete('/{riskType}', [RiskTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('rating-criterias')->name('rating-criterias.')->group(function () {
        Route::get('/', [RatingCriteriaController::class, 'index'])->name('index');
        Route::get('/create', [RatingCriteriaController::class, 'create'])->name('create');
        Route::post('/', [RatingCriteriaController::class, 'store'])->name('store');
        Route::get('/{ratingCriteria}/edit', [RatingCriteriaController::class, 'edit'])->name('edit');
        Route::put('/{ratingCriteria}', [RatingCriteriaController::class, 'update'])->name('update');
        Route::delete('/{ratingCriteria}', [RatingCriteriaController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('risk-scales')->name('risk-scales.')->group(function () {
        Route::get('/', [RiskScaleController::class, 'index'])->name('index');
        Route::get('/create', [RiskScaleController::class, 'create'])->name('create');
        Route::post('/', [RiskScaleController::class, 'store'])->name('store');
        Route::get('/{riskScale}/edit', [RiskScaleController::class, 'edit'])->name('edit');
        Route::put('/{riskScale}', [RiskScaleController::class, 'update'])->name('update');
        Route::delete('/{riskScale}', [RiskScaleController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('risk-probabilities')->name('risk-probabilities.')->group(function () {
        Route::get('/', [RiskProbabilityController::class, 'index'])->name('index');
        Route::get('/create', [RiskProbabilityController::class, 'create'])->name('create');
        Route::post('/', [RiskProbabilityController::class, 'store'])->name('store');
        Route::get('/{riskProbability}/edit', [RiskProbabilityController::class, 'edit'])->name('edit');
        Route::put('/{riskProbability}', [RiskProbabilityController::class, 'update'])->name('update');
        Route::delete('/{riskProbability}', [RiskProbabilityController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('risk-impacts')->name('risk-impacts.')->group(function () {
        Route::get('/', [RiskImpactController::class, 'index'])->name('index');
        Route::get('/create', [RiskImpactController::class, 'create'])->name('create');
        Route::post('/', [RiskImpactController::class, 'store'])->name('store');
        Route::get('/{riskImpact}/edit', [RiskImpactController::class, 'edit'])->name('edit');
        Route::put('/{riskImpact}', [RiskImpactController::class, 'update'])->name('update');
        Route::delete('/{riskImpact}', [RiskImpactController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('risk-score-levels')->name('risk-score-levels.')->group(function () {
        Route::get('/', [RiskScoreLevelController::class, 'index'])->name('index');
        Route::get('/create', [RiskScoreLevelController::class, 'create'])->name('create');
        Route::post('/', [RiskScoreLevelController::class, 'store'])->name('store');
        Route::get('/{riskScoreLevel}/edit', [RiskScoreLevelController::class, 'edit'])->name('edit');
        Route::put('/{riskScoreLevel}', [RiskScoreLevelController::class, 'update'])->name('update');
        Route::delete('/{riskScoreLevel}', [RiskScoreLevelController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('investation-types')->name('investation-types.')->group(function () {
        Route::get('/', [InvestationTypeController::class, 'index'])->name('index');
        Route::get('/create', [InvestationTypeController::class, 'create'])->name('create');
        Route::post('/', [InvestationTypeController::class, 'store'])->name('store');
        Route::get('/{investationType}/edit', [InvestationTypeController::class, 'edit'])->name('edit');
        Route::put('/{investationType}', [InvestationTypeController::class, 'update'])->name('update');
        Route::delete('/{investationType}', [InvestationTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('investation-criterias')->name('investation-criterias.')->group(function () {
        Route::get('/', [InvestationCriteriaController::class, 'index'])->name('index');
        Route::get('/create', [InvestationCriteriaController::class, 'create'])->name('create');
        Route::post('/', [InvestationCriteriaController::class, 'store'])->name('store');
        Route::get('/{investationCriteria}/edit', [InvestationCriteriaController::class, 'edit'])->name('edit');
        Route::put('/{investationCriteria}', [InvestationCriteriaController::class, 'update'])->name('update');
        Route::delete('/{investationCriteria}', [InvestationCriteriaController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('investattion-categories')->name('investattion-categories.')->group(function () {
        Route::get('/', [InvestattionCategoryController::class, 'index'])->name('index');
        Route::get('/create', [InvestattionCategoryController::class, 'create'])->name('create');
        Route::post('/', [InvestattionCategoryController::class, 'store'])->name('store');
        Route::get('/{investattionCategory}/edit', [InvestattionCategoryController::class, 'edit'])->name('edit');
        Route::put('/{investattionCategory}', [InvestattionCategoryController::class, 'update'])->name('update');
        Route::delete('/{investattionCategory}', [InvestattionCategoryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('rkap')->name('rkap.')->group(function () {
        Route::get('/', [RKAPController::class, 'index'])->name('index');
        Route::get('/create', [RKAPController::class, 'create'])->name('create');
        Route::post('/', [RKAPController::class, 'store'])->name('store');
        Route::get('/{rkap}/edit', [RKAPController::class, 'edit'])->name('edit');
        Route::put('/{rkap}', [RKAPController::class, 'update'])->name('update');
        Route::delete('/{rkap}', [RKAPController::class, 'destroy'])->name('destroy');
    });
});
