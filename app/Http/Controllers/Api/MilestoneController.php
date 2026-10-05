<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\Project;
use App\Support\Pulse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MilestoneController extends Controller
{
    public function store(Request $request, Project $project)
    {
        abort_unless($request->user()->canEditProject($project), 403);
        $data = $this->rules($request);
        $data['completed_on'] = $data['status'] === 'Done' ? now()->toDateString() : null;

        return response()->json($project->milestones()->create($data), 201);
    }

    public function update(Request $request, Milestone $milestone)
    {
        abort_unless($request->user()->canEditProject($milestone->project), 403);
        $data = $this->rules($request);
        if ($data['status'] === 'Done' && $milestone->status !== 'Done') {
            $data['completed_on'] = now()->toDateString();
        } elseif ($data['status'] !== 'Done') {
            $data['completed_on'] = null;
        }
        $milestone->update($data);

        return $milestone;
    }

    public function destroy(Request $request, Milestone $milestone)
    {
        abort_unless($request->user()->canEditProject($milestone->project), 403);
        $milestone->delete();

        return response()->noContent();
    }

    private function rules(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:200',
            'due_date' => 'nullable|date',
            'status' => ['required', Rule::in(Pulse::MILESTONE_STATUS)],
        ]);
    }
}
