<?php

use App\Http\Controllers\Erkap\CompanyTargetController;
use App\Http\Controllers\Erkap\CostElementCategoryController;
use App\Http\Controllers\Erkap\CostElementController;
use App\Http\Controllers\Erkap\DashboardController as ErkapDashboardController;
use App\Http\Controllers\Erkap\DepartmentRiskStrategyController;
use App\Http\Controllers\Erkap\DepartmentTargetController;
use App\Http\Controllers\Erkap\InvestattionCategoryController;
use App\Http\Controllers\Erkap\InvestationCriteriaController;
use App\Http\Controllers\Erkap\InvestationTypeController;
use App\Http\Controllers\Erkap\RatingCriteriaController;
use App\Http\Controllers\Erkap\RiskAnalysisController;
use App\Http\Controllers\Erkap\RiskIdentificationController;
use App\Http\Controllers\Erkap\RiskIdentificationImpactController;
use App\Http\Controllers\Erkap\RiskIdentificationReasonController;
use App\Http\Controllers\Erkap\RiskRankingController;
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

    Route::prefix('company-targets')->name('company-targets.')->group(function () {
        Route::get('/', [CompanyTargetController::class, 'index'])->name('index');
        Route::get('/create', [CompanyTargetController::class, 'create'])->name('create');
        Route::post('/', [CompanyTargetController::class, 'store'])->name('store');
        Route::get('/{companyTarget}/edit', [CompanyTargetController::class, 'edit'])->name('edit');
        Route::put('/{companyTarget}', [CompanyTargetController::class, 'update'])->name('update');
        Route::delete('/{companyTarget}', [CompanyTargetController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('department-targets')->name('department-targets.')->group(function () {
        Route::get('/', [DepartmentTargetController::class, 'index'])->name('index');
        Route::get('/create', [DepartmentTargetController::class, 'create'])->name('create');
        Route::post('/', [DepartmentTargetController::class, 'store'])->name('store');
        Route::get('/{departmentTarget}/edit', [DepartmentTargetController::class, 'edit'])->name('edit');
        Route::put('/{departmentTarget}', [DepartmentTargetController::class, 'update'])->name('update');
        Route::delete('/{departmentTarget}', [DepartmentTargetController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('risk-identifications')->name('risk-identifications.')->group(function () {
        Route::get('/', [RiskIdentificationController::class, 'index'])->name('index');
        Route::get('/create', [RiskIdentificationController::class, 'create'])->name('create');
        Route::post('/', [RiskIdentificationController::class, 'store'])->name('store');
        Route::get('/{riskIdentification}/edit', [RiskIdentificationController::class, 'edit'])->name('edit');
        Route::put('/{riskIdentification}', [RiskIdentificationController::class, 'update'])->name('update');
        Route::delete('/{riskIdentification}', [RiskIdentificationController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('risk-identification-reasons')->name('risk-identification-reasons.')->group(function () {
        Route::get('/', [RiskIdentificationReasonController::class, 'index'])->name('index');
        Route::get('/create', [RiskIdentificationReasonController::class, 'create'])->name('create');
        Route::post('/', [RiskIdentificationReasonController::class, 'store'])->name('store');
        Route::get('/{riskIdentificationReason}/edit', [RiskIdentificationReasonController::class, 'edit'])->name('edit');
        Route::put('/{riskIdentificationReason}', [RiskIdentificationReasonController::class, 'update'])->name('update');
        Route::delete('/{riskIdentificationReason}', [RiskIdentificationReasonController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('risk-identification-impacts')->name('risk-identification-impacts.')->group(function () {
        Route::get('/', [RiskIdentificationImpactController::class, 'index'])->name('index');
        Route::get('/create', [RiskIdentificationImpactController::class, 'create'])->name('create');
        Route::post('/', [RiskIdentificationImpactController::class, 'store'])->name('store');
        Route::get('/{riskIdentificationImpact}/edit', [RiskIdentificationImpactController::class, 'edit'])->name('edit');
        Route::put('/{riskIdentificationImpact}', [RiskIdentificationImpactController::class, 'update'])->name('update');
        Route::delete('/{riskIdentificationImpact}', [RiskIdentificationImpactController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('risk-analysis')->name('risk-analysis.')->group(function () {
        Route::get('/', [RiskAnalysisController::class, 'index'])->name('index');
        Route::get('/create', [RiskAnalysisController::class, 'create'])->name('create');
        Route::post('/', [RiskAnalysisController::class, 'store'])->name('store');
        Route::get('/{riskAnalysis}/edit', [RiskAnalysisController::class, 'edit'])->name('edit');
        Route::put('/{riskAnalysis}', [RiskAnalysisController::class, 'update'])->name('update');
        Route::delete('/{riskAnalysis}', [RiskAnalysisController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('risk-rankings')->name('risk-rankings.')->group(function () {
        Route::get('/', [RiskRankingController::class, 'index'])->name('index');
        Route::get('/create', [RiskRankingController::class, 'create'])->name('create');
        Route::post('/', [RiskRankingController::class, 'store'])->name('store');
        Route::get('/{riskRanking}/edit', [RiskRankingController::class, 'edit'])->name('edit');
        Route::put('/{riskRanking}', [RiskRankingController::class, 'update'])->name('update');
        Route::delete('/{riskRanking}', [RiskRankingController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('department-risk-strategies')->name('department-risk-strategies.')->group(function () {
        Route::get('/', [DepartmentRiskStrategyController::class, 'index'])->name('index');
        Route::get('/create', [DepartmentRiskStrategyController::class, 'create'])->name('create');
        Route::post('/', [DepartmentRiskStrategyController::class, 'store'])->name('store');
        Route::get('/{departmentRiskStrategy}/edit', [DepartmentRiskStrategyController::class, 'edit'])->name('edit');
        Route::put('/{departmentRiskStrategy}', [DepartmentRiskStrategyController::class, 'update'])->name('update');
        Route::delete('/{departmentRiskStrategy}', [DepartmentRiskStrategyController::class, 'destroy'])->name('destroy');
    });
});
