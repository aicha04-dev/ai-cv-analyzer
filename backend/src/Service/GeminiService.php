<?php

namespace App\Service;

class GeminiService
{
    private const GEMINI_URL =
    'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.7-flash:generateContent';
        public function __construct(
        private string $apiKey
    ) {
    }

    /**
     * Analyze a CV using Gemini.
     */
    public function analyzeCv(string $cvText): array
    {
        $prompt = <<<PROMPT
You are an AI CV analyzer.

Analyze the following CV.

Return ONLY one valid JSON object with exactly these fields:

{
    "name": "",
    "email": "",
    "phone": "",
    "summary": "",
    "skills": [],
    "experience": [],
    "education": [],
    "languages": [],
    "certifications": []
}

Rules:
- Return exactly ONE JSON object.
- Do not add any text before or after the JSON.
- Do not use markdown or code fences.
- Do not invent information.
- If information is missing, use an empty string or empty array.
- skills must be an array of strings.
- languages must be an array of strings.
- certifications must be an array of strings.
- experience must be an array of objects.
- education must be an array of objects.
- The JSON must be syntactically valid.

CV:

$cvText
PROMPT;

        return $this->sendRequest($prompt);
    }

    /**
     * Match a CV against a job offer using Gemini.
     */
    public function matchCvToJob(
        string $cvText,
        string $jobText
    ): array {
        $prompt = <<<PROMPT
You are an AI job matching system.

Compare the following CV with the following job offer.

Return ONLY one valid JSON object with exactly these fields:

{
    "score": 0,
    "matchedSkills": [],
    "missingSkills": [],
    "analysis": ""
}

Rules:
- Return exactly ONE JSON object.
- Do not add any text before or after the JSON.
- Do not use markdown or code fences.
- score must be an integer between 0 and 100.
- matchedSkills must be an array of strings.
- missingSkills must be an array of strings.
- analysis must be a string.
- Do not invent information.
- Base the score only on the information provided.
- The JSON must be syntactically valid.

CV:

$cvText

JOB OFFER:

$jobText
PROMPT;

        return $this->sendRequest($prompt);
    }

