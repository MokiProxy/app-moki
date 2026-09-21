<?php

use App\Http\Controllers\Erkap\ApprovalController;
use App\Http\Controllers\Erkap\AuditLogController;
use App\Http\Controllers\Erkap\BudgetRealizationController;
use App\Http\Controllers\Erkap\CompanyTargetController;
use App\Http\Controllers\Erkap\CostCenterController;
use App\Http\Controllers\Erkap\CostElementCategoryController;
use App\Http\Controllers\Erkap\CostElementController;
use App\Http\Controllers\Erkap\BudgetCapexController;
use App\Http\Controllers\Erkap\ChartOfAccountController;
use App\Http\Controllers\Erkap\DashboardController as ErkapDashboardController;
use App\Http\Controllers\Erkap\DepartmentRiskStrategyController;
use App\Http\Controllers\Erkap\DepartmentTargetController;
use App\Http\Controllers\Erkap\ExpensePlanController;
use App\Http\Controllers\Erkap\InvestattionCategoryController;
use App\Http\Controllers\Erkap\InvestationCriteriaController;
use App\Http\Controllers\Erkap\InvestationTypeController;
use App\Http\Controllers\Erkap\InvestmentPlanController;
use App\Http\Controllers\Erkap\PerformanceScorecardController;
use App\Http\Controllers\Erkap\ProfitLossController;
use App\Http\Controllers\Erkap\ProgramRealizationController;
use App\Http\Controllers\Erkap\RatingCriteriaController;
use App\Http\Controllers\Erkap\RiskAssessmentMonthlyController;
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
use App\Http\Controllers\Erkap\RevenuePlanController;
use App\Http\Controllers\Erkap\RoutineCostController;
use App\Http\Controllers\Erkap\WorkProgramController;
use Illuminate\Support\Facades\Route;

