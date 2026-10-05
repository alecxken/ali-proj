<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/** Reference lists + week helpers shared by the API, validation and reports. */
final class Pulse
{
    public const ROLES = [
        'admin' => 'Administrator',
        'pmo' => 'PMO / Programme lead',
        'owner' => 'Project owner',
        'contributor' => 'Contributor',
        'viewer' => 'Viewer (read-only)',
    ];
    public const PHASES = ['Discovery', 'Design', 'Development', 'SIT', 'UAT', 'Pilot', 'Rollout', 'Live'];
    public const PROJECT_STATUS = ['Active', 'On hold', 'Completed', 'Cancelled'];
    public const RAG = ['G' => 'On track', 'A' => 'At risk', 'R' => 'Off track'];
    public const MILESTONE_STATUS = ['Not started', 'In progress', 'Done', 'Blocked'];
    public const ISSUE_KINDS = ['Production issue', 'Issue', 'Risk', 'Dependency'];
    public const SEVERITY = ['Critical', 'High', 'Medium', 'Low'];
    public const ISSUE_STATUS = ['Open', 'In progress', 'Monitoring', 'Resolved', 'Closed'];
    public const OPEN_ISSUE_STATUS = ['Open', 'In progress', 'Monitoring'];

    /** Monday of the week containing $date (reporting weeks run Mon–Sun). */
    public static function weekStart(?string $date = null): CarbonImmutable
    {
        try {
            $d = $date ? CarbonImmutable::parse($date) : CarbonImmutable::today();
        } catch (\Throwable) {
            $d = CarbonImmutable::today();
        }
        return $d->startOfWeek(CarbonImmutable::MONDAY)->startOfDay();
    }

    public static function weekLabel(CarbonImmutable $ws): string
    {
        $we = $ws->addDays(6);
        return $ws->month === $we->month
            ? $ws->format('j').'–'.$we->format('j M Y')
            : $ws->format('j M').' – '.$we->format('j M Y');
    }

    /** Split a multi-line field into clean bullet items. */
    public static function lines(?string $text): array
    {
        return collect(preg_split('/\r?\n/', (string) $text))
            ->map(fn ($l) => trim(ltrim(trim($l), '•-* ')))
            ->filter()->values()->all();
    }

    public static function lookups(): array
    {
        return [
            'roles' => self::ROLES, 'phases' => self::PHASES, 'projectStatus' => self::PROJECT_STATUS,
            'rag' => self::RAG, 'milestoneStatus' => self::MILESTONE_STATUS, 'issueKinds' => self::ISSUE_KINDS,
            'severity' => self::SEVERITY, 'issueStatus' => self::ISSUE_STATUS, 'openIssueStatus' => self::OPEN_ISSUE_STATUS,
        ];
    }
}
