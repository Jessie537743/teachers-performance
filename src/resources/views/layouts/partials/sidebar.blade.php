{{-- Overview --}}
<div class="px-3.5 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-widest text-white/50">Overview</div>
<a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Dashboard</a>
@can('view-analytics')
<a href="{{ route('analytics.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('analytics.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Analytics</a>
@endcan

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
@canany(['manage-settings', 'manage-roles', 'view-users'])
<div class="px-3.5 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-widest text-white/50">Settings</div>
@endcanany
@can('manage-settings')
<a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('settings.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Settings</a>
@endcan
@can('manage-roles')
<a href="{{ route('roles.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('roles.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Roles & Permissions</a>
@endcan

{{-- AI --}}
@if(auth()->user()->hasRole(['admin', 'dean']))
<div class="px-3.5 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-widest text-white/50">AI</div>
<a href="{{ route('model-training.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl mb-1 text-sm transition-all duration-200 {{ request()->routeIs('model-training.*') ? 'bg-white/15 text-white border-l-[3px] border-blue-400 pl-[11px]' : 'text-white/85 hover:bg-white/10 hover:text-white hover:translate-x-1' }}">Model Training</a>
@endif
