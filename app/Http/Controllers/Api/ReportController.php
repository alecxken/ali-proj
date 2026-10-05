<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReportRun;
use App\Services\PortfolioSnapshot;
use App\Services\ReportGenerator;
use App\Support\Pulse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function preview(Request $request, PortfolioSnapshot $snapshots)
    {
        return $snapshots->build(Pulse::weekStart($request->query('week')), $request->query('programme'));
    }

    public function history()
    {
        return ReportRun::with('user:id,name')->latest()->limit(30)->get();
    }

    public function generate(Request $request, ReportGenerator $generator)
    {
        $data = $request->validate([
            'week' => 'required|date', 'format' => ['required', Rule::in(['pdf', 'pptx'])],
            'programme' => 'nullable|string|max:160',
        ]);
        try {
            $run = $generator->generate(Pulse::weekStart($data['week']), $data['format'], $data['programme'] ?? null, $request->user());
        } catch (\RuntimeException $e) {
            report($e);
            return response()->json(['message' => 'The report could not be generated. Check that Python and its report packages are installed (see README).'], 500);
        }

        return response()->json($run, 201);
    }

    public function download(ReportRun $run)
    {
        $path = config('pulse.reports.disk_path').DIRECTORY_SEPARATOR.basename($run->file);
        abort_unless(File::exists($path), 404, 'File no longer on the server — generate it again.');

        return response()->download($path, $run->file);
    }
}
