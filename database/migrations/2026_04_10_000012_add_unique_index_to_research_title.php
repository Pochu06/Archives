<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasDuplicateTitles = DB::table('research')
            ->select('title')
            ->groupBy('title')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateTitles) {
            return;
        }

        Schema::table('research', function (Blueprint $table) {
            $table->unique('title', 'research_title_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('research', 'title')) {
            return;
        }

        $sm = Schema::getConnection()->getSchemaBuilder();
        $indexes = method_exists($sm, 'getIndexes') ? $sm->getIndexes('research') : [];
        $hasUniqueIndex = collect($indexes)->contains(function (array $index) {
            return ($index['name'] ?? null) === 'research_title_unique';
        });

        if (! $hasUniqueIndex) {
            return;
        }

        Schema::table('research', function (Blueprint $table) {
            $table->dropUnique('research_title_unique');
        });
    }
};