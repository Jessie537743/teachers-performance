<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\User;
use App\Services\MarkdownRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnnouncementManagementController extends Controller
{
    public function __construct(private MarkdownRenderer $renderer) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Announcement::class);

        $status = $request->string('status')->toString() ?: 'published';
        $announcements = Announcement::query()
            ->where('status', $status)
            ->with('creator')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.announcements.index', [
            'announcements' => $announcements,
            'status'        => $status,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Announcement::class);

        return view('admin.announcements.create', $this->formContext());
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $html = $this->renderer->render($data['body_markdown']);

        DB::transaction(function () use ($data, $html, $request) {
            $a = Announcement::create([
                'title'         => $data['title'],
                'body_markdown' => $data['body_markdown'],
                'body_html'     => $html,
                'priority'      => $data['priority'],
                'is_pinned'     => $request->boolean('is_pinned'),
                'everyone'      => $request->boolean('everyone'),
                'show_on_login' => $request->boolean('show_on_login'),
                'status'        => $data['status'],
                'publish_at'    => $data['publish_at'] ?? null,
                'expires_at'    => $data['expires_at'] ?? null,
                'created_by'    => $request->user()->id,
            ]);

            $this->syncTargets($a, $data['targets'] ?? []);
        });

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement created.');
    }

    public function edit(Announcement $announcement): View
    {
        $this->authorize('update', $announcement);

        return view('admin.announcements.edit', $this->formContext() + [
            'announcement' => $announcement->load('targets'),
        ]);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $data = $request->validated();
        $bodyChanged = $data['body_markdown'] !== $announcement->body_markdown;
        $priorityChanged = $data['priority'] !== $announcement->priority;
        $html = $bodyChanged ? $this->renderer->render($data['body_markdown']) : $announcement->body_html;

        DB::transaction(function () use ($data, $html, $request, $announcement, $bodyChanged, $priorityChanged) {
            $announcement->update([
                'title'         => $data['title'],
                'body_markdown' => $data['body_markdown'],
                'body_html'     => $html,
                'priority'      => $data['priority'],
                'is_pinned'     => $request->boolean('is_pinned'),
                'everyone'      => $request->boolean('everyone'),
                'show_on_login' => $request->boolean('show_on_login'),
                'status'        => $data['status'],
                'publish_at'    => $data['publish_at'] ?? null,
                'expires_at'    => $data['expires_at'] ?? null,
                'updated_by'    => $request->user()->id,
            ]);

            $this->syncTargets($announcement, $data['targets'] ?? []);

            if ($request->boolean('notify_again') && ($bodyChanged || $priorityChanged)) {
                $announcement->reads()->delete();
            }
        });

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement updated.');
    }

    public function archive(Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);
        $announcement->update(['status' => 'archived']);

        return back()->with('status', 'Announcement archived.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->authorize('delete', $announcement);
        $announcement->delete();

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement deleted.');
    }

    private function syncTargets(Announcement $announcement, array $rows): void
    {
        $announcement->targets()->delete();
        foreach ($rows as $r) {
            $announcement->targets()->create([
                'target_type' => $r['target_type'],
                'target_id'   => (string) $r['target_id'],
                'is_exclude'  => (bool) ($r['is_exclude'] ?? false),
            ]);
        }
    }

    private function formContext(): array
    {
        return [
            'allRoles'     => \App\Enums\Permission::allRoles(),
            'departments'  => Department::orderBy('name')->get(['id', 'name']),
            'userOptions'  => User::orderBy('name')->get(['id', 'name', 'email', 'department_id']),
        ];
    }
}
