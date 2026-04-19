<?php

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordRequestController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvaluateController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AnnouncementController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    PreventAccessFromCentralDomains::class,
    InitializeTenancyBySubdomain::class,
])->group(function () {

// Root: redirect unauthenticated visitors to the login page
Route::get('/', fn() => redirect()->route('login'));

// Forgot Password Request (guest)
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password-request', [ForgotPasswordRequestController::class, 'showForm'])->name('forgot-password.form');
    Route::post('/forgot-password-request/verify', [ForgotPasswordRequestController::class, 'verify'])->name('forgot-password.verify');
    Route::post('/forgot-password-request/submit', [ForgotPasswordRequestController::class, 'submit'])->name('forgot-password.submit');
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
});

// -------------------------------------------------------------------------
// All feature routes — protected by auth + must.change.password
// Access is controlled by permissions (gates) instead of role middleware
// -------------------------------------------------------------------------
Route::middleware(['auth', 'must.change.password'])->group(function () {

    // Help Center — accessible to all authenticated users
    Route::get('/help', [HelpController::class, 'index'])->name('help.index');

    // Announcements — visible to every authenticated user
    Route::get('/announcements',                           [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements/mark-read-batch',          [AnnouncementController::class, 'markReadBatch'])->name('announcements.read-batch');
    Route::get('/announcements/{announcement}',            [AnnouncementController::class, 'show'])->name('announcements.show');
    Route::post('/announcements/{announcement}/read',      [AnnouncementController::class, 'markRead'])->name('announcements.read');
    Route::post('/announcements/{announcement}/ack',       [AnnouncementController::class, 'acknowledge'])->name('announcements.ack');

    // Dashboard — single entry point, controller resolves the right view per role
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Analytics — permission-gated, controller resolves system vs department view
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

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
    Route::post('faculty/bulk-upload', [Admin\FacultyController::class, 'bulkUpload'])
        ->name('faculty.bulk-upload');
    Route::get('faculty/bulk-template', [Admin\FacultyController::class, 'downloadBulkTemplate'])
        ->name('faculty.bulk-template');

    Route::get('faculty-profiles/{faculty_profile}/intervention-suggestions', [Admin\FacultyInterventionSuggestionController::class, 'show'])
        ->name('faculty.intervention-suggestions');

    Route::get('certificates/performance/{faculty_profile}', [Admin\PerformanceCertificateController::class, 'show'])
        ->name('certificates.performance-excellent');
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
});

require __DIR__.'/auth.php';

}); // end tenancy middleware group
