<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Support\Pulse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status', 'Active');

        return Project::with(['owner:id,name', 'members:id,name',
            'updates' => fn ($q) => $q->select('id', 'project_id', 'week_start', 'rag', 'progress')])
            ->withCount(['issues as open_issues' => fn ($q) => $q->open()])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->query('programme'), fn ($q, $v) => $q->where('programme', $v))
            ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($w) => $w->where('name', 'like', "%$v%")->orWhere('code', 'like', "%$v%")))
            ->when($request->boolean('mine'), fn ($q) => $q->where(fn ($w) => $w->where('owner_id', $user->id)
                ->orWhereHas('members', fn ($m) => $m->where('users.id', $user->id))))
            ->orderBy('programme')->orderBy('sort_order')->orderBy('name')
            ->get()
            ->map(function (Project $p) use ($user) {
                $latest = $p->updates->first();
                $data = $p->only(['id', 'name', 'code', 'programme', 'phase', 'status', 'target_date', 'open_issues']);
                $data['owner'] = $p->owner?->name;
                $data['rag'] = $latest?->rag;
                $data['progress'] = $latest?->progress;
                $data['last_update'] = $latest?->week_start?->toDateString();
                $data['can_edit'] = $user->canEditProject($p);
                return $data;
            });
    }

    public function show(Request $request, Project $project)
    {
        $project->load(['owner:id,name', 'members:id,name', 'milestones',
            'updates' => fn ($q) => $q->with('author:id,name')->limit(12),
            'issues' => fn ($q) => $q->bySeverity()]);
        $user = $request->user();

        return array_merge($project->toArray(), [
            'can_edit' => $user->canEditProject($project),
            'can_manage' => $user->canManageProject($project),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isManager(), 403);
        $project = Project::create($this->validated($request));
        $project->members()->sync($request->input('member_ids', []));

        return response()->json($project, 201);
    }

    public function update(Request $request, Project $project)
    {
        abort_unless($request->user()->canManageProject($project), 403);
        $data = $this->validated($request, $project);
        if (! $request->user()->isManager()) {
            unset($data['owner_id']); // owners can't hand their project to someone else
        }
        $project->update($data);
        $project->members()->sync($request->input('member_ids', []));

        return $project;
    }

    public function destroy(Request $request, Project $project)
    {
        abort_unless($request->user()->isManager(), 403);
        $project->delete();

        return response()->noContent();
    }

    private function validated(Request $request, ?Project $project = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'code' => ['nullable', 'string', 'max:30', Rule::unique('projects', 'code')->ignore($project?->id)],
            'programme' => 'nullable|string|max:160',
            'description' => 'nullable|string|max:5000',
            'phase' => ['required', Rule::in(Pulse::PHASES)],
            'status' => ['required', Rule::in(Pulse::PROJECT_STATUS)],
            'owner_id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'target_date' => 'nullable|date|after_or_equal:start_date',
            'sort_order' => 'nullable|integer|min:0',
            'member_ids' => 'array',
            'member_ids.*' => 'integer|exists:users,id',
        ]);
        $data['code'] = $data['code'] ? strtoupper($data['code']) : null;
        $data['sort_order'] ??= 0;
        unset($data['member_ids']);

        return $data;
    }
}
