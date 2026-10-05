<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\PortfolioSnapshot;
use App\Support\Pulse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PortfolioSnapshot $snapshots)
    {
        $week = Pulse::weekStart($request->query('week'));
        $snap = $snapshots->build($week, $request->query('programme'));
        $user = $request->user();

        // Projects this user should update this week but hasn't
        $mine = Project::with('members:id')->where('status', 'Active')->get()
            ->filter(fn ($p) => $user->canEditProject($p))->pluck('id')->all();
        $snap['my_pending'] = collect($snap['projects'])->filter(fn ($r) => $r['stale'] && in_array($r['id'], $mine))->values();
        $snap['programmes'] = Project::whereNotNull('programme')->distinct()->orderBy('programme')->pluck('programme');

        return $snap;
    }
}
