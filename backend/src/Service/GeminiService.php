<?php

namespace App\Service;

class GeminiService
{
    private const GEMINI_URL =
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent';

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

Return ONLY valid JSON with exactly these fields:

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
- Do not invent information.
- If information is missing, use an empty string or empty array.
- skills must be an array of strings.
- languages must be an array of strings.
- certifications must be an array of strings.
- experience and education must be arrays.
- Return JSON only.
- Do not use markdown.

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
- analysis must explain the match clearly.
- Do not invent information.
- Base the score only on the information provided.
- Return JSON only.
- Do not use markdown.

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
         *
         * Gemini can temporarily return:
         *
         * 502 = Bad Gateway
         * 503 = Service Unavailable
         * 429 = Too Many Requests / quota
         *
         * We retry only temporary server errors.
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

                /*
                 * Connection timeout.
                 */
                CURLOPT_CONNECTTIMEOUT => 15,

                /*
                 * Maximum request time.
                 */
                CURLOPT_TIMEOUT => 90,

                /*
                 * Force IPv4.
                 */
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,

                /*
                 * Fresh connection.
                 */
                CURLOPT_FRESH_CONNECT => true,
                CURLOPT_FORBID_REUSE => true,

                /*
                 * SSL verification.
                 */
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

                    /*
                     * 2, 4, 6 seconds
                     */
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

                    /*
                     * Exponential-ish backoff:
                     *
                     * attempt 1 -> 2 seconds
                     * attempt 2 -> 4 seconds
                     * attempt 3 -> 6 seconds
                     */

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
        '/^```(?:json)?\s*/i',
        '',
        $generatedText
    );

    $generatedText = preg_replace(
        '/\s*```$/',
        '',
        $generatedText
    );

    $generatedText = trim($generatedText);

    /*
     * =========================================================
     * FIRST ATTEMPT: NORMAL JSON DECODE
     * =========================================================
     */

    $result = json_decode(
        $generatedText,
        true
    );

    if (
        json_last_error() === JSON_ERROR_NONE &&
        is_array($result)
    ) {
        return $result;
    }

    /*
     * =========================================================
     * SECOND ATTEMPT: EXTRACT THE JSON OBJECT
     * =========================================================
     *
     * Gemini occasionally adds extra characters before or
     * after the JSON object.
     */

    $firstBrace = strpos($generatedText, '{');
    $lastBrace = strrpos($generatedText, '}');

    if (
        $firstBrace !== false &&
        $lastBrace !== false &&
        $lastBrace > $firstBrace
    ) {
        $jsonOnly = substr(
            $generatedText,
            $firstBrace,
            $lastBrace - $firstBrace + 1
        );

        $result = json_decode(
            $jsonOnly,
            true
        );

        if (
            json_last_error() === JSON_ERROR_NONE &&
            is_array($result)
        ) {
            return $result;
        }
    }

    /*
     * =========================================================
     * JSON ERROR
     * =========================================================
     */

    throw new \RuntimeException(
        'Gemini did not return valid JSON: '
        . $generatedText
    );
}
}