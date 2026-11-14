<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_history') && ! Schema::hasIndex('project_history', ['project_id', 'id'])) {
            Schema::table('project_history', function (Blueprint $table) {
                $table->index(['project_id', 'id'], 'project_history_project_id_id_idx');
            });
        }

        if (Schema::hasTable('project_history') && ! Schema::hasIndex('project_history', ['project_id', 'created_at', 'id'])) {
            Schema::table('project_history', function (Blueprint $table) {
                $table->index(['project_id', 'created_at', 'id'], 'project_history_project_created_idx');
            });
        }

        if (Schema::hasTable('project_attachments') && ! Schema::hasIndex('project_attachments', ['project_id', 'uploaded_at', 'id'])) {
            Schema::table('project_attachments', function (Blueprint $table) {
                $table->index(['project_id', 'uploaded_at', 'id'], 'project_attachments_project_uploaded_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_history') && Schema::hasIndex('project_history', 'project_history_project_id_id_idx')) {
            Schema::table('project_history', function (Blueprint $table) {
                $table->dropIndex('project_history_project_id_id_idx');
            });
        }

        if (Schema::hasTable('project_history') && Schema::hasIndex('project_history', 'project_history_project_created_idx')) {
            Schema::table('project_history', function (Blueprint $table) {
                $table->dropIndex('project_history_project_created_idx');
            });
        }

        if (Schema::hasTable('project_attachments') && Schema::hasIndex('project_attachments', 'project_attachments_project_uploaded_idx')) {
            Schema::table('project_attachments', function (Blueprint $table) {
                $table->dropIndex('project_attachments_project_uploaded_idx');
            });
        }
    }
};