    /**
     * Send a request to Gemini.
     */
    private function sendRequest(string $prompt): array
    {
        if (trim($this->apiKey) === '') {
            throw new \RuntimeException(
                'GEMINI_API_KEY is not configured.'
            );
        }

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.2,
            ],
        ];

        $jsonPayload = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($jsonPayload === false) {
            throw new \RuntimeException(
                'Could not encode Gemini request.'
            );
        }

        /*
         * =========================================================
         * RETRY CONFIGURATION
         * =========================================================
         */

        $maxAttempts = 4;
        $lastError = '';

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $ch = curl_init(self::GEMINI_URL);

            if ($ch === false) {
                throw new \RuntimeException(
                    'Could not initialize cURL.'
                );
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonPayload,

                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'x-goog-api-key: ' . $this->apiKey,
                ],

                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 90,

                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,

                CURLOPT_FRESH_CONNECT => true,
                CURLOPT_FORBID_REUSE => true,

                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);

            $httpCode = curl_getinfo(
                $ch,
                CURLINFO_HTTP_CODE
            );

            curl_close($ch);

            /*
             * =====================================================
             * CURL / NETWORK ERROR
             * =====================================================
             */

            if ($response === false) {
                $lastError =
                    'Gemini cURL error: '
                    . $curlError
                    . ' (errno: '
                    . $curlErrno
                    . ')';

                if ($attempt < $maxAttempts) {
                    sleep($attempt * 2);
                    continue;
                }

                throw new \RuntimeException($lastError);
            }

            /*
             * =====================================================
             * QUOTA / RATE LIMIT
             * =====================================================
             */

            if ($httpCode === 429) {
                throw new \RuntimeException(
                    'Gemini API quota or rate limit exceeded. '
                    . 'Please try again later or check your Gemini API quota.'
                );
            }

            /*
             * =====================================================
             * TEMPORARY GEMINI SERVER ERROR
             * =====================================================
             */

            if (
                $httpCode === 502 ||
                $httpCode === 503 ||
                $httpCode === 504
            ) {
                $lastError =
                    "Gemini API returned HTTP {$httpCode}: {$response}";

                if ($attempt < $maxAttempts) {
                    sleep($attempt * 2);
                    continue;
                }

                throw new \RuntimeException(
                    'Gemini is temporarily unavailable after '
                    . $maxAttempts
                    . ' attempts. '
                    . 'Please try again later. '
                    . $lastError
                );
            }

            /*
             * =====================================================
             * OTHER HTTP ERRORS
             * =====================================================
             */

            if ($httpCode < 200 || $httpCode >= 300) {
                throw new \RuntimeException(
                    "Gemini API returned HTTP {$httpCode}: {$response}"
                );
            }

            /*
             * =====================================================
             * SUCCESS
             * =====================================================
             */

            return $this->processResponse($response);
        }

        throw new \RuntimeException(
            $lastError ?: 'Gemini request failed.'
        );
    }

    /**
     * Process Gemini response and extract JSON.
     */
    private function processResponse(string $body): array
    {
        /*
         * Decode the Gemini API response itself.
         */
        $data = json_decode($body, true);

        if (!is_array($data)) {
            throw new \RuntimeException(
                'Gemini returned an invalid API response: ' . $body
            );
        }

        /*
         * Get generated text.
         */
        $generatedText =
            $data['candidates'][0]['content']['parts'][0]['text']
            ?? '';

        if (
            !is_string($generatedText) ||
            trim($generatedText) === ''
        ) {
            throw new \RuntimeException(
                'Gemini returned an empty response.'
            );
        }

        $generatedText = trim($generatedText);

        /*
         * =========================================================
         * REMOVE MARKDOWN CODE FENCES
         * =========================================================
         */

        $generatedText = preg_replace(
            '/^\s*```(?:json)?\s*/i',
            '',
            $generatedText
        );

        $generatedText = preg_replace(
            '/\s*```\s*$/',
            '',
            $generatedText
        );

        $generatedText = trim($generatedText);

        /*
         * =========================================================
         * FIRST ATTEMPT
         * =========================================================
         *
         * Try the complete response directly.
         */

        $result = json_decode(
            $generatedText,
            true
        );

        if (
            json_last_error() === JSON_ERROR_NONE &&
            is_array($result)
        ) {
            return $this->validateResult($result);
        }

        /*
         * =========================================================
         * SECOND ATTEMPT
         * =========================================================
         *
         * Extract the first complete JSON object.
         *
         * This handles cases such as:
         *
         * Some text...
         * { ... }
         * Some extra text...
         */

        $jsonOnly = $this->extractFirstJsonObject($generatedText);

        if ($jsonOnly !== null) {
            $result = json_decode(
                $jsonOnly,
                true
            );

            if (
                json_last_error() === JSON_ERROR_NONE &&
                is_array($result)
            ) {
                return $this->validateResult($result);
            }
        }

        /*
         * =========================================================
         * JSON ERROR
         * =========================================================
         */

        $jsonError = json_last_error_msg();

        throw new \RuntimeException(
            'Gemini did not return valid JSON: '
            . $generatedText
            . ' | JSON error: '
            . $jsonError
        );
    }

    /**
     * Extract the first balanced JSON object from a string.
     *
     * This is more reliable than simply using the first "{" and
     * the last "}" because Gemini may accidentally return extra
     * braces or another JSON object after the valid one.
     */
    private function extractFirstJsonObject(string $text): ?string
    {
        $length = strlen($text);

        $start = strpos($text, '{');

        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escaped = false;

        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($char === '\\' && $inString) {
                $escaped = true;
                continue;
            }

            if ($char === '"') {
                $inString = !$inString;
                continue;
            }

            if ($inString) {
                continue;
            }

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr(
                        $text,
                        $start,
                        $i - $start + 1
                    );
                }
            }
        }

        return null;
    }

    /**
     * Validate and normalize Gemini's result.
     */
    private function validateResult(array $result): array
    {
        /*
         * CV analysis response.
         */
        if (
            array_key_exists('name', $result) ||
            array_key_exists('email', $result) ||
            array_key_exists('skills', $result)
        ) {
            $result['name'] =
                isset($result['name']) && is_string($result['name'])
                    ? $result['name']
                    : '';

            $result['email'] =
                isset($result['email']) && is_string($result['email'])
                    ? $result['email']
                    : '';

            $result['phone'] =
                isset($result['phone']) && is_string($result['phone'])
                    ? $result['phone']
                    : '';

            $result['summary'] =
                isset($result['summary']) && is_string($result['summary'])
                    ? $result['summary']
                    : '';

            $result['skills'] =
                isset($result['skills']) && is_array($result['skills'])
                    ? $result['skills']
                    : [];

            $result['experience'] =
                isset($result['experience']) && is_array($result['experience'])
                    ? $result['experience']
                    : [];

            $result['education'] =
                isset($result['education']) && is_array($result['education'])
                    ? $result['education']
                    : [];

            $result['languages'] =
                isset($result['languages']) && is_array($result['languages'])
                    ? $result['languages']
                    : [];

            $result['certifications'] =
                isset($result['certifications']) && is_array($result['certifications'])
                    ? $result['certifications']
                    : [];

            return $result;
        }

        /*
         * Job matching response.
         */
        if (
            array_key_exists('score', $result) ||
            array_key_exists('matchedSkills', $result)
        ) {
            $score = $result['score'] ?? 0;

            if (!is_numeric($score)) {
                $score = 0;
            }

            $score = (int) $score;

            $score = max(0, min(100, $score));

            $result['score'] = $score;

            $result['matchedSkills'] =
                isset($result['matchedSkills']) &&
                is_array($result['matchedSkills'])
                    ? $result['matchedSkills']
                    : [];

            $result['missingSkills'] =
                isset($result['missingSkills']) &&
                is_array($result['missingSkills'])
                    ? $result['missingSkills']
                    : [];

            $result['analysis'] =
                isset($result['analysis']) &&
                is_string($result['analysis'])
                    ? $result['analysis']
                    : '';

            return $result;
        }

        /*
         * Unknown response structure.
         */
        throw new \RuntimeException(
            'Gemini returned JSON, but the JSON structure is invalid: '
            . json_encode(
                $result,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        );
    }
}
