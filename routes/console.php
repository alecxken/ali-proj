<?php

use App\Services\ReportGenerator;
use App\Support\Pulse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('pulse:report {--week=} {--format=pdf} {--programme=}', function (ReportGenerator $generator) {
    $run = $generator->generate(Pulse::weekStart($this->option('week')), $this->option('format'), $this->option('programme') ?: null);
    $this->info('Report written to '.config('pulse.reports.disk_path').DIRECTORY_SEPARATOR.$run->file);
})->purpose('Generate the weekly progress report (pdf or pptx)');

// Auto-build both formats every Friday at 16:00 (needs the Laravel scheduler running, see README).
Schedule::command('pulse:report --format=pdf')->fridays()->at('16:00');
Schedule::command('pulse:report --format=pptx')->fridays()->at('16:05');
