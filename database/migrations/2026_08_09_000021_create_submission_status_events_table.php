<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('submission_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_id')->constrained('research')->onDelete('cascade');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100);
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['research_id', 'created_at']);
            $table->index(['actor_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('submission_status_events');
    }
};
