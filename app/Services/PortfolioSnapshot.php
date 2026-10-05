<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\Project;
use App\Models\WeeklyUpdate;
use App\Support\Pulse;
use Carbon\CarbonImmutable;

/**
 * Builds the weekly portfolio view. The dashboard, the report preview and the
 * PDF / PPT generator all consume this one structure, so they never disagree.
 */
class PortfolioSnapshot
{
    private const RAG_RANK = ['R' => 0, 'A' => 1, 'G' => 2];

    public function build(CarbonImmutable $week, ?string $programme = null): array
    {
        $weekEnd = $week->addDays(6);
        $horizon = $week->addDays(20); // this week + next two
        $today = CarbonImmutable::today();

        $projects = Project::query()
            ->whereIn('status', ['Active', 'On hold'])
            ->when($programme, fn ($q) => $q->where('programme', $programme))
            ->with(['owner:id,name', 'milestones',
                'updates' => fn ($q) => $q->where('week_start', '<=', $week->toDateString()),
                'issues' => fn ($q) => $q->open()])
            ->orderBy('programme')->orderBy('sort_order')->orderBy('name')
            ->get();

        $rows = $projects->map(function (Project $p) use ($week, $horizon, $today) {
            /** @var WeeklyUpdate|null $shown */
            $shown = $p->updates->first();                       // latest on/before this week
            $current = $shown && $shown->week_start->isSameDay($week) ? $shown : null;
            $previous = $shown ? $p->updates->first(fn ($u) => $u->week_start->lt($shown->week_start)) : null;

            $open = $p->milestones->where('status', '!=', 'Done')->filter(fn ($m) => $m->due_date);
            $due = $open->filter(fn ($m) => $m->due_date->between($week, $horizon))->values();
            $overdue = $open->filter(fn ($m) => $m->due_date->lt($today))->values();

            return [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'programme' => $p->programme,
                'phase' => $shown?->phase ?: $p->phase,
                'status' => $p->status,
                'owner' => $p->owner?->name,
                'target_date' => $p->target_date?->toDateString(),
                'rag' => $shown?->rag,
                'trend' => $this->trend($shown?->rag, $previous?->rag),
                'stale' => $current === null,
                'update_week' => $shown?->week_start?->toDateString(),
                'progress' => $shown?->progress,
                'summary' => $shown?->summary,
                'achievements' => Pulse::lines($shown?->achievements),
                'critical_path' => Pulse::lines($shown?->critical_path),
                'next_steps' => Pulse::lines($shown?->next_steps),
                'support_needed' => Pulse::lines($shown?->support_needed),
                'metrics' => array_values($shown?->metrics ?? []),
                'milestones_due' => $due->map(fn ($m) => [
                    'title' => $m->title, 'due_date' => $m->due_date->toDateString(), 'status' => $m->status,
                ])->all(),
                'milestones_overdue' => $overdue->map(fn ($m) => [
                    'title' => $m->title, 'due_date' => $m->due_date->toDateString(), 'status' => $m->status,
                ])->all(),
                'milestones_done' => $p->milestones->where('status', 'Done')->count(),
                'milestones_total' => $p->milestones->count(),
                'open_issues' => $p->issues->count(),
            ];
        })
            ->sortBy(fn ($r) => sprintf('%s|%d|%s', $r['programme'] ?? '', self::RAG_RANK[$r['rag']] ?? 3, $r['name']))
            ->values();

        $issueQuery = fn () => Issue::query()->with('project:id,name,programme')
            ->when($programme, fn ($q) => $q->whereHas('project', fn ($p) => $p->where('programme', $programme)));
        $mapIssue = fn (Issue $i) => [
            'id' => $i->id, 'kind' => $i->kind, 'title' => $i->title, 'project' => $i->project?->name,
            'severity' => $i->severity, 'status' => $i->status, 'owner' => $i->owner_name,
            'due_date' => $i->due_date?->toDateString(), 'overdue' => $i->overdue, 'latest_note' => $i->latest_note,
        ];
        $issues = $issueQuery()->open()->bySeverity()->get()->map($mapIssue)->values();
        $resolved = $issueQuery()->whereBetween('resolved_on', [$week->toDateString(), $weekEnd->toDateString()])
            ->get()->map($mapIssue)->values();

        $active = $rows->where('status', 'Active');

        return [
            'org' => config('pulse.org_name'),
            'title' => config('pulse.report_title'),
            'brand' => config('pulse.brand_color'),
            'programme' => $programme,
            'week_start' => $week->toDateString(),
            'week_label' => Pulse::weekLabel($week),
            'generated_at' => now()->format('j M Y, H:i'),
            'totals' => [
                'projects' => $rows->count(),
                'G' => $rows->where('rag', 'G')->count(),
                'A' => $rows->where('rag', 'A')->count(),
                'R' => $rows->where('rag', 'R')->count(),
                'none' => $rows->whereNull('rag')->count(),
                'expected' => $active->count(),
                'submitted' => $active->where('stale', false)->count(),
                'open_issues' => $issues->count(),
                'critical_issues' => $issues->whereIn('severity', ['Critical', 'High'])->count(),
                'production_issues' => $issues->where('kind', 'Production issue')->count(),
                'overdue_milestones' => $rows->sum(fn ($r) => count($r['milestones_overdue'])),
            ],
            'projects' => $rows->all(),
            'issues' => $issues->all(),
            'resolved' => $resolved->all(),
        ];
    }

    private function trend(?string $now, ?string $before): string
    {
        if (! $now) return 'none';
        if (! $before) return 'new';
        $d = self::RAG_RANK[$now] <=> self::RAG_RANK[$before];
        return $d > 0 ? 'up' : ($d < 0 ? 'down' : 'flat');
    }
}
