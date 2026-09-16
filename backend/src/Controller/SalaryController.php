<?php

namespace App\Controller;

use App\Repository\JobRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class SalaryController extends AbstractController
{
    #[Route(
        '/api/jobs/salary-options',
        name: 'api_jobs_salary_options',
        methods: ['GET'],
        priority: 100
    )]
    public function salaryOptions(
        JobRepository $jobRepository
    ): JsonResponse {
        $salaryRange = $jobRepository->findSalaryRange();

        return $this->json([
            'minSalary' => $salaryRange['minSalary'],
            'maxSalary' => $salaryRange['maxSalary'],
            'step' => $salaryRange['step'],
        ]);
    }
}