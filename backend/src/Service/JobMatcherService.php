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
        $prompt = <<<PROMPT
You are an AI job matching system.

Compare the following CV with the following job offer.

Return ONLY valid JSON with exactly these fields:

{
    "score": 0,
    "matchedSkills": [],
    "missingSkills": [],
    "analysis": ""
}

Rules:
- score must be an integer between 0 and 100.
- matchedSkills must be an array of strings.
- missingSkills must be an array of strings.
- analysis must briefly explain the match.
- Do not invent information.
- Return JSON only.
- Do not use markdown.

CV:
$cvText

JOB:
$jobText
PROMPT;

        // We will connect this to Gemini in the next step.
        throw new \RuntimeException(
            'JobMatcherService is not connected to Gemini yet.'
        );
    }
}