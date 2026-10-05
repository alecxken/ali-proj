<?php

namespace Database\Seeders;

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Models\WeeklyUpdate;
use App\Support\Pulse;
use Illuminate\Database\Seeder;

/**
 * Demo data modelled on the RDB weekly tracker email. Dates are relative to the
 * current week so the dashboard always looks "live" after `php artisan migrate --seed`.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $pw = 'Pulse@2026';
        $u = fn ($name, $email, $role, $title) => User::updateOrCreate(['email' => $email],
            ['name' => $name, 'role' => $role, 'title' => $title, 'password' => $pw, 'active' => true]);

        $admin = $u('System Admin', 'admin@pulse.local', 'admin', 'Administrator');
        $pmo = $u('Abdallah Ali', 'abdallah@pulse.local', 'pmo', 'Retail Digital Solutions & Innovation Lead');
        $patrick = $u('Patrick Mwangi', 'patrick@pulse.local', 'owner', 'Delivery Lead – Digital Lending');
        $morphael = $u('Morphael Otieno', 'morphael@pulse.local', 'contributor', 'Data Engineer');
        $qa = $u('Grace Wanjiku', 'grace@pulse.local', 'contributor', 'QA Lead');
        $u('Director Products', 'director@pulse.local', 'viewer', 'Director, Products');

        $w = Pulse::weekStart();
        $prev = $w->subWeek();
        $d = fn (int $days) => $w->addDays($days)->toDateString();
        $m = fn ($label, $value, $target = 100, $unit = '%') => compact('label', 'value', 'target', 'unit');

        $project = function (array $attrs, array $members, array $milestones, array $updates) {
            $p = Project::updateOrCreate(['code' => $attrs['code']], $attrs);
            $p->members()->sync(collect($members)->pluck('id'));
            $p->milestones()->delete();
            foreach ($milestones as [$title, $due, $status]) {
                $p->milestones()->create(['title' => $title, 'due_date' => $due, 'status' => $status,
                    'completed_on' => $status === 'Done' ? $due : null]);
            }
            foreach ($updates as $week => $data) {
                WeeklyUpdate::updateOrCreate(['project_id' => $p->id, 'week_start' => $week], $data);
            }
            return $p;
        };
        $prog = 'Digital Lending (SASA)';

        $sasa = $project(
            ['code' => 'SASA-DWH', 'name' => 'SASA DB migration from Data Warehouse', 'programme' => $prog, 'phase' => 'UAT',
             'status' => 'Active', 'owner_id' => $patrick->id, 'start_date' => $d(-90), 'target_date' => $d(25), 'sort_order' => 1,
             'description' => 'Move SASA lending data off the shared data warehouse onto a dedicated database.'],
            [$morphael, $qa],
            [['Test scenarios uploaded to TestRail', $d(-4), 'Done'], ['Testers set up in T24 UAT with loan limits', $d(-4), 'Done'],
             ['UAT sign-off', $d(11), 'In progress'], ['Production cut-over', $d(25), 'Not started']],
            [
                $prev->toDateString() => ['rag' => 'A', 'progress' => 55, 'phase' => 'SIT', 'user_id' => $patrick->id,
                    'summary' => 'SIT closing; UAT preparation under way.',
                    'critical_path' => 'Test scenarios pending upload to TestRail',
                    'metrics' => [$m('Overall coverage', 0), $m('Overall pass rate', 0)]],
                $w->toDateString() => ['rag' => 'R', 'progress' => 60, 'phase' => 'UAT', 'user_id' => $patrick->id,
                    'summary' => 'UAT started this week but mobile loan journeys are blocked on both iOS and Android.',
                    'achievements' => "UAT activities commenced following upload of test scenarios on TestRail\nLoan limits assigned to all testers across all loan products\nAll testers set up in the T24 UAT environment",
                    'critical_path' => "iOS: app crashes during the loan application, so no application can be completed\nAndroid: loan can be initiated but is not visible in the app or SASA Portal — not reaching SASA or T24",
                    'next_steps' => "Vendor to fix iOS crash and redeploy UAT build\nTrace Android loan request through SASA → T24 integration",
                    'support_needed' => 'Priority vendor fix for the iOS crash to protect the UAT timeline',
                    'metrics' => [$m('Overall coverage', 23), $m('Overall pass rate', 12), $m('Coverage on executable tests', 23),
                        $m('Pass rate against tested scripts', 52)]],
            ]);

        $pilot = $project(
            ['code' => 'LFA-PRP', 'name' => 'LFA Principal Reduction Pilot regressions', 'programme' => $prog, 'phase' => 'Pilot',
             'status' => 'Active', 'owner_id' => $patrick->id, 'start_date' => $d(-60), 'target_date' => $d(10), 'sort_order' => 2],
            [$qa],
            [['Android pilot regression', $d(-3), 'Done'], ['iOS pilot regression', $d(-3), 'Done'],
             ['Credit Ops T24 validation of booked loans', $d(4), 'In progress'], ['Pilot go / no-go', $d(10), 'Not started']],
            [
                $prev->toDateString() => ['rag' => 'A', 'progress' => 60, 'phase' => 'Pilot', 'user_id' => $qa->id,
                    'summary' => 'Pilot regressions in progress on mobile.',
                    'metrics' => [$m('Coverage', 52), $m('Pass rate (executed)', 90)]],
                $w->toDateString() => ['rag' => 'A', 'progress' => 79, 'phase' => 'Pilot', 'user_id' => $qa->id,
                    'summary' => 'Mobile regressions complete; waiting on Credit Ops to validate booked loans in T24.',
                    'achievements' => "Android regression at 100% coverage, 100% pass\niOS regression at 100% coverage, 91% pass",
                    'critical_path' => 'Booked loans pending T24 validation by the Credit Operations team',
                    'next_steps' => "Credit Ops to complete T24 checks\nRetest the failed iOS scripts",
                    'metrics' => [$m('Coverage', 79), $m('Pass rate (executed)', 95), $m('Android pass rate', 100),
                        $m('iOS pass rate', 91), $m('Credit Ops checks coverage', 0)]],
            ]);

        $cob = $project(
            ['code' => 'LFA-COB', 'name' => 'LFA – Personal loan COB issues', 'programme' => $prog, 'phase' => 'UAT',
             'status' => 'Active', 'owner_id' => $patrick->id, 'start_date' => $d(-45), 'target_date' => $d(4), 'sort_order' => 3],
            [$qa],
            [['UAT execution', $d(-2), 'Done'], ['UAT sign-off', $d(4), 'Blocked']],
            [$w->toDateString() => ['rag' => 'A', 'progress' => 92, 'phase' => 'UAT', 'user_id' => $qa->id,
                'summary' => 'UAT fully executed; sign-off delayed by the shared T24 environment.',
                'achievements' => 'UAT at 100% coverage and 92% pass rate',
                'critical_path' => "Slow issue resolution\nShared T24 test environment — COBs cannot be run on demand",
                'support_needed' => 'Dedicated COB window on the T24 test environment (escalated)',
                'metrics' => [$m('UAT coverage', 100), $m('UAT pass rate', 92)]]]);

        $od = $project(
            ['code' => 'LFA-OD', 'name' => 'LFA – Overdraft', 'programme' => $prog, 'phase' => 'UAT', 'status' => 'Active',
             'owner_id' => $patrick->id, 'start_date' => $d(-120), 'target_date' => $d(30), 'sort_order' => 4],
            [$qa, $morphael],
            [['T24 build', $d(-30), 'Done'], ['SASA app & APIs', $d(-25), 'Done'], ['CM APIs', $d(-20), 'Done'],
             ['NCBA Now interface', $d(-15), 'Done'], ['SIT closure', $d(-6), 'In progress'], ['UAT sign-off', $d(18), 'Not started']],
            [$w->toDateString() => ['rag' => 'G', 'progress' => 70, 'phase' => 'UAT', 'user_id' => $patrick->id,
                'summary' => 'Development complete across T24, SASA, CM and NCBA Now; UAT kicked off.',
                'achievements' => "T24, SASA App & APIs, CM APIs and NCBA Now interface all done\nUAT kicked off",
                'critical_path' => 'SIT still open past its planned close date',
                'next_steps' => "Complete tester set-up and limit allocation on UAT\nClose out SIT",
                'metrics' => [$m('Dev complete', 100), $m('UAT coverage', 0)]]]);

        $mi = $project(
            ['code' => 'PH-MI', 'name' => 'Product House MI & reporting framework', 'programme' => 'Product House',
             'phase' => 'Design', 'status' => 'Active', 'owner_id' => $pmo->id, 'start_date' => $d(-14), 'target_date' => $d(90), 'sort_order' => 1,
             'description' => '24 management reports and dashboards for the Director Products and Retail leadership (Power BI, email, PPT).'],
            [$morphael],
            collect(['Retail Product House Executive Dashboard', 'Retail Balance Sheet Dashboard', 'Deposits Growth Dashboard',
                'Retail Assets Dashboard', 'Product Performance Scorecard', 'Digital Banking Performance Dashboard',
                'Pipeline Management & Digital Sales Funnel', '90-Day Strategy Execution Tracker'])
                ->map(fn ($t, $i) => [$t, $d(14 + $i * 7), $i === 0 ? 'In progress' : 'Not started'])->all(),
            [$w->toDateString() => ['rag' => 'G', 'progress' => 10, 'phase' => 'Design', 'user_id' => $pmo->id,
                'summary' => 'Report catalogue agreed (24 reports); building the executive dashboard first.',
                'achievements' => "Agreed report catalogue, cycle, consumers and owners for 24 reports\nStarted Executive Dashboard data model",
                'next_steps' => "Confirm data sources for Balance Sheet and Deposits dashboards\nAgree Power BI workspace access",
                'metrics' => [$m('Reports specified', 24, 24, ''), $m('Reports live', 0, 24, '')]]]);

        Issue::query()->delete();
        $issue = fn (array $a) => Issue::create($a + ['user_id' => $pmo->id]);
        $issue(['project_id' => $sasa->id, 'kind' => 'Production issue', 'title' => 'SME Lending: customers not receiving disbursements',
            'severity' => 'Critical', 'status' => 'In progress', 'owner_name' => 'Vendor / CM team', 'due_date' => $d(0),
            'latest_note' => 'CM restarted at 1am; 4 customers retested but issue persists. Resolution call in progress.']);
        $issue(['project_id' => $sasa->id, 'kind' => 'Production issue', 'title' => 'Instability on data warehouse',
            'severity' => 'High', 'status' => 'Monitoring', 'owner_name' => 'Patrick Mwangi, Morphael Otieno', 'due_date' => $d(-3),
            'latest_note' => 'Bank-side issue. Monitoring tool shared; fix provided and regressions ongoing. Customer impact being tracked.']);
        $issue(['project_id' => $pilot->id, 'kind' => 'Production issue', 'title' => 'Wrong next repayment date displayed',
            'severity' => 'Medium', 'status' => 'Monitoring', 'owner_name' => 'Vendor', 'due_date' => $d(-3),
            'latest_note' => 'Resolved. RCA to be shared.']);
        $issue(['project_id' => $cob->id, 'kind' => 'Dependency', 'title' => 'Shared T24 test environment — COBs cannot run on demand',
            'severity' => 'High', 'status' => 'Open', 'owner_name' => 'T24 environment team', 'due_date' => $d(4),
            'latest_note' => 'Escalated for a dedicated COB window.']);
        $issue(['project_id' => $od->id, 'kind' => 'Risk', 'title' => 'SIT overrun may compress UAT window',
            'severity' => 'Medium', 'status' => 'Open', 'owner_name' => 'Patrick Mwangi', 'due_date' => $d(7)]);
        $issue(['project_id' => $mi->id, 'kind' => 'Dependency', 'title' => 'Data warehouse stability needed for daily dashboards',
            'severity' => 'Medium', 'status' => 'Open', 'owner_name' => 'Retail Analytics']);
    }
}
