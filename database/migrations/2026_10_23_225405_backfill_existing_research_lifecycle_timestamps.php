<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('research')
            ->whereNull('submitted_at')
            ->select(['id', 'created_at', 'user_id'])
            ->orderBy('id')
            ->chunkById(500, function ($papers) {
                foreach ($papers as $paper) {
                    DB::table('research')
                        ->where('id', $paper->id)
                        ->whereNull('submitted_at')
                        ->update([
                            'submitted_at' => $paper->created_at,
                            'submitted_by' => $paper->user_id,
                        ]);
                }
            });

        DB::table('research')
            ->whereNotNull('approved_at')
            ->where('status', 'approved')
            ->orderBy('id')
            ->chunkById(500, function ($papers) {
                foreach ($papers as $paper) {
                    DB::table('research')
                        ->where('id', $paper->id)
                        ->whereNull('accepted_at')
                        ->update([
                            'accepted_at' => $paper->approved_at,
                            'accepted_by' => $paper->approved_by,
                            'published_at' => $paper->approved_at,
                            'published_by' => $paper->approved_by,
                        ]);
                }
            });

        $this->backfillStageFromEvents('college_reviewed', [
            'approved_by_college',
            'rejected_by_college',
            'revision_requested_by_college',
            'bulk_approved_college',
            'bulk_rejected_college',
        ]);
        $this->backfillStageFromEvents('rde_reviewed', [
            'approved_by_rde',
            'rejected_by_rde',
            'revision_requested_by_rde',
            'bulk_approved_rde',
            'bulk_rejected_rde',
        ]);

        DB::table('research')
            ->where('status', 'approved')
            ->whereNotNull('approved_at')
            ->whereNull('rde_reviewed_at')
            ->update([
                'rde_reviewed_at' => DB::raw('approved_at'),
                'rde_reviewed_by' => DB::raw('approved_by'),
            ]);
    }

    private function backfillStageFromEvents(string $stage, array $actions): void
    {
        $timestampColumn = $stage.'_at';
        $actorColumn = $stage.'_by';

        DB::table('submission_status_events')
            ->whereIn('action', $actions)
            ->orderBy('id')
            ->chunkById(500, function ($events) use ($timestampColumn, $actorColumn) {
                foreach ($events as $event) {
                    DB::table('research')
                        ->where('id', $event->research_id)
                        ->whereNull($timestampColumn)
                        ->update([
                            $timestampColumn => $event->created_at,
                            $actorColumn => $event->actor_id,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Backfilled values are retained to avoid erasing lifecycle history if this migration is rolled back.
    }
};
