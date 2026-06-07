<?php

namespace App\Jobs;

use App\Models\Research;
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

        $researchSummaryService->generateAndStoreForResearch($research);
    }
}