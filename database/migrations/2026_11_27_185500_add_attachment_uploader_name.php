<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_attachments', function (Blueprint $table) {
            $table->string('uploader_name', 100)->nullable()->after('uploaded_by');
        });

        $logs = DB::table('activity_logs')
            ->where('event_type', 'attachment_added')
            ->orderBy('id')
            ->get(['project_id', 'author', 'message']);

        foreach ($logs as $log) {
            if (! preg_match('/^資料「(.+)」を添付$/u', (string) $log->message, $matches)) {
                continue;
            }

            $author = trim((string) $log->author);

            if ($author === '' || $author === '不明') {
                continue;
            }

            DB::table('project_attachments')
                ->where('project_id', $log->project_id)
                ->where('original_file_name', $matches[1])
                ->whereNull('uploader_name')
                ->update(['uploader_name' => $author]);
        }
    }

    public function down(): void
    {
        Schema::table('project_attachments', function (Blueprint $table) {
            $table->dropColumn('uploader_name');
        });
    }
};
