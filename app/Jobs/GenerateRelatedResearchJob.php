<?php

namespace App\Jobs;

use App\Models\Research;
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

        $relatedResearchService->generateAndStoreForResearch($research);
    }
}