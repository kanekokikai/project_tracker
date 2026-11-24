<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topic_reminder_sends', function (Blueprint $table) {
            $table->id();
            $table->date('sent_on');
            $table->string('slot', 10);
            $table->string('kind', 20);
            $table->string('target', 191);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['sent_on', 'slot', 'kind', 'target']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topic_reminder_sends');
    }
};
