<?php

namespace App\Controller;

use App\Repository\JobRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class JobSearchController extends AbstractController
{
#[Route(
    '/api/jobs/search',
    name: 'api_jobs_search',
    methods: ['GET'],
    priority: 100
)]    public function search(
        Request $request,
        JobRepository $jobRepository
    ): JsonResponse {
        $keyword = $request->query->get('keyword');
        $category = $request->query->get('category');
        $country = $request->query->get('country');
        $experience = $request->query->get('experience');

        $salaryMin = $request->query->get('salaryMin');
        $salaryMax = $request->query->get('salaryMax');

        /*
         * Convert salary values to float.
         */
        $salaryMinValue = null;
        $salaryMaxValue = null;

        if ($salaryMin !== null && $salaryMin !== '') {
            if (!is_numeric($salaryMin)) {
                return $this->json([
                    'error' => 'salaryMin must be a number.'
                ], 400);
            }

            $salaryMinValue = (float) $salaryMin;
        }

        if ($salaryMax !== null && $salaryMax !== '') {
            if (!is_numeric($salaryMax)) {
                return $this->json([
                    'error' => 'salaryMax must be a number.'
                ], 400);
            }

            $salaryMaxValue = (float) $salaryMax;
        }

        /*
         * Validate salary range.
         */
        if (
            $salaryMinValue !== null &&
            $salaryMaxValue !== null &&
            $salaryMinValue > $salaryMaxValue
        ) {
            return $this->json([
                'error' => 'salaryMin cannot be greater than salaryMax.'
            ], 400);
        }

        /*
         * Search database.
         */
        $jobs = $jobRepository->searchJobs(
            $keyword,
            $category,
            $country,
            $experience,
            $salaryMinValue,
            $salaryMaxValue
        );

        /*
         * Convert entities to JSON.
         */
        $results = [];

        foreach ($jobs as $job) {
            $results[] = [
                'id' => $job->getId(),
                'externalId' => $job->getExternalId(),
                'title' => $job->getTitle(),
                'company' => $job->getCompany(),
                'description' => $job->getDescription(),
                'location' => $job->getLocation(),
                'country' => $job->getCountry(),
                'category' => $job->getCategory(),
                'skills' => $job->getSkills(),
                'salaryMin' => $job->getSalaryMin(),
                'salaryMax' => $job->getSalaryMax(),
                'experienceLevel' => $job->getExperienceLevel(),
                'sourceUrl' => $job->getSourceUrl(),
                'createAt' => $job->getCreateAt()?->format('Y-m-d H:i:s'),
            ];
        }

        return $this->json([
            'count' => count($results),
            'jobs' => $results,
        ]);
    }
}