Route::prefix("erkap")->name("erkap.")->group(function () {
    Route::get("/", [ErkapDashboardController::class, 'index'])->middleware('permission:erkap.menu')->name("index");
    Route::get("/dashboard/risk-detail", [ErkapDashboardController::class, 'riskDetail'])->middleware('permission:erkap.menu')->name("dashboard.risk-detail");
    Route::get("/dashboard/budget-detail", [ErkapDashboardController::class, 'budgetDetail'])->middleware('permission:erkap.menu')->name("dashboard.budget-detail");
    Route::get("/dashboard/export-budget", [ErkapDashboardController::class, 'exportBudget'])->middleware('permission:erkap.menu')->name("dashboard.export-budget");
    Route::get("/dashboard/export-budget-pdf", [ErkapDashboardController::class, 'exportBudgetPdf'])->middleware('permission:erkap.menu')->name("dashboard.export-budget-pdf");

    Route::prefix('cost-element-categories')->name('cost-element-categories.')->group(function () {
        Route::get('/', [CostElementCategoryController::class, 'index'])->middleware('permission:erkap.cost-element-categories.view')->name('index');
        Route::get('/create', [CostElementCategoryController::class, 'create'])->middleware('permission:erkap.cost-element-categories.create')->name('create');
        Route::post('/', [CostElementCategoryController::class, 'store'])->middleware('permission:erkap.cost-element-categories.create')->name('store');
        Route::get('/{costElementCategory}/edit', [CostElementCategoryController::class, 'edit'])->middleware('permission:erkap.cost-element-categories.edit')->name('edit');
        Route::put('/{costElementCategory}', [CostElementCategoryController::class, 'update'])->middleware('permission:erkap.cost-element-categories.edit')->name('update');
        Route::delete('/{costElementCategory}', [CostElementCategoryController::class, 'destroy'])->middleware('permission:erkap.cost-element-categories.delete')->name('destroy');
    });

    Route::prefix('cost-elements')->name('cost-elements.')->group(function () {
        Route::get('/', [CostElementController::class, 'index'])->middleware('permission:erkap.cost-elements.view')->name('index');
        Route::get('/create', [CostElementController::class, 'create'])->middleware('permission:erkap.cost-elements.create')->name('create');
        Route::post('/', [CostElementController::class, 'store'])->middleware('permission:erkap.cost-elements.create')->name('store');
        Route::get('/{costElement}/edit', [CostElementController::class, 'edit'])->middleware('permission:erkap.cost-elements.edit')->name('edit');
        Route::put('/{costElement}', [CostElementController::class, 'update'])->middleware('permission:erkap.cost-elements.edit')->name('update');
        Route::delete('/{costElement}', [CostElementController::class, 'destroy'])->middleware('permission:erkap.cost-elements.delete')->name('destroy');
    });

    Route::prefix('chart-of-accounts')->name('chart-of-accounts.')->group(function () {
        Route::get('/', [ChartOfAccountController::class, 'index'])->middleware('permission:erkap.chart-of-accounts.view')->name('index');
        Route::post('/sync', [ChartOfAccountController::class, 'sync'])->middleware('permission:erkap.chart-of-accounts.create')->name('sync');
        Route::get('/{chartOfAccount}', [ChartOfAccountController::class, 'show'])->middleware('permission:erkap.chart-of-accounts.view')->name('show');
    });

    Route::prefix('risk-appetites')->name('risk-appetites.')->group(function () {
        Route::get('/', [RiskAppetiteController::class, 'index'])->middleware('permission:erkap.risk-appetites.view')->name('index');
        Route::get('/create', [RiskAppetiteController::class, 'create'])->middleware('permission:erkap.risk-appetites.create')->name('create');
        Route::post('/', [RiskAppetiteController::class, 'store'])->middleware('permission:erkap.risk-appetites.create')->name('store');
        Route::get('/{riskAppetite}/edit', [RiskAppetiteController::class, 'edit'])->middleware('permission:erkap.risk-appetites.edit')->name('edit');
        Route::put('/{riskAppetite}', [RiskAppetiteController::class, 'update'])->middleware('permission:erkap.risk-appetites.edit')->name('update');
        Route::delete('/{riskAppetite}', [RiskAppetiteController::class, 'destroy'])->middleware('permission:erkap.risk-appetites.delete')->name('destroy');
    });

    Route::prefix('risk-taxonomies')->name('risk-taxonomies.')->group(function () {
        Route::get('/', [RiskTaxonomyController::class, 'index'])->middleware('permission:erkap.risk-taxonomies.view')->name('index');
        Route::get('/create', [RiskTaxonomyController::class, 'create'])->middleware('permission:erkap.risk-taxonomies.create')->name('create');
        Route::post('/', [RiskTaxonomyController::class, 'store'])->middleware('permission:erkap.risk-taxonomies.create')->name('store');
        Route::get('/{riskTaxonomy}/edit', [RiskTaxonomyController::class, 'edit'])->middleware('permission:erkap.risk-taxonomies.edit')->name('edit');
        Route::put('/{riskTaxonomy}', [RiskTaxonomyController::class, 'update'])->middleware('permission:erkap.risk-taxonomies.edit')->name('update');
        Route::delete('/{riskTaxonomy}', [RiskTaxonomyController::class, 'destroy'])->middleware('permission:erkap.risk-taxonomies.delete')->name('destroy');
    });

    Route::prefix('risk-types')->name('risk-types.')->group(function () {
        Route::get('/', [RiskTypeController::class, 'index'])->middleware('permission:erkap.risk-types.view')->name('index');
        Route::get('/create', [RiskTypeController::class, 'create'])->middleware('permission:erkap.risk-types.create')->name('create');
        Route::post('/', [RiskTypeController::class, 'store'])->middleware('permission:erkap.risk-types.create')->name('store');
        Route::get('/{riskType}/edit', [RiskTypeController::class, 'edit'])->middleware('permission:erkap.risk-types.edit')->name('edit');
        Route::put('/{riskType}', [RiskTypeController::class, 'update'])->middleware('permission:erkap.risk-types.edit')->name('update');
        Route::delete('/{riskType}', [RiskTypeController::class, 'destroy'])->middleware('permission:erkap.risk-types.delete')->name('destroy');
    });

    Route::prefix('rating-criterias')->name('rating-criterias.')->group(function () {
        Route::get('/', [RatingCriteriaController::class, 'index'])->middleware('permission:erkap.rating-criterias.view')->name('index');
        Route::get('/create', [RatingCriteriaController::class, 'create'])->middleware('permission:erkap.rating-criterias.create')->name('create');
        Route::post('/', [RatingCriteriaController::class, 'store'])->middleware('permission:erkap.rating-criterias.create')->name('store');
        Route::get('/{ratingCriteria}/edit', [RatingCriteriaController::class, 'edit'])->middleware('permission:erkap.rating-criterias.edit')->name('edit');
        Route::put('/{ratingCriteria}', [RatingCriteriaController::class, 'update'])->middleware('permission:erkap.rating-criterias.edit')->name('update');
        Route::delete('/{ratingCriteria}', [RatingCriteriaController::class, 'destroy'])->middleware('permission:erkap.rating-criterias.delete')->name('destroy');
    });

    Route::prefix('risk-scales')->name('risk-scales.')->group(function () {
        Route::get('/', [RiskScaleController::class, 'index'])->middleware('permission:erkap.risk-scales.view')->name('index');
        Route::get('/create', [RiskScaleController::class, 'create'])->middleware('permission:erkap.risk-scales.create')->name('create');
        Route::post('/', [RiskScaleController::class, 'store'])->middleware('permission:erkap.risk-scales.create')->name('store');
        Route::get('/{riskScale}/edit', [RiskScaleController::class, 'edit'])->middleware('permission:erkap.risk-scales.edit')->name('edit');
        Route::put('/{riskScale}', [RiskScaleController::class, 'update'])->middleware('permission:erkap.risk-scales.edit')->name('update');
        Route::delete('/{riskScale}', [RiskScaleController::class, 'destroy'])->middleware('permission:erkap.risk-scales.delete')->name('destroy');
    });

    Route::prefix('risk-probabilities')->name('risk-probabilities.')->group(function () {
        Route::get('/', [RiskProbabilityController::class, 'index'])->middleware('permission:erkap.risk-probabilities.view')->name('index');
        Route::get('/create', [RiskProbabilityController::class, 'create'])->middleware('permission:erkap.risk-probabilities.create')->name('create');
        Route::post('/', [RiskProbabilityController::class, 'store'])->middleware('permission:erkap.risk-probabilities.create')->name('store');
        Route::get('/{riskProbability}/edit', [RiskProbabilityController::class, 'edit'])->middleware('permission:erkap.risk-probabilities.edit')->name('edit');
        Route::put('/{riskProbability}', [RiskProbabilityController::class, 'update'])->middleware('permission:erkap.risk-probabilities.edit')->name('update');
        Route::delete('/{riskProbability}', [RiskProbabilityController::class, 'destroy'])->middleware('permission:erkap.risk-probabilities.delete')->name('destroy');
    });

    Route::prefix('risk-impacts')->name('risk-impacts.')->group(function () {
        Route::get('/', [RiskImpactController::class, 'index'])->middleware('permission:erkap.risk-impacts.view')->name('index');
        Route::get('/create', [RiskImpactController::class, 'create'])->middleware('permission:erkap.risk-impacts.create')->name('create');
        Route::post('/', [RiskImpactController::class, 'store'])->middleware('permission:erkap.risk-impacts.create')->name('store');
        Route::get('/{riskImpact}/edit', [RiskImpactController::class, 'edit'])->middleware('permission:erkap.risk-impacts.edit')->name('edit');
        Route::put('/{riskImpact}', [RiskImpactController::class, 'update'])->middleware('permission:erkap.risk-impacts.edit')->name('update');
        Route::delete('/{riskImpact}', [RiskImpactController::class, 'destroy'])->middleware('permission:erkap.risk-impacts.delete')->name('destroy');
    });

    Route::prefix('risk-score-levels')->name('risk-score-levels.')->group(function () {
        Route::get('/', [RiskScoreLevelController::class, 'index'])->middleware('permission:erkap.risk-score-levels.view')->name('index');
        Route::get('/create', [RiskScoreLevelController::class, 'create'])->middleware('permission:erkap.risk-score-levels.create')->name('create');
        Route::post('/', [RiskScoreLevelController::class, 'store'])->middleware('permission:erkap.risk-score-levels.create')->name('store');
        Route::get('/{riskScoreLevel}/edit', [RiskScoreLevelController::class, 'edit'])->middleware('permission:erkap.risk-score-levels.edit')->name('edit');
        Route::put('/{riskScoreLevel}', [RiskScoreLevelController::class, 'update'])->middleware('permission:erkap.risk-score-levels.edit')->name('update');
        Route::delete('/{riskScoreLevel}', [RiskScoreLevelController::class, 'destroy'])->middleware('permission:erkap.risk-score-levels.delete')->name('destroy');
    });

    Route::prefix('investation-types')->name('investation-types.')->group(function () {
        Route::get('/', [InvestationTypeController::class, 'index'])->middleware('permission:erkap.investation-types.view')->name('index');
        Route::get('/create', [InvestationTypeController::class, 'create'])->middleware('permission:erkap.investation-types.create')->name('create');
        Route::post('/', [InvestationTypeController::class, 'store'])->middleware('permission:erkap.investation-types.create')->name('store');
        Route::get('/{investationType}/edit', [InvestationTypeController::class, 'edit'])->middleware('permission:erkap.investation-types.edit')->name('edit');
        Route::put('/{investationType}', [InvestationTypeController::class, 'update'])->middleware('permission:erkap.investation-types.edit')->name('update');
        Route::delete('/{investationType}', [InvestationTypeController::class, 'destroy'])->middleware('permission:erkap.investation-types.delete')->name('destroy');
    });

    Route::prefix('investation-criterias')->name('investation-criterias.')->group(function () {
        Route::get('/', [InvestationCriteriaController::class, 'index'])->middleware('permission:erkap.investation-criterias.view')->name('index');
        Route::get('/create', [InvestationCriteriaController::class, 'create'])->middleware('permission:erkap.investation-criterias.create')->name('create');
        Route::post('/', [InvestationCriteriaController::class, 'store'])->middleware('permission:erkap.investation-criterias.create')->name('store');
        Route::get('/{investationCriteria}/edit', [InvestationCriteriaController::class, 'edit'])->middleware('permission:erkap.investation-criterias.edit')->name('edit');
        Route::put('/{investationCriteria}', [InvestationCriteriaController::class, 'update'])->middleware('permission:erkap.investation-criterias.edit')->name('update');
        Route::delete('/{investationCriteria}', [InvestationCriteriaController::class, 'destroy'])->middleware('permission:erkap.investation-criterias.delete')->name('destroy');
    });

    Route::prefix('investattion-categories')->name('investattion-categories.')->group(function () {
        Route::get('/', [InvestattionCategoryController::class, 'index'])->middleware('permission:erkap.investattion-categories.view')->name('index');
        Route::get('/create', [InvestattionCategoryController::class, 'create'])->middleware('permission:erkap.investattion-categories.create')->name('create');
        Route::post('/', [InvestattionCategoryController::class, 'store'])->middleware('permission:erkap.investattion-categories.create')->name('store');
        Route::get('/{investattionCategory}/edit', [InvestattionCategoryController::class, 'edit'])->middleware('permission:erkap.investattion-categories.edit')->name('edit');
        Route::put('/{investattionCategory}', [InvestattionCategoryController::class, 'update'])->middleware('permission:erkap.investattion-categories.edit')->name('update');
        Route::delete('/{investattionCategory}', [InvestattionCategoryController::class, 'destroy'])->middleware('permission:erkap.investattion-categories.delete')->name('destroy');
    });

    Route::prefix('rkap')->name('rkap.')->group(function () {
        Route::get('/', [RKAPController::class, 'index'])->middleware('permission:erkap.rkap.view')->name('index');
        Route::get('/create', [RKAPController::class, 'create'])->middleware('permission:erkap.rkap.create')->name('create');
        Route::post('/', [RKAPController::class, 'store'])->middleware('permission:erkap.rkap.create')->name('store');
        Route::get('/{rkap}/edit', [RKAPController::class, 'edit'])->middleware('permission:erkap.rkap.edit')->name('edit');
        Route::put('/{rkap}', [RKAPController::class, 'update'])->middleware('permission:erkap.rkap.edit')->name('update');
        Route::delete('/{rkap}', [RKAPController::class, 'destroy'])->middleware('permission:erkap.rkap.delete')->name('destroy');
        Route::post('/{rkap}/submit', [RKAPController::class, 'submit'])->middleware('permission:erkap.rkap.submit')->name('submit');
    });

    Route::prefix('company-targets')->name('company-targets.')->group(function () {
        Route::get('/', [CompanyTargetController::class, 'index'])->middleware('permission:erkap.company-targets.view')->name('index');
        Route::get('/create', [CompanyTargetController::class, 'create'])->middleware('permission:erkap.company-targets.create')->name('create');
        Route::post('/', [CompanyTargetController::class, 'store'])->middleware('permission:erkap.company-targets.create')->name('store');
        Route::get('/{companyTarget}/edit', [CompanyTargetController::class, 'edit'])->middleware('permission:erkap.company-targets.edit')->name('edit');
        Route::put('/{companyTarget}', [CompanyTargetController::class, 'update'])->middleware('permission:erkap.company-targets.edit')->name('update');
        Route::delete('/{companyTarget}', [CompanyTargetController::class, 'destroy'])->middleware('permission:erkap.company-targets.delete')->name('destroy');
    });

    Route::prefix('department-targets')->name('department-targets.')->group(function () {
        Route::get('/', [DepartmentTargetController::class, 'index'])->middleware('permission:erkap.department-targets.view')->name('index');
        Route::get('/create', [DepartmentTargetController::class, 'create'])->middleware('permission:erkap.department-targets.create')->name('create');
        Route::post('/', [DepartmentTargetController::class, 'store'])->middleware('permission:erkap.department-targets.create')->name('store');
        Route::get('/{departmentTarget}/edit', [DepartmentTargetController::class, 'edit'])->middleware('permission:erkap.department-targets.edit')->name('edit');
        Route::put('/{departmentTarget}', [DepartmentTargetController::class, 'update'])->middleware('permission:erkap.department-targets.edit')->name('update');
        Route::delete('/{departmentTarget}', [DepartmentTargetController::class, 'destroy'])->middleware('permission:erkap.department-targets.delete')->name('destroy');
    });

    Route::prefix('risk-identifications')->name('risk-identifications.')->group(function () {
        Route::get('/', [RiskIdentificationController::class, 'index'])->middleware('permission:erkap.risk-identifications.view')->name('index');
        Route::get('/export', [RiskIdentificationController::class, 'export'])->middleware('permission:erkap.risk-identifications.view')->name('export');
        Route::get('/export-pdf', [RiskIdentificationController::class, 'exportPdf'])->middleware('permission:erkap.risk-identifications.view')->name('export-pdf');
        Route::get('/create', [RiskIdentificationController::class, 'create'])->middleware('permission:erkap.risk-identifications.create')->name('create');
        Route::post('/', [RiskIdentificationController::class, 'store'])->middleware('permission:erkap.risk-identifications.create')->name('store');
        Route::get('/{riskIdentification}/edit', [RiskIdentificationController::class, 'edit'])->middleware('permission:erkap.risk-identifications.edit')->name('edit');
        Route::put('/{riskIdentification}', [RiskIdentificationController::class, 'update'])->middleware('permission:erkap.risk-identifications.edit')->name('update');
        Route::delete('/{riskIdentification}', [RiskIdentificationController::class, 'destroy'])->middleware('permission:erkap.risk-identifications.delete')->name('destroy');
    });

    Route::prefix('risk-identification-reasons')->name('risk-identification-reasons.')->group(function () {
        Route::get('/', [RiskIdentificationReasonController::class, 'index'])->middleware('permission:erkap.risk-identification-reasons.view')->name('index');
        Route::get('/create', [RiskIdentificationReasonController::class, 'create'])->middleware('permission:erkap.risk-identification-reasons.create')->name('create');
        Route::post('/', [RiskIdentificationReasonController::class, 'store'])->middleware('permission:erkap.risk-identification-reasons.create')->name('store');
        Route::get('/{riskIdentificationReason}/edit', [RiskIdentificationReasonController::class, 'edit'])->middleware('permission:erkap.risk-identification-reasons.edit')->name('edit');
        Route::put('/{riskIdentificationReason}', [RiskIdentificationReasonController::class, 'update'])->middleware('permission:erkap.risk-identification-reasons.edit')->name('update');
        Route::delete('/{riskIdentificationReason}', [RiskIdentificationReasonController::class, 'destroy'])->middleware('permission:erkap.risk-identification-reasons.delete')->name('destroy');
    });

    Route::prefix('risk-identification-impacts')->name('risk-identification-impacts.')->group(function () {
        Route::get('/', [RiskIdentificationImpactController::class, 'index'])->middleware('permission:erkap.risk-identification-impacts.view')->name('index');
        Route::get('/create', [RiskIdentificationImpactController::class, 'create'])->middleware('permission:erkap.risk-identification-impacts.create')->name('create');
        Route::post('/', [RiskIdentificationImpactController::class, 'store'])->middleware('permission:erkap.risk-identification-impacts.create')->name('store');
        Route::get('/{riskIdentificationImpact}/edit', [RiskIdentificationImpactController::class, 'edit'])->middleware('permission:erkap.risk-identification-impacts.edit')->name('edit');
        Route::put('/{riskIdentificationImpact}', [RiskIdentificationImpactController::class, 'update'])->middleware('permission:erkap.risk-identification-impacts.edit')->name('update');
        Route::delete('/{riskIdentificationImpact}', [RiskIdentificationImpactController::class, 'destroy'])->middleware('permission:erkap.risk-identification-impacts.delete')->name('destroy');
    });

    Route::prefix('risk-analysis')->name('risk-analysis.')->group(function () {
        Route::get('/', [RiskAnalysisController::class, 'index'])->middleware('permission:erkap.risk-analysis.view')->name('index');
        Route::get('/create', [RiskAnalysisController::class, 'create'])->middleware('permission:erkap.risk-analysis.create')->name('create');
        Route::post('/', [RiskAnalysisController::class, 'store'])->middleware('permission:erkap.risk-analysis.create')->name('store');
        Route::get('/get-score-level/{probabilityId}/{impactId}', [RiskAnalysisController::class, 'getScoreLevel'])->middleware('permission:erkap.risk-analysis.view|erkap.risk-analysis.create|erkap.risk-analysis.edit')->name('get-score-level');
        Route::get('/{riskAnalysis}/edit', [RiskAnalysisController::class, 'edit'])->middleware('permission:erkap.risk-analysis.edit')->name('edit');
        Route::put('/{riskAnalysis}', [RiskAnalysisController::class, 'update'])->middleware('permission:erkap.risk-analysis.edit')->name('update');
        Route::delete('/{riskAnalysis}', [RiskAnalysisController::class, 'destroy'])->middleware('permission:erkap.risk-analysis.delete')->name('destroy');
    });

    Route::prefix('risk-rankings')->name('risk-rankings.')->group(function () {
        Route::get('/', [RiskRankingController::class, 'index'])->middleware('permission:erkap.risk-rankings.view')->name('index');
        Route::get('/create', [RiskRankingController::class, 'create'])->middleware('permission:erkap.risk-rankings.create')->name('create');
        Route::post('/', [RiskRankingController::class, 'store'])->middleware('permission:erkap.risk-rankings.create')->name('store');
        Route::get('/{riskRanking}/edit', [RiskRankingController::class, 'edit'])->middleware('permission:erkap.risk-rankings.edit')->name('edit');
        Route::put('/{riskRanking}', [RiskRankingController::class, 'update'])->middleware('permission:erkap.risk-rankings.edit')->name('update');
        Route::delete('/{riskRanking}', [RiskRankingController::class, 'destroy'])->middleware('permission:erkap.risk-rankings.delete')->name('destroy');
    });

    Route::prefix('department-risk-strategies')->name('department-risk-strategies.')->group(function () {
        Route::get('/', [DepartmentRiskStrategyController::class, 'index'])->middleware('permission:erkap.department-risk-strategies.view')->name('index');
        Route::get('/create', [DepartmentRiskStrategyController::class, 'create'])->middleware('permission:erkap.department-risk-strategies.create')->name('create');
        Route::post('/', [DepartmentRiskStrategyController::class, 'store'])->middleware('permission:erkap.department-risk-strategies.create')->name('store');
        Route::get('/{departmentRiskStrategy}/edit', [DepartmentRiskStrategyController::class, 'edit'])->middleware('permission:erkap.department-risk-strategies.edit')->name('edit');
        Route::put('/{departmentRiskStrategy}', [DepartmentRiskStrategyController::class, 'update'])->middleware('permission:erkap.department-risk-strategies.edit')->name('update');
        Route::delete('/{departmentRiskStrategy}', [DepartmentRiskStrategyController::class, 'destroy'])->middleware('permission:erkap.department-risk-strategies.delete')->name('destroy');
    });

    Route::prefix('work-programs')->name('work-programs.')->group(function () {
        Route::get('/', [WorkProgramController::class, 'index'])->middleware('permission:erkap.work-programs.view')->name('index');
        Route::get('/export', [WorkProgramController::class, 'export'])->middleware('permission:erkap.work-programs.view')->name('export');
        Route::get('/export-pdf', [WorkProgramController::class, 'exportPdf'])->middleware('permission:erkap.work-programs.view')->name('export-pdf');
        Route::get('/create', [WorkProgramController::class, 'create'])->middleware('permission:erkap.work-programs.create')->name('create');
        Route::post('/', [WorkProgramController::class, 'store'])->middleware('permission:erkap.work-programs.create')->name('store');
        Route::get('/{workProgram}/edit', [WorkProgramController::class, 'edit'])->middleware('permission:erkap.work-programs.edit')->name('edit');
        Route::put('/{workProgram}', [WorkProgramController::class, 'update'])->middleware('permission:erkap.work-programs.edit')->name('update');
        Route::delete('/{workProgram}', [WorkProgramController::class, 'destroy'])->middleware('permission:erkap.work-programs.delete')->name('destroy');
        Route::post('/submit-batch', [WorkProgramController::class, 'submitBatch'])->middleware('permission:erkap.work-programs.submit')->name('submit-batch');
        Route::post('/{workProgram}/submit', [WorkProgramController::class, 'submit'])->middleware('permission:erkap.work-programs.submit')->name('submit');
    });

    Route::prefix('routine-costs')->name('routine-costs.')->group(function () {
        Route::get('/', [RoutineCostController::class, 'index'])->middleware('permission:erkap.routine-costs.view')->name('index');
        Route::get('/export', [RoutineCostController::class, 'export'])->middleware('permission:erkap.routine-costs.view')->name('export');
        Route::get('/export-pdf', [RoutineCostController::class, 'exportPdf'])->middleware('permission:erkap.routine-costs.view')->name('export-pdf');
        Route::get('/consolidate', [RoutineCostController::class, 'consolidate'])->middleware('permission:erkap.routine-costs.view')->name('consolidate');
        Route::get('/create', [RoutineCostController::class, 'create'])->middleware('permission:erkap.routine-costs.create')->name('create');
        Route::post('/', [RoutineCostController::class, 'store'])->middleware('permission:erkap.routine-costs.create')->name('store');
        Route::get('/{routineCost}/edit', [RoutineCostController::class, 'edit'])->middleware('permission:erkap.routine-costs.edit')->name('edit');
        Route::put('/{routineCost}', [RoutineCostController::class, 'update'])->middleware('permission:erkap.routine-costs.edit')->name('update');
        Route::delete('/{routineCost}', [RoutineCostController::class, 'destroy'])->middleware('permission:erkap.routine-costs.delete')->name('destroy');
        Route::post('/submit-batch', [RoutineCostController::class, 'submitBatch'])->middleware('permission:erkap.routine-costs.submit')->name('submit-batch');
        Route::post('/{routineCost}/submit', [RoutineCostController::class, 'submit'])->middleware('permission:erkap.routine-costs.submit')->name('submit');
    });

    Route::prefix('cost-centers')->name('cost-centers.')->group(function () {
        Route::get('/', [CostCenterController::class, 'index'])->middleware('permission:erkap.cost-centers.view')->name('index');
        Route::get('/create', [CostCenterController::class, 'create'])->middleware('permission:erkap.cost-centers.create')->name('create');
        Route::post('/', [CostCenterController::class, 'store'])->middleware('permission:erkap.cost-centers.create')->name('store');
        Route::get('/{costCenter}/edit', [CostCenterController::class, 'edit'])->middleware('permission:erkap.cost-centers.edit')->name('edit');
        Route::put('/{costCenter}', [CostCenterController::class, 'update'])->middleware('permission:erkap.cost-centers.edit')->name('update');
        Route::delete('/{costCenter}', [CostCenterController::class, 'destroy'])->middleware('permission:erkap.cost-centers.delete')->name('destroy');
    });

    Route::prefix('investment-plans')->name('investment-plans.')->group(function () {
        Route::get('/', [InvestmentPlanController::class, 'index'])->middleware('permission:erkap.investment-plans.view')->name('index');
        Route::get('/export', [InvestmentPlanController::class, 'export'])->middleware('permission:erkap.investment-plans.view')->name('export');
        Route::get('/create', [InvestmentPlanController::class, 'create'])->middleware('permission:erkap.investment-plans.create')->name('create');
        Route::post('/', [InvestmentPlanController::class, 'store'])->middleware('permission:erkap.investment-plans.create')->name('store');
        Route::get('/{investmentPlan}/edit', [InvestmentPlanController::class, 'edit'])->middleware('permission:erkap.investment-plans.edit')->name('edit');
        Route::put('/{investmentPlan}', [InvestmentPlanController::class, 'update'])->middleware('permission:erkap.investment-plans.edit')->name('update');
        Route::delete('/{investmentPlan}', [InvestmentPlanController::class, 'destroy'])->middleware('permission:erkap.investment-plans.delete')->name('destroy');
        Route::post('/submit-batch', [InvestmentPlanController::class, 'submitBatch'])->middleware('permission:erkap.investment-plans.submit')->name('submit-batch');
        Route::post('/{investmentPlan}/submit', [InvestmentPlanController::class, 'submit'])->middleware('permission:erkap.investment-plans.submit')->name('submit');
    });

    Route::prefix('budget-capex')->name('budget-capex.')->group(function () {
        Route::get('/', [BudgetCapexController::class, 'index'])->middleware('permission:erkap.budget-capex.view')->name('index');
        Route::post('/consolidate', [BudgetCapexController::class, 'consolidate'])->middleware('permission:erkap.budget-capex.create')->name('consolidate');
        Route::get('/{budgetCapex}', [BudgetCapexController::class, 'show'])->middleware('permission:erkap.budget-capex.view')->name('show');
        Route::put('/{budgetCapex}', [BudgetCapexController::class, 'update'])->middleware('permission:erkap.budget-capex.edit')->name('update');
    });

    Route::prefix('revenue-plans')->name('revenue-plans.')->group(function () {
        Route::get('/', [RevenuePlanController::class, 'index'])->middleware('permission:erkap.revenue-plans.view')->name('index');
        Route::get('/create', [RevenuePlanController::class, 'create'])->middleware('permission:erkap.revenue-plans.create')->name('create');
        Route::post('/', [RevenuePlanController::class, 'store'])->middleware('permission:erkap.revenue-plans.create')->name('store');
        Route::get('/{revenuePlan}/edit', [RevenuePlanController::class, 'edit'])->middleware('permission:erkap.revenue-plans.edit')->name('edit');
        Route::put('/{revenuePlan}', [RevenuePlanController::class, 'update'])->middleware('permission:erkap.revenue-plans.edit')->name('update');
        Route::delete('/{revenuePlan}', [RevenuePlanController::class, 'destroy'])->middleware('permission:erkap.revenue-plans.delete')->name('destroy');
    });

    Route::prefix('expense-plans')->name('expense-plans.')->group(function () {
        Route::get('/', [ExpensePlanController::class, 'index'])->middleware('permission:erkap.expense-plans.view')->name('index');
        Route::get('/create', [ExpensePlanController::class, 'create'])->middleware('permission:erkap.expense-plans.create')->name('create');
        Route::post('/', [ExpensePlanController::class, 'store'])->middleware('permission:erkap.expense-plans.create')->name('store');
        Route::get('/{expensePlan}/edit', [ExpensePlanController::class, 'edit'])->middleware('permission:erkap.expense-plans.edit')->name('edit');
        Route::put('/{expensePlan}', [ExpensePlanController::class, 'update'])->middleware('permission:erkap.expense-plans.edit')->name('update');
        Route::delete('/{expensePlan}', [ExpensePlanController::class, 'destroy'])->middleware('permission:erkap.expense-plans.delete')->name('destroy');
    });

    Route::prefix('profit-loss')->name('profit-loss.')->group(function () {
        Route::get('/', [ProfitLossController::class, 'index'])->middleware('permission:erkap.profit-loss.view')->name('index');
        Route::get('/simulate', [ProfitLossController::class, 'simulate'])->middleware('permission:erkap.profit-loss.view')->name('simulate');
        Route::post('/generate', [ProfitLossController::class, 'generate'])->middleware('permission:erkap.profit-loss.create')->name('generate');
        Route::get('/{profitLossStatement}', [ProfitLossController::class, 'show'])->middleware('permission:erkap.profit-loss.view')->name('show');
    });

    Route::prefix('budget-realizations')->name('budget-realizations.')->group(function () {
        Route::get('/', [BudgetRealizationController::class, 'index'])->middleware('permission:erkap.budget-realizations.view')->name('index');
        Route::get('/create', [BudgetRealizationController::class, 'create'])->middleware('permission:erkap.budget-realizations.create')->name('create');
        Route::post('/', [BudgetRealizationController::class, 'store'])->middleware('permission:erkap.budget-realizations.create')->name('store');
        Route::post('/import', [BudgetRealizationController::class, 'import'])->middleware('permission:erkap.budget-realizations.create')->name('import');
        Route::delete('/{budgetRealization}', [BudgetRealizationController::class, 'destroy'])->middleware('permission:erkap.budget-realizations.delete')->name('destroy');
    });

    Route::prefix('program-realizations')->name('program-realizations.')->group(function () {
        Route::get('/', [ProgramRealizationController::class, 'index'])->middleware('permission:erkap.program-realizations.view')->name('index');
        Route::get('/create', [ProgramRealizationController::class, 'create'])->middleware('permission:erkap.program-realizations.create')->name('create');
        Route::post('/', [ProgramRealizationController::class, 'store'])->middleware('permission:erkap.program-realizations.create')->name('store');
        Route::delete('/{programRealization}', [ProgramRealizationController::class, 'destroy'])->middleware('permission:erkap.program-realizations.delete')->name('destroy');
    });

    Route::prefix('risk-assessments-monthly')->name('risk-assessments-monthly.')->group(function () {
        Route::get('/', [RiskAssessmentMonthlyController::class, 'index'])->middleware('permission:erkap.risk-assessments-monthly.view')->name('index');
        Route::get('/create', [RiskAssessmentMonthlyController::class, 'create'])->middleware('permission:erkap.risk-assessments-monthly.create')->name('create');
        Route::post('/', [RiskAssessmentMonthlyController::class, 'store'])->middleware('permission:erkap.risk-assessments-monthly.create')->name('store');
        Route::delete('/{riskAssessmentMonthly}', [RiskAssessmentMonthlyController::class, 'destroy'])->middleware('permission:erkap.risk-assessments-monthly.delete')->name('destroy');
    });

    Route::prefix('performance-scorecards')->name('performance-scorecards.')->group(function () {
        Route::get('/', [PerformanceScorecardController::class, 'index'])->middleware('permission:erkap.performance-scorecards.view')->name('index');
        Route::get('/create', [PerformanceScorecardController::class, 'create'])->middleware('permission:erkap.performance-scorecards.create')->name('create');
        Route::post('/', [PerformanceScorecardController::class, 'store'])->middleware('permission:erkap.performance-scorecards.create')->name('store');
        Route::delete('/{performanceScorecard}', [PerformanceScorecardController::class, 'destroy'])->middleware('permission:erkap.performance-scorecards.delete')->name('destroy');
    });

    Route::prefix('approvals')->name('approvals.')->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])->middleware('permission:erkap.approvals.view')->name('index');
        Route::get('/history', [ApprovalController::class, 'history'])->middleware('permission:erkap.approvals.view')->name('history');
        Route::get('/division/{division}', [ApprovalController::class, 'division'])->middleware('permission:erkap.approvals.view')->name('division');
        Route::get('/{type}/{id}', [ApprovalController::class, 'show'])->middleware('permission:erkap.approvals.view')->name('show');
        Route::post('/{type}/{id}/approve', [ApprovalController::class, 'approve'])->middleware('permission:erkap.approvals.view')->name('approve');
        Route::post('/{type}/{id}/reject', [ApprovalController::class, 'reject'])->middleware('permission:erkap.approvals.view')->name('reject');
    });

    Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->middleware('permission:erkap.audit-logs.view')->name('index');
        Route::get('/{auditLog}', [AuditLogController::class, 'show'])->middleware('permission:erkap.audit-logs.view')->name('show');
    });
});