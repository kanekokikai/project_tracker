<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chatwork_members')) {
            return;
        }

        Schema::create('chatwork_members', function (Blueprint $table) {
            $table->id();
            $table->string('member_name', 100)->unique();
            $table->string('chatwork_account_id', 50);
            $table->string('note', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        $defaults = config('chatwork.member_mapping', []);
        $order = 1;

        foreach ($defaults as $name => $accountId) {
            DB::table('chatwork_members')->insert([
                'member_name' => $name,
                'chatwork_account_id' => $accountId,
                'note' => null,
                'sort_order' => $order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $order++;
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chatwork_members');
    }
};
