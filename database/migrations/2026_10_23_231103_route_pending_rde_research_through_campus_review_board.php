<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('research')
            ->where('status', 'pending_rde')
            ->orderBy('id')
            ->chunkById(500, function ($papers) {
                foreach ($papers as $paper) {
                    DB::table('research')
                        ->where('id', $paper->id)
                        ->update(['status' => 'pending_campus_review']);

                    DB::table('submission_status_events')->insert([
                        'research_id' => $paper->id,
                        'actor_id' => null,
                        'action' => 'workflow_routed_to_campus_review_board',
                        'from_status' => 'pending_rde',
                        'to_status' => 'pending_campus_review',
                        'notes' => 'Queued for the newly required Campus Review Board stage.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('submission_status_events')
            ->where('action', 'workflow_routed_to_campus_review_board')
            ->orderBy('id')
            ->chunkById(500, function ($events) {
                foreach ($events as $event) {
                    DB::table('research')
                        ->where('id', $event->research_id)
                        ->where('status', 'pending_campus_review')
                        ->whereNull('campus_review_board_reviewed_at')
                        ->update(['status' => 'pending_rde']);
                }
            });

        DB::table('submission_status_events')
            ->where('action', 'workflow_routed_to_campus_review_board')
            ->delete();
    }
};
