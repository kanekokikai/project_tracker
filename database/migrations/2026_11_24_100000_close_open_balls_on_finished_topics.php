<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $finished = DB::table('projects')
            ->whereIn('status', ['完了', '中止'])
            ->pluck('id');

        if ($finished->isEmpty()) {
            return;
        }

        DB::table('project_balls')
            ->where('status', 'open')
            ->whereIn('project_id', $finished->all())
            ->update([
                'status' => 'done',
                'done_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }
};
