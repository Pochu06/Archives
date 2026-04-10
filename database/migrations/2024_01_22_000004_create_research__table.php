<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('research', function (Blueprint $table) {
            $table->id();
            $table->string('title', 500);
            $table->text('abstract');
            $table->string('keywords', 500);
            $table->string('authors', 500);
            $table->foreignId('college_id')->constrained('colleges')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('adviser_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status')->default('pending_college');
            $table->integer('publication_year');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('research');
    }
};
