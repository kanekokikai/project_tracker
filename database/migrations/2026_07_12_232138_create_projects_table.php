<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('projects')) {
            return;
        }

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('status', ['未着手', '進行中', 'レビュー中', '保留中', '完了', '中止'])->default('未着手');
            $table->foreignId('parent_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->json('team_members')->nullable();
            $table->string('department', 50)->default('選択なし');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
