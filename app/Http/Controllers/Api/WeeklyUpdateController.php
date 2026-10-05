<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\WeeklyUpdate;
use App\Support\Pulse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WeeklyUpdateController extends Controller
{
    /**
     * The update for a given week. If none exists yet, return last week's content as a
     * draft so the author only edits what changed — the single biggest time saver.
     */
    public function show(Request $request, Project $project)
    {
        $week = Pulse::weekStart($request->query('week'));
        $existing = $project->updates()->where('week_start', $week->toDateString())->first();
        $previous = $project->updates()->where('week_start', '<', $week->toDateString())->first();
        $seed = $existing ?? $previous;

        $draft = $seed ? $seed->only(['rag', 'progress', 'phase', 'summary', 'achievements', 'critical_path',
            'next_steps', 'support_needed', 'metrics']) : ['rag' => 'G', 'metrics' => []];
        if (! $existing && $previous) {
            $draft['achievements'] = ''; // achievements are week-specific; don't carry them forward
        }
        $draft['phase'] ??= $project->phase;

        return [
            'project' => $project->only(['id', 'name', 'code', 'phase']),
            'week' => $week->toDateString(),
            'week_label' => Pulse::weekLabel($week),
            'exists' => (bool) $existing,
            'carried_from' => ! $existing ? $previous?->week_start?->toDateString() : null,
            'previous_metrics' => $previous?->metrics ?? [],
            'update' => $draft,
            'can_edit' => $request->user()->canEditProject($project),
        ];
    }

    public function save(Request $request, Project $project)
    {
        abort_unless($request->user()->canEditProject($project), 403);
        $data = $request->validate([
            'week' => 'required|date',
            'rag' => ['required', Rule::in(array_keys(Pulse::RAG))],
            'progress' => 'nullable|integer|min:0|max:100',
            'phase' => ['nullable', Rule::in(Pulse::PHASES)],
            'summary' => 'required|string|max:1000',
            'achievements' => 'nullable|string|max:5000',
            'critical_path' => 'nullable|string|max:5000',
            'next_steps' => 'nullable|string|max:5000',
            'support_needed' => 'nullable|string|max:3000',
            'metrics' => 'array|max:12',
            'metrics.*.label' => 'required|string|max:120',
            'metrics.*.value' => 'required|numeric',
            'metrics.*.target' => 'nullable|numeric',
            'metrics.*.unit' => 'nullable|string|max:10',
        ]);
        $week = Pulse::weekStart($data['week']);
        unset($data['week']);
        $data['metrics'] = array_values(array_map(fn ($m) => [
            'label' => trim($m['label']), 'value' => (float) $m['value'],
            'target' => isset($m['target']) && $m['target'] !== '' ? (float) $m['target'] : null,
            'unit' => $m['unit'] ?? '%',
        ], $data['metrics'] ?? []));
        $data['user_id'] = $request->user()->id;

        $update = WeeklyUpdate::updateOrCreate(['project_id' => $project->id, 'week_start' => $week->toDateString()], $data);
        if (! empty($data['phase']) && $data['phase'] !== $project->phase) {
            $project->update(['phase' => $data['phase']]);
        }

        return $update;
    }
}
