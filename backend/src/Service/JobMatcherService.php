<?php

namespace App\Service;

class JobMatcherService
{
    public function __construct(
        private GeminiService $geminiService
    ) {
    }

    public function match(string $cvText, string $jobText): array
    {
        return $this->geminiService->matchCvToJob(
            $cvText,
            $jobText
        );
    }
}
