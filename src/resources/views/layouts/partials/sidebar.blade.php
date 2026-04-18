{{-- Overview --}}
<div class="px-3.5 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-widest text-white/50">Overview</div>
<a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Dashboard</a>
@can('view-analytics')
<a href="{{ route('analytics.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('analytics.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Analytics</a>
@endcan
<a href="{{ route('announcements.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('announcements.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">
    <span>Announcements</span>
    @if(($unreadAnnouncementCount ?? 0) > 0)
        <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold bg-red-500 text-white rounded-full">{{ $unreadAnnouncementCount > 99 ? '99+' : $unreadAnnouncementCount }}</span>
    @endif
</a>

{{-- Evaluation --}}
@canany(['submit-dean-evaluation', 'submit-self-evaluation', 'submit-peer-evaluation', 'submit-student-evaluation', 'view-admin-dashboard', 'manage-criteria', 'manage-evaluation-periods', 'monitor-not-evaluated'])
<div class="px-3.5 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-widest text-white/50">Evaluation</div>
@endcanany
@canany(['submit-dean-evaluation', 'submit-self-evaluation', 'submit-peer-evaluation', 'submit-student-evaluation', 'view-admin-dashboard', 'monitor-not-evaluated'])
<a href="{{ route('evaluate.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('evaluate.*', 'certificates.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Evaluations</a>
@endcanany
@can('print-or-generate-comment')
<a href="{{ route('reports.employee-comments') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('reports.employee-comments') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Employee Comments</a>
@endcan
@canany(['generate-report', 'view-generated-report'])
<a href="{{ route('reports.individual-evaluation') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('reports.individual-evaluation') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Individual Evaluation Reports</a>
<a href="{{ route('reports.department') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('reports.department') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Department Report</a>
<a href="{{ route('reports.low-performance-personnel') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('reports.low-performance-personnel') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Low performance personnel</a>
<a href="{{ route('reports.chronic-low-performance') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('reports.chronic-low-performance') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Sustained Low Performance</a>
@endcanany
@can('manage-criteria')
<a href="{{ route('criteria.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('criteria.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Criteria</a>
@endcan
@can('manage-evaluation-periods')
<a href="{{ route('evaluation-periods.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('evaluation-periods.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Evaluation Periods</a>
@endcan

{{-- Academic --}}
@canany(['manage-courses', 'manage-subjects'])
<div class="px-3.5 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-widest text-white/50">Academic</div>
@endcanany
@can('manage-courses')
<a href="{{ route('courses.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('courses.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Courses</a>
@endcan
@can('manage-subjects')
<a href="{{ route('subjects.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('subjects.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Subjects</a>
@endcan

{{-- People --}}
@canany(['manage-departments', 'manage-faculty', 'manage-students'])
<div class="px-3.5 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-widest text-white/50">People</div>
@endcanany
@can('manage-departments')
<a href="{{ route('departments.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('departments.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Departments</a>
@endcan
@can('manage-faculty')
<a href="{{ route('faculty.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('faculty.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Faculty</a>
@endcan
@can('manage-students')
<a href="{{ route('students.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('students.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Students</a>
@endcan

{{-- Settings --}}
@canany(['manage-settings', 'manage-roles', 'view-users', 'manage-announcements-system', 'manage-announcements-department'])
<div class="px-3.5 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-widest text-white/50">Settings</div>
@endcanany
@can('manage-settings')
<a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('settings.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Settings</a>
@endcan
@can('manage-roles')
<a href="{{ route('roles.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('roles.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Roles & Permissions</a>
@endcan
@can('manage-settings')
<a href="{{ route('sentiment-lexicon.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('sentiment-lexicon.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Sentiment Lexicon</a>
@endcan
@canany(['manage-announcements-system', 'manage-announcements-department'])
<a href="{{ route('admin.announcements.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('admin.announcements.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Manage Announcements</a>
@endcanany
@can('manage-settings')
<a href="{{ route('audit-logs.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('audit-logs.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Audit Trail</a>
@endcan
@can('manage-settings')
<a href="{{ route('password-reset-requests.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('password-reset-requests.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">
    <span>Password Requests</span>
    @php try { $pendingResets = \App\Models\PasswordResetRequest::pending()->count(); } catch (\Throwable $e) { $pendingResets = 0; } @endphp
    @if($pendingResets > 0)
        <span class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold bg-red-500 text-white rounded-full">{{ $pendingResets }}</span>
    @endif
</a>
@endcan

{{-- AI --}}
@if(auth()->user()->hasRole(['admin', 'dean']))
<div class="px-3.5 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-widest text-white/50">AI</div>
<a href="{{ route('model-training.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('model-training.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Model Training</a>
@endif

{{-- Help Center (visible to all users) --}}
<div class="mt-6 pt-4 border-t border-white/10">
    <a href="{{ route('help.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('help.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/50 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 opacity-70"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        Help Center
    </a>
</div>
