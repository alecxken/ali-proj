<?php

namespace App\Services;

use App\Models\ReportRun;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Hands the weekly snapshot (as JSON) to reports/generate_report.py, which renders
 * the PDF or PowerPoint. Python is used because reportlab / python-pptx give far
 * better PDF and native, editable PPT output than the PHP options.
 */
class ReportGenerator
{
    public function __construct(private PortfolioSnapshot $snapshots) {}

    public function generate(CarbonImmutable $week, string $format, ?string $programme = null, ?User $user = null): ReportRun
    {
        if (! in_array($format, ['pdf', 'pptx'], true)) {
            throw new RuntimeException('Format must be pdf or pptx.');
        }
        $cfg = config('pulse.reports');
        File::ensureDirectoryExists($cfg['disk_path']);

        $slug = Str::slug($programme ?: 'all-projects');
        $file = "progress-report_{$week->toDateString()}_{$slug}.{$format}";
        $out = $cfg['disk_path'].DIRECTORY_SEPARATOR.$file;
        $json = $cfg['disk_path'].DIRECTORY_SEPARATOR.Str::uuid().'.json';

        File::put($json, json_encode($this->snapshots->build($week, $programme), JSON_UNESCAPED_UNICODE));
        try {
            $result = Process::timeout($cfg['timeout'])->run([
                $cfg['python'], $cfg['script'], '--input', $json, '--output', $out, '--format', $format,
            ]);
        } finally {
            File::delete($json);
        }
        if ($result->failed() || ! File::exists($out)) {
            throw new RuntimeException('Report generation failed: '.trim($result->errorOutput() ?: $result->output()));
        }

        return ReportRun::create([
            'week_start' => $week->toDateString(), 'format' => $format, 'programme' => $programme,
            'file' => $file, 'user_id' => $user?->id,
        ]);
    }
}
