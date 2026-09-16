<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AdzunaService
{
    private HttpClientInterface $client;
    private string $appId;
    private string $appKey;

    public function __construct(
        HttpClientInterface $client,
        string $appId,
        string $appKey
    ) {
        $this->client = $client;
        $this->appId = $appId;
        $this->appKey = $appKey;
    }

    /**
     * Search jobs on Adzuna.
     */
    public function searchJobs(
        string $country = 'gb',
        string $keyword = '',
        int $page = 1,
        int $resultsPerPage = 20
    ): array {
        $url = sprintf(
            'https://api.adzuna.com/v1/api/jobs/%s/search/%d',
            strtolower($country),
            $page
        );

        $query = [
            'app_id' => $this->appId,
            'app_key' => $this->appKey,
            'results_per_page' => $resultsPerPage,
            'content-type' => 'application/json',
        ];

        if ($keyword !== '') {
            $query['what'] = $keyword;
        }

        $response = $this->client->request(
            'GET',
            $url,
            [
                'query' => $query,
            ]
        );

        return $response->toArray();
    }
}
