<?php

namespace App\Jobs;

use App\Models\Research;
use App\Notifications\InAppAlertNotification;
use App\Services\ResearchSummaryService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateResearchSummaryJob
{
    use Dispatchable;
    use Queueable;

    public function __construct(private readonly int $researchId)
    {
    }

    public function handle(ResearchSummaryService $researchSummaryService): void
    {
        $research = Research::find($this->researchId);

        if (! $research) {
            return;
        }

        $generated = $researchSummaryService->generateAndStoreForResearch($research);

        if (! $generated) {
            return;
        }

        $recipient = $research->user()->first();

        if (! $recipient) {
            return;
        }

        $recipient->notify(new InAppAlertNotification([
            'type' => 'ai_processing_complete',
            'title' => 'AI summary ready',
            'message' => 'The AI summary for "'.$research->title.'" has finished processing.',
            'action_url' => route('research.show', $research->id),
            'action_label' => 'View Research',
            'icon' => 'fa-wand-magic-sparkles',
            'level' => 'success',
        ]));
    }
}