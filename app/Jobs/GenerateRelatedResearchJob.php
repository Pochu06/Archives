<?php

namespace App\Jobs;

use App\Models\Research;
use App\Notifications\InAppAlertNotification;
use App\Services\RelatedResearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

class GenerateRelatedResearchJob
{
    use Dispatchable;
    use Queueable;

    public function __construct(private readonly int $researchId)
    {
    }

    public function handle(RelatedResearchService $relatedResearchService): void
    {
        $research = Research::find($this->researchId);

        if (! $research) {
            return;
        }

        $generated = $relatedResearchService->generateAndStoreForResearch($research);

        if (! $generated) {
            return;
        }

        $recipient = $research->user()->first();

        if (! $recipient) {
            return;
        }

        $recipient->notify(new InAppAlertNotification([
            'type' => 'ai_processing_complete',
            'title' => 'Related research ready',
            'message' => 'Similar papers for "'.$research->title.'" are ready to review.',
            'action_url' => route('research.show', $research->id),
            'action_label' => 'View Matches',
            'icon' => 'fa-diagram-project',
            'level' => 'success',
        ]));
    }
}