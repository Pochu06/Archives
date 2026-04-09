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
        Schema::create('research_drafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('title')->nullable();
            $table->longText('abstract')->nullable();
            $table->longText('introduction')->nullable();
            $table->longText('methodology')->nullable();
            $table->longText('results')->nullable();
            $table->longText('discussion')->nullable();
            $table->longText('references')->nullable();
            $table->longText('conclusion')->nullable();
            $table->longText('recommendations')->nullable();
            $table->text('keywords')->nullable();
            $table->text('authors')->nullable();
            $table->unsignedBigInteger('college_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->integer('publication_year')->nullable();
            $table->timestamp('last_saved_at')->useCurrent();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('college_id')->references('id')->on('colleges')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('research_drafts');
    }
};
