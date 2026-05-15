<?php

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentalPlanController;
use App\Http\Controllers\EvaluateController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TopPerformerController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AnnouncementController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureTenantIsActive;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    PreventAccessFromCentralDomains::class,
    InitializeTenancyBySubdomain::class,
    EnsureTenantIsActive::class,
])->group(function () {

// Root: redirect unauthenticated visitors to the login page
Route::get('/', fn() => redirect()->route('login'));

// Guest registration (Dean approves students, Admin approves personnel).
// The legacy /forgot-password-request verify-identity flow has been replaced
// by Laravel's built-in email-link reset (see routes/auth.php).
Route::middleware('guest')->group(function () {
    Route::get('/register', [\App\Http\Controllers\Auth\RegistrationController::class, 'show'])->name('register.show');
    Route::post('/register', [\App\Http\Controllers\Auth\RegistrationController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');
    Route::get('/register/submitted', [\App\Http\Controllers\Auth\RegistrationController::class, 'submitted'])->name('register.submitted');
});


// -------------------------------------------------------------------------
// Change password (auth required; intentionally excluded from must.change.password
// so users with must_change_password = true can still reach this screen)
// -------------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    Route::get('/change-password', [ChangePasswordController::class, 'show'])
        ->name('password.change');
    Route::post('/change-password', [ChangePasswordController::class, 'update'])
        ->name('password.update.custom');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Plan upgrade landing — middleware redirects here when a capability is missing
    Route::get('/plan/upgrade', [\App\Http\Controllers\PlanUpgradeController::class, 'show'])
        ->name('plan.upgrade');

    // Billing — current plan, invoices, cancel subscription
    Route::get('/billing', [\App\Http\Controllers\BillingController::class, 'show'])->name('billing.show');
    Route::post('/billing/cancel', [\App\Http\Controllers\BillingController::class, 'cancel'])->name('billing.cancel');

    // Self-serve plan upgrade — checkout page + confirm
    Route::get('/billing/checkout', [\App\Http\Controllers\BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/billing/checkout', [\App\Http\Controllers\BillingController::class, 'confirmCheckout'])->name('billing.checkout.confirm');
});

// -------------------------------------------------------------------------
// All feature routes — protected by auth + must.change.password
// Access is controlled by permissions (gates) instead of role middleware
// -------------------------------------------------------------------------
Route::middleware(['auth', 'must.change.password'])->group(function () {

    // Help Center — accessible to all authenticated users
    Route::get('/help', [HelpController::class, 'index'])->name('help.index');

    // Guided app tour — JS POSTs here when the user finishes or skips a tour
    // so we don't auto-show it again. Manual replay from the user menu does
    // not call this endpoint.
    Route::post('/tour/complete', [\App\Http\Controllers\TourController::class, 'complete'])
        ->name('tour.complete');

    // Announcements — visible to every authenticated user
    Route::get('/announcements',                           [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements/mark-read-batch',          [AnnouncementController::class, 'markReadBatch'])->name('announcements.read-batch');
    Route::get('/announcements/{announcement}',            [AnnouncementController::class, 'show'])->name('announcements.show');
    Route::post('/announcements/{announcement}/read',      [AnnouncementController::class, 'markRead'])->name('announcements.read');
    Route::post('/announcements/{announcement}/ack',       [AnnouncementController::class, 'acknowledge'])->name('announcements.ack');

    // Dashboard — single entry point, controller resolves the right view per role
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Analytics — permission-gated AND plan-gated.
    // `ai_predictions` is the headline feature here; tenants without it see /plan/upgrade.
    Route::get('/analytics', [AnalyticsController::class, 'index'])
        ->middleware('plan.feature:ai_predictions')
        ->name('analytics.index');

    // Evaluations — unified controller with type-based routing
    Route::get('/evaluate', [EvaluateController::class, 'index'])->name('evaluate.index');
    Route::get('/evaluate/{type}/{facultyId}/{subjectId?}', [EvaluateController::class, 'show'])
        ->name('evaluate.show');
    Route::post('/evaluate', [EvaluateController::class, 'store'])->name('evaluate.store');

    // -----------------------------------------------------------------------
    // Management features — each gated by its own permission
    // Controllers stay in the Admin namespace (they contain the business logic)
    // but routes are no longer role-prefixed
    // -----------------------------------------------------------------------
    Route::resource('departments', Admin\DepartmentController::class)
        ->except(['create', 'show', 'edit']);
    Route::post('departments/{department}/reactivate', [Admin\DepartmentController::class, 'reactivate'])
        ->name('departments.reactivate');

    Route::resource('faculty', Admin\FacultyController::class)
        ->except(['create', 'show', 'edit']);
    Route::post('faculty/{faculty}/reactivate', [Admin\FacultyController::class, 'reactivate'])
        ->name('faculty.reactivate');
    Route::post('faculty/bulk-upload', [Admin\FacultyController::class, 'bulkUpload'])
        ->name('faculty.bulk-upload');
    Route::get('faculty/bulk-template', [Admin\FacultyController::class, 'downloadBulkTemplate'])
        ->name('faculty.bulk-template');

    Route::get('faculty-profiles/{faculty_profile}/intervention-suggestions', [Admin\FacultyInterventionSuggestionController::class, 'show'])
        ->name('faculty.intervention-suggestions');

    // Departmental Plan — dean/head roll-up of action items for their department.
    // Generation pulls from existing evaluation results + dean recommendations.
    Route::get('departmental-plan', [DepartmentalPlanController::class, 'index'])
        ->name('departmental-plan.index');
    Route::post('departmental-plan/generate', [DepartmentalPlanController::class, 'generate'])
        ->name('departmental-plan.generate');
    Route::post('departmental-plan/{plan}/status', [DepartmentalPlanController::class, 'updateStatus'])
        ->name('departmental-plan.status');
    Route::post('departmental-plan/items/{item}/status', [DepartmentalPlanController::class, 'updateItemStatus'])
        ->name('departmental-plan.item.status');

    // Intervention Recommendation Module — roster of all faculty + mapped HR intervention
    Route::get('intervention-recommendations', [Admin\InterventionRecommendationController::class, 'index'])
        ->name('intervention-recommendations.index');
    Route::get('intervention-recommendations/{faculty_id}', [Admin\InterventionRecommendationController::class, 'show'])
        ->whereNumber('faculty_id')
        ->name('intervention-recommendations.show');

    // AI-driven intervention plans — gated by plan capability
    Route::middleware('plan.feature:ai_predictions')->group(function () {
        Route::get('faculty-profiles/{faculty_profile}/ai-intervention-plan', [Admin\AiInterventionPlanController::class, 'show'])
            ->name('faculty.ai-intervention-plan.show');
        Route::post('faculty-profiles/{faculty_profile}/ai-intervention-plan/generate', [Admin\AiInterventionPlanController::class, 'generate'])
            ->name('faculty.ai-intervention-plan.generate');
        Route::post('intervention-plans/{plan}/status', [Admin\AiInterventionPlanController::class, 'updateStatus'])
            ->name('faculty.ai-intervention-plan.status');

        // Per-comment AI Improvement Suggestion (analyze + regenerate via AJAX)
        Route::post('feedback-improvement/analyze', [Admin\FeedbackImprovementController::class, 'analyze'])
            ->name('feedback-improvement.analyze');
    });

    // Individual Development Plan (IDP) — every faculty member, AI-assisted growth plan.
    // Not plan-gated: this is a standard HR development workflow, not a paid AI feature.
    Route::get('faculty-profiles/{faculty_profile}/idp', [Admin\IndividualDevelopmentPlanController::class, 'show'])
        ->name('faculty.idp.show');
    Route::post('faculty-profiles/{faculty_profile}/idp/generate', [Admin\IndividualDevelopmentPlanController::class, 'generate'])
        ->name('faculty.idp.generate');
    Route::post('individual-development-plans/{plan}/status', [Admin\IndividualDevelopmentPlanController::class, 'updateStatus'])
        ->name('faculty.idp.status');

    // Registration approvals — Dean/Head approve students; Admin/HR approve personnel
    Route::get('registration-approvals', [Admin\RegistrationApprovalController::class, 'index'])
        ->name('admin.registration-approvals.index');
    Route::post('registration-approvals/{registration}/approve', [Admin\RegistrationApprovalController::class, 'approve'])
        ->name('admin.registration-approvals.approve');
    Route::post('registration-approvals/{registration}/reject', [Admin\RegistrationApprovalController::class, 'reject'])
        ->name('admin.registration-approvals.reject');

    Route::get('certificates/performance/{faculty_profile}', [Admin\PerformanceCertificateController::class, 'show'])
        ->name('certificates.performance-excellent');

    // Top Performer of the Department — one #1 award per (department, period).
    Route::get('top-performers', [TopPerformerController::class, 'index'])
        ->name('top-performers.index');
    Route::get('top-performers/{faculty_profile}/certificate', [TopPerformerController::class, 'certificate'])
        ->name('top-performers.certificate');
    Route::get('reports/employee-comments', [Admin\EmployeeCommentReportController::class, 'index'])
        ->name('reports.employee-comments');
    Route::get('reports/individual-evaluation', [Admin\IndividualEvaluationReportController::class, 'index'])
        ->name('reports.individual-evaluation');
    Route::get('reports/department', [Admin\DepartmentReportController::class, 'index'])
        ->name('reports.department');
    Route::get('reports/low-performance-personnel', [Admin\LowPerformancePersonnelReportController::class, 'index'])
        ->name('reports.low-performance-personnel');
    Route::get('reports/chronic-low-performance', [Admin\ChronicLowPerformanceReportController::class, 'index'])
        ->name('reports.chronic-low-performance');

    Route::resource('students', Admin\StudentController::class)
        ->except(['create', 'show', 'edit']);
    Route::post('students/bulk-upload', [Admin\StudentController::class, 'bulkUpload'])
        ->name('students.bulk-upload');
    Route::get('students/bulk-template', [Admin\StudentController::class, 'downloadBulkTemplate'])
        ->name('students.bulk-template');

    Route::post('subjects/bulk-upload', [Admin\SubjectController::class, 'bulkUpload'])
        ->name('subjects.bulk-upload');
    Route::get('subjects/bulk-template', [Admin\SubjectController::class, 'downloadBulkTemplate'])
        ->name('subjects.bulk-template');
    Route::resource('subjects', Admin\SubjectController::class)
        ->except(['create']);
    Route::post('subjects/{subject}/reactivate', [Admin\SubjectController::class, 'reactivate'])
        ->name('subjects.reactivate');

    Route::resource('courses', Admin\CourseController::class)
        ->except(['create', 'show', 'edit']);

    Route::resource('criteria', Admin\CriteriaController::class)
        ->except(['create', 'show', 'edit']);

    // List-only UI: no detail page — GET /evaluation-periods/{id} would otherwise 405 (resource has no show).
    Route::get('evaluation-periods/{evaluation_period}', [Admin\EvaluationPeriodController::class, 'show'])
        ->name('evaluation-periods.show');

    Route::resource('evaluation-periods', Admin\EvaluationPeriodController::class)
        ->except(['create', 'show', 'edit']);

    // Roles & Permissions
    Route::get('/roles', [Admin\RolePermissionController::class, 'index'])->name('roles.index');
    Route::put('/roles', [Admin\RolePermissionController::class, 'update'])->name('roles.update');
    Route::post('/roles/reset', [Admin\RolePermissionController::class, 'reset'])->name('roles.reset');

    // Permission Delegations
    Route::get('/roles/delegations', [Admin\PermissionDelegationController::class, 'index'])->name('roles.delegations.index');
    Route::post('/roles/delegations', [Admin\PermissionDelegationController::class, 'store'])->name('roles.delegations.store');
    Route::post('/roles/delegations/{delegation}/revoke', [Admin\PermissionDelegationController::class, 'revoke'])->name('roles.delegations.revoke');

    // Settings
    Route::get('/settings', [Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/general', [Admin\SettingsController::class, 'updateGeneral'])->name('settings.update-general');
    Route::delete('/settings/logo', [Admin\SettingsController::class, 'removeLogo'])->name('settings.remove-logo');
    Route::post('/settings/signatures/{user}', [Admin\SettingsController::class, 'uploadSignature'])->name('settings.signatures.upload');
    Route::post('/settings/signatures/{user}/draw', [Admin\SettingsController::class, 'drawSignature'])->name('settings.signatures.draw');
    Route::patch('/settings/signatures/{signature}/title', [Admin\SettingsController::class, 'updateSignatureTitle'])->name('settings.signatures.title');
    Route::post('/settings/signatures/{signature}/mark', [Admin\SettingsController::class, 'markSignatory'])->name('settings.signatures.mark');
    Route::post('/settings/signatures/{signature}/clear', [Admin\SettingsController::class, 'clearSignatory'])->name('settings.signatures.clear');
    Route::delete('/settings/signatures/{signature}', [Admin\SettingsController::class, 'removeSignature'])->name('settings.signatures.remove');

    // Announcements — admin CRUD (policy-gated by AnnouncementPolicy)
    Route::resource('admin/announcements', Admin\AnnouncementManagementController::class)
        ->except(['show'])
        ->names('admin.announcements');
    Route::post('admin/announcements/{announcement}/archive', [Admin\AnnouncementManagementController::class, 'archive'])
        ->name('admin.announcements.archive');

    // Audit Logs
    Route::get('/audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit-logs.index');

    // Password Reset Requests (admin approval)
    Route::get('/password-reset-requests', [Admin\PasswordResetApprovalController::class, 'index'])->name('password-reset-requests.index');
    Route::post('/password-reset-requests/{passwordResetRequest}/approve', [Admin\PasswordResetApprovalController::class, 'approve'])->name('password-reset-requests.approve');
    Route::post('/password-reset-requests/{passwordResetRequest}/decline', [Admin\PasswordResetApprovalController::class, 'decline'])->name('password-reset-requests.decline');

    // Sentiment Lexicon Management
    Route::get('/sentiment-lexicon', [Admin\SentimentLexiconController::class, 'index'])->name('sentiment-lexicon.index');
    Route::post('/sentiment-lexicon', [Admin\SentimentLexiconController::class, 'store'])->name('sentiment-lexicon.store');
    Route::put('/sentiment-lexicon/{sentimentLexicon}', [Admin\SentimentLexiconController::class, 'update'])->name('sentiment-lexicon.update');
    Route::delete('/sentiment-lexicon/{sentimentLexicon}', [Admin\SentimentLexiconController::class, 'destroy'])->name('sentiment-lexicon.destroy');

    // AI Model Training (admin + dean)
    Route::get('/model-training', [Admin\ModelTrainingController::class, 'index'])->name('model-training.index');
    Route::post('/model-training/train', [Admin\ModelTrainingController::class, 'train'])->name('model-training.train');

    // API Integration — external systems feed Students / Departments / Subjects / Courses
    Route::get   ('/api-integration',                 [Admin\ApiIntegrationController::class, 'index'])  ->name('api-integration.index');
    Route::put   ('/api-integration',                 [Admin\ApiIntegrationController::class, 'update']) ->name('api-integration.update');
    Route::post  ('/api-integration/test',            [Admin\ApiIntegrationController::class, 'test'])   ->name('api-integration.test');
    Route::post  ('/api-integration/sync/{resource}', [Admin\ApiIntegrationController::class, 'sync'])   ->name('api-integration.sync');
    Route::delete('/api-integration',                 [Admin\ApiIntegrationController::class, 'destroy'])->name('api-integration.destroy');
});

require __DIR__.'/auth.php';

}); // end tenancy middleware group
