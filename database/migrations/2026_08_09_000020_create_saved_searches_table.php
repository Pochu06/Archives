<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('context', 100)->default('research.index');
            $table->string('name', 100);
            $table->json('filters');
            $table->timestamps();

            $table->unique(['user_id', 'context', 'name']);
            $table->index(['user_id', 'context']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('saved_searches');
    }
};
