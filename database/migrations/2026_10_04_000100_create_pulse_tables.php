<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code', 30)->nullable()->unique();
            $t->string('programme')->nullable()->index();     // e.g. "Retail Digital Lending"
            $t->text('description')->nullable();
            $t->string('phase', 30)->default('Discovery');
            $t->string('status', 20)->default('Active')->index();
            $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $t->date('start_date')->nullable();
            $t->date('target_date')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('project_user', function (Blueprint $t) {
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->primary(['project_id', 'user_id']);
        });

        Schema::create('milestones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->date('due_date')->nullable();
            $t->string('status', 20)->default('Not started');
            $t->date('completed_on')->nullable();
            $t->timestamps();
        });

        // One status update per project per reporting week (Monday-based).
        Schema::create('weekly_updates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->date('week_start')->index();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->char('rag', 1)->default('G');
            $t->unsignedTinyInteger('progress')->nullable();
            $t->string('phase', 30)->nullable();
            $t->text('summary')->nullable();
            $t->text('achievements')->nullable();   // one item per line
            $t->text('critical_path')->nullable();  // blockers / key items in the critical path
            $t->text('next_steps')->nullable();
            $t->text('support_needed')->nullable(); // escalations to leadership
            $t->json('metrics')->nullable();        // [{label, value, target, unit}]
            $t->timestamps();
            $t->unique(['project_id', 'week_start']);
        });

        // RAID log + production issues
        Schema::create('issues', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('kind', 30)->default('Issue')->index();
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('severity', 10)->default('Medium');
            $t->string('status', 20)->default('Open')->index();
            $t->string('owner_name')->nullable();
            $t->date('due_date')->nullable();
            $t->text('latest_note')->nullable();
            $t->date('resolved_on')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('report_runs', function (Blueprint $t) {
            $t->id();
            $t->date('week_start');
            $t->string('format', 5);
            $t->string('programme')->nullable();
            $t->string('file');
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['report_runs', 'issues', 'weekly_updates', 'milestones', 'project_user', 'projects'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
