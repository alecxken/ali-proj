<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\Project;
use App\Support\Pulse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IssueController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');

        return Issue::with('project:id,name')
            ->when($status === 'open', fn ($q) => $q->open())
            ->when(in_array($status, Pulse::ISSUE_STATUS, true), fn ($q) => $q->where('status', $status))
            ->when($request->query('kind'), fn ($q, $v) => $q->where('kind', $v))
            ->when($request->query('severity'), fn ($q, $v) => $q->where('severity', $v))
            ->when($request->query('project_id'), fn ($q, $v) => $q->where('project_id', $v))
            ->bySeverity()->get();
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;
        $data['resolved_on'] = in_array($data['status'], ['Resolved', 'Closed']) ? now()->toDateString() : null;

        return response()->json(Issue::create($data)->load('project:id,name'), 201);
    }

    public function update(Request $request, Issue $issue)
    {
        $this->authorizeIssue($request, $issue);
        $data = $this->validated($request);
        $closing = in_array($data['status'], ['Resolved', 'Closed']);
        if ($closing && ! $issue->resolved_on) $data['resolved_on'] = now()->toDateString();
        if (! $closing) $data['resolved_on'] = null;
        $issue->update($data);

        return $issue->load('project:id,name');
    }

    public function destroy(Request $request, Issue $issue)
    {
        abort_unless($request->user()->isManager(), 403);
        $issue->delete();

        return response()->noContent();
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'kind' => ['required', Rule::in(Pulse::ISSUE_KINDS)],
            'title' => 'required|string|max:240',
            'description' => 'nullable|string|max:5000',
            'severity' => ['required', Rule::in(Pulse::SEVERITY)],
            'status' => ['required', Rule::in(Pulse::ISSUE_STATUS)],
            'owner_name' => 'nullable|string|max:120',
            'due_date' => 'nullable|date',
            'latest_note' => 'nullable|string|max:2000',
        ]);
        $user = $request->user();
        abort_if($user->role === 'viewer', 403);
        if (! empty($data['project_id'])) {
            abort_unless($user->canEditProject(Project::with('members:id')->find($data['project_id'])), 403);
        } else {
            abort_unless($user->isManager(), 403, 'Only PMO can log issues without a project.');
        }

        return $data;
    }

    private function authorizeIssue(Request $request, Issue $issue): void
    {
        $user = $request->user();
        abort_unless($issue->project ? $user->canEditProject($issue->project) : $user->isManager(), 403);
    }
}
