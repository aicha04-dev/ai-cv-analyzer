<?php

namespace App\Service;

class GeminiService
{
    /**
     * Gemini models are tried in this order.
     *
     * Primary:
     *   gemini-3.7-flash
     *
     * Fallbacks:
     *   gemini-3.6-flash
     *   gemini-3.5-flash
     */
    private const GEMINI_MODELS = [
        'gemini-3.7-flash',
        'gemini-3.6-flash',
        'gemini-3.5-flash',
    ];

    private const GEMINI_API_BASE_URL =
        'https://generativelanguage.googleapis.com/v1beta/models/';

    /**
     * Number of attempts for each model.
     */
    private const MAX_ATTEMPTS_PER_MODEL = 3;

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
     *
     * Each model gets its own retry cycle.
     *
     * Example:
     *
     * 3.7 Flash
     *   attempt 1 -> retry
     *   attempt 2 -> retry
     *   attempt 3 -> fallback
     *
     * 3.6 Flash
     *   attempt 1 -> retry
     *   attempt 2 -> retry
     *   attempt 3 -> fallback
     *
     * 3.5 Flash
     *   attempt 1 -> retry
     *   attempt 2 -> retry
     *   attempt 3 -> fail
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

        $errors = [];

        foreach (self::GEMINI_MODELS as $model) {
            $url =
                self::GEMINI_API_BASE_URL
                . $model
                . ':generateContent';

            for (
                $attempt = 1;
                $attempt <= self::MAX_ATTEMPTS_PER_MODEL;
                $attempt++
            ) {
                $result = $this->callGemini(
                    $url,
                    $jsonPayload
                );

                $httpCode = $result['httpCode'];
                $response = $result['response'];
                $curlError = $result['curlError'];
                $curlErrno = $result['curlErrno'];

                /*
                 * ---------------------------------------------------------
                 * CURL / NETWORK ERROR
                 * ---------------------------------------------------------
                 */

                if ($response === false) {
                    $error =
                        'Gemini cURL error: '
                        . $curlError
                        . ' (errno: '
                        . $curlErrno
                        . ')';

                    $errors[] =
                        "{$model}, attempt {$attempt}: {$error}";

                    if ($attempt < self::MAX_ATTEMPTS_PER_MODEL) {
                        $this->sleepWithExponentialBackoff($attempt);
                        continue;
                    }

                    break;
                }

                /*
                 * ---------------------------------------------------------
                 * SUCCESS
                 * ---------------------------------------------------------
                 */

                if ($httpCode >= 200 && $httpCode < 300) {
                    try {
                        return $this->processResponse($response);
                    } catch (\Throwable $e) {
                        /*
                         * The HTTP request succeeded, but Gemini returned
                         * something that could not be parsed.
                         *
                         * There is no benefit in changing models for a
                         * normal JSON parsing problem, so expose the error.
                         */
                        throw new \RuntimeException(
                            'Gemini returned an invalid response: '
                            . $e->getMessage()
                        );
                    }
                }

                /*
                 * ---------------------------------------------------------
                 * TRANSIENT ERRORS
                 *
                 * 408 = Request Timeout
                 * 429 = Rate Limit / Quota
                 * 500 = Internal Server Error
                 * 502 = Bad Gateway
                 * 503 = Service Unavailable
                 * 504 = Gateway Timeout
                 *
                 * These can be retried.
                 * ---------------------------------------------------------
                 */

                if (
                    $httpCode === 408 ||
                    $httpCode === 429 ||
                    $httpCode === 500 ||
                    $httpCode === 502 ||
                    $httpCode === 503 ||
                    $httpCode === 504
                ) {
                    $errors[] =
                        "{$model}, attempt {$attempt}: "
                        . "HTTP {$httpCode}: {$response}";

                    if ($attempt < self::MAX_ATTEMPTS_PER_MODEL) {
                        $this->sleepWithExponentialBackoff($attempt);
                        continue;
                    }

                    /*
                     * This model failed all attempts.
                     * Move to the next fallback model.
                     */
                    break;
                }

                /*
                 * ---------------------------------------------------------
                 * NON-RETRYABLE ERRORS
                 *
                 * 400 = Bad Request
                 * 401 = Invalid API key
                 * 403 = Permission denied
                 * 404 = Model not found
                 *
                 * These usually indicate a configuration/request problem.
                 * Do not waste time retrying the same request.
                 * ---------------------------------------------------------
                 */

                throw new \RuntimeException(
                    "Gemini API returned HTTP {$httpCode}: {$response}"
                );
            }
        }

        /*
         * -------------------------------------------------------------
         * ALL MODELS FAILED
         * -------------------------------------------------------------
         */

