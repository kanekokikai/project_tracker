<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_balls') || Schema::hasColumn('project_balls', 'group_id')) {
            return;
        }

        Schema::table('project_balls', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->after('project_id');
            $table->index('group_id');
        });

        $this->linkExistingThrows();
    }

    private function linkExistingThrows(): void
    {
        $buckets = [];

        foreach (DB::table('project_balls')->whereNull('group_id')->orderBy('id')->get() as $ball) {
            $key = implode("\0", [
                $ball->project_id,
                $ball->task,
                $ball->due_date ?? '',
                $ball->created_by,
                $ball->created_at,
            ]);
            $buckets[$key][] = $ball->id;
        }

        foreach ($buckets as $ids) {
            if (count($ids) < 2) {
                continue;
            }

            DB::table('project_balls')->whereIn('id', $ids)->update(['group_id' => $ids[0]]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_balls') || ! Schema::hasColumn('project_balls', 'group_id')) {
            return;
        }

        Schema::table('project_balls', function (Blueprint $table) {
            $table->dropIndex(['group_id']);
            $table->dropColumn('group_id');
        });
    }
};
