<?php

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvaluateController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

// Root: redirect unauthenticated visitors to the login page
Route::get('/', fn() => redirect()->route('login'));


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

    Route::resource('courses', Admin\CourseController::class)
        ->except(['create', 'show', 'edit']);

    Route::resource('criteria', Admin\CriteriaController::class)
        ->except(['create', 'show', 'edit']);

    Route::resource('evaluation-periods', Admin\EvaluationPeriodController::class)
        ->except(['create', 'show', 'edit']);

    // Roles & Permissions
    Route::get('/roles', [Admin\RolePermissionController::class, 'index'])->name('roles.index');
    Route::put('/roles', [Admin\RolePermissionController::class, 'update'])->name('roles.update');
    Route::post('/roles/reset', [Admin\RolePermissionController::class, 'reset'])->name('roles.reset');

    // Settings
    Route::get('/settings', [Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/general', [Admin\SettingsController::class, 'updateGeneral'])->name('settings.update-general');
    Route::delete('/settings/logo', [Admin\SettingsController::class, 'removeLogo'])->name('settings.remove-logo');

    // AI Model Training (admin + dean)
    Route::get('/model-training', [Admin\ModelTrainingController::class, 'index'])->name('model-training.index');
    Route::post('/model-training/train', [Admin\ModelTrainingController::class, 'train'])->name('model-training.train');
});

require __DIR__.'/auth.php';