        throw new \RuntimeException(
            'All Gemini models are temporarily unavailable. '
            . 'Tried: '
            . implode(', ', self::GEMINI_MODELS)
            . '. '
            . 'Please try again later. '
            . 'Details: '
            . implode(' | ', $errors)
        );
    }

    /**
     * Execute one Gemini HTTP request.
     */
    private function callGemini(
        string $url,
        string $jsonPayload
    ): array {
        $ch = curl_init($url);

        if ($ch === false) {
            return [
                'response' => false,
                'httpCode' => 0,
                'curlError' => 'Could not initialize cURL.',
                'curlErrno' => 0,
            ];
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

        return [
            'response' => $response,
            'httpCode' => $httpCode,
            'curlError' => $curlError,
            'curlErrno' => $curlErrno,
        ];
    }

    /**
     * Exponential backoff with jitter.
     *
     * Attempt 1:
     *   roughly 2-3 seconds
     *
     * Attempt 2:
     *   roughly 4-5 seconds
     *
     * This avoids sending all retries at exactly the same time.
     */
    private function sleepWithExponentialBackoff(int $attempt): void
    {
        $baseDelay = 2 ** $attempt;

        /*
         * Add 0-1000 milliseconds of random jitter.
         */
        $jitterMilliseconds = random_int(0, 1000);

        $seconds =
            $baseDelay
            + ($jitterMilliseconds / 1000);

        usleep((int) ($seconds * 1_000_000));
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
                'Gemini returned an invalid API response: '
                . $body
            );
        }

        /*
         * Get generated text.
         */
        $generatedText =
            $data['candidates'][0]['content']['parts'][0]['text']
            ?? '';

        if (
            !is_string($generatedText)
            || trim($generatedText) === ''
        ) {
            throw new \RuntimeException(
                'Gemini returned an empty response.'
            );
        }

        $generatedText = trim($generatedText);

        /*
         * Remove markdown code fences if Gemini adds them.
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
         * First attempt:
         * Try the complete response directly.
         */
        $result = json_decode(
            $generatedText,
            true
        );

        if (
            json_last_error() === JSON_ERROR_NONE
            && is_array($result)
        ) {
            return $this->validateResult($result);
        }

        /*
         * Second attempt:
         * Extract the first balanced JSON object.
         */
        $jsonOnly =
            $this->extractFirstJsonObject($generatedText);

        if ($jsonOnly !== null) {
            $result = json_decode(
                $jsonOnly,
                true
            );

            if (
                json_last_error() === JSON_ERROR_NONE
                && is_array($result)
            ) {
                return $this->validateResult($result);
            }
        }

        /*
         * JSON error.
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
     */
    private function extractFirstJsonObject(
        string $text
    ): ?string {
        $length = strlen($text);

        $start = strpos($text, '{');

        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escaped = false;

        for (
            $i = $start;
            $i < $length;
            $i++
        ) {
            $char = $text[$i];

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if (
                $char === '\\'
                && $inString
            ) {
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
    private function validateResult(
        array $result
    ): array {
        /*
         * -------------------------------------------------------------
         * CV ANALYSIS RESPONSE
         * -------------------------------------------------------------
         */

        if (
            array_key_exists('name', $result)
            || array_key_exists('email', $result)
            || array_key_exists('skills', $result)
        ) {
            $result['name'] =
                isset($result['name'])
                && is_string($result['name'])
                    ? $result['name']
                    : '';

            $result['email'] =
                isset($result['email'])
                && is_string($result['email'])
                    ? $result['email']
                    : '';

            $result['phone'] =
                isset($result['phone'])
                && is_string($result['phone'])
                    ? $result['phone']
                    : '';

            $result['summary'] =
                isset($result['summary'])
                && is_string($result['summary'])
                    ? $result['summary']
                    : '';

            $result['skills'] =
                isset($result['skills'])
                && is_array($result['skills'])
                    ? $result['skills']
                    : [];

            $result['experience'] =
                isset($result['experience'])
                && is_array($result['experience'])
                    ? $result['experience']
                    : [];

            $result['education'] =
                isset($result['education'])
                && is_array($result['education'])
                    ? $result['education']
                    : [];

            $result['languages'] =
                isset($result['languages'])
                && is_array($result['languages'])
                    ? $result['languages']
                    : [];

            $result['certifications'] =
                isset($result['certifications'])
                && is_array($result['certifications'])
                    ? $result['certifications']
                    : [];

            return $result;
        }

        /*
         * -------------------------------------------------------------
         * JOB MATCHING RESPONSE
         * -------------------------------------------------------------
         */

        if (
            array_key_exists('score', $result)
            || array_key_exists('matchedSkills', $result)
        ) {
            $score = $result['score'] ?? 0;

            if (!is_numeric($score)) {
                $score = 0;
            }

            $score = (int) $score;

            $score = max(
                0,
                min(100, $score)
            );

            $result['score'] = $score;

            $result['matchedSkills'] =
                isset($result['matchedSkills'])
                && is_array($result['matchedSkills'])
                    ? $result['matchedSkills']
                    : [];

            $result['missingSkills'] =
                isset($result['missingSkills'])
                && is_array($result['missingSkills'])
                    ? $result['missingSkills']
                    : [];

            $result['analysis'] =
                isset($result['analysis'])
                && is_string($result['analysis'])
                    ? $result['analysis']
                    : '';

            return $result;
        }

        /*
         * -------------------------------------------------------------
         * UNKNOWN RESPONSE STRUCTURE
         * -------------------------------------------------------------
         */

        throw new \RuntimeException(
            'Gemini returned JSON, but the JSON structure is invalid: '
            . json_encode(
                $result,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            )
        );
    }
}
