<?php

namespace App\Controller;

use App\Entity\CV;
use App\Entity\Job;
use App\Entity\JobMatch;
use App\Repository\JobMatchRepository;
use App\Service\PdfTextExtractor;
use App\Service\GeminiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class JobMatchController extends AbstractController
{
    // =========================================================
    // MATCH CV WITH JOB
    // =========================================================

    #[Route('/api/job-match', name: 'job_match', methods: ['POST'])]
    public function match(
        EntityManagerInterface $entityManager,
        PdfTextExtractor $pdfTextExtractor,
        GeminiService $geminiService
    ): JsonResponse {
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!$data) {
            return $this->json([
                'error' => 'Invalid JSON'
            ], 400);
        }

        $cvId = $data['cvId'] ?? null;
        $jobId = $data['jobId'] ?? null;

        if (!$cvId || !$jobId) {
            return $this->json([
                'error' => 'cvId and jobId are required'
            ], 400);
        }

        $cv = $entityManager
            ->getRepository(CV::class)
            ->find($cvId);

        $job = $entityManager
            ->getRepository(Job::class)
            ->find($jobId);

        if (!$cv) {
            return $this->json([
                'error' => 'CV not found'
            ], 404);
        }

        if (!$job) {
            return $this->json([
                'error' => 'Job not found'
            ], 404);
        }

        try {
            // =====================================================
            // GET CV PDF PATH
            // =====================================================

            $filePath = $this->getParameter('kernel.project_dir')
                . '/public/uploads/'
                . $cv->getFileName();

            // =====================================================
            // EXTRACT CV TEXT
            // =====================================================

            $cvText = $pdfTextExtractor->extract($filePath);

            // =====================================================
            // BUILD JOB TEXT
            // =====================================================

            $jobText = sprintf(
                "Title: %s\nCompany: %s\nLocation: %s\nDescription: %s\nSkills: %s",
                $job->getTitle(),
                $job->getCompany(),
                $job->getLocation() ?? '',
                $job->getDescription(),
                $job->getSkills() ?? ''
            );

            // =====================================================
            // ASK GEMINI TO COMPARE CV AND JOB
            // =====================================================

            $result = $geminiService->matchCvToJob(
                $cvText,
                $jobText
            );

            // =====================================================
            // CREATE JOB MATCH
            // =====================================================

            $jobMatch = new JobMatch();

            $jobMatch->setCv($cv);
            $jobMatch->setJob($job);
            $jobMatch->setScore($result['score'] ?? 0);

            $jobMatch->setAnalysis(
                json_encode(
                    $result,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                )
            );

            $entityManager->persist($jobMatch);
            $entityManager->flush();

            // =====================================================
            // RESPONSE
            // =====================================================

            return $this->json([
                'message' => 'CV matched with job successfully',

                'match' => [
                    'id' => $jobMatch->getId(),
                    'cvId' => $cv->getId(),
                    'jobId' => $job->getId(),
                    'score' => $jobMatch->getScore(),

                    'analysis' => $result
                ]
            ], 201);

        } catch (\Throwable $e) {

            return $this->json([
                'error' => 'Job matching failed',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    // =========================================================
    // GET CV MATCH HISTORY
    // =========================================================

    #[Route(
        '/api/cv/{cvId}/matches',
        name: 'cv_match_history',
        methods: ['GET']
    )]
    public function history(
        int $cvId,
        JobMatchRepository $jobMatchRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        // =====================================================
        // FIND CV
        // =====================================================

        $cv = $entityManager
            ->getRepository(CV::class)
            ->find($cvId);

        if (!$cv) {
            return $this->json([
                'error' => 'CV not found'
            ], 404);
        }

        // =====================================================
        // GET MATCHES
        // =====================================================

        $matches = $jobMatchRepository->findBy(
            ['cv' => $cv],
            ['createdAt' => 'DESC']
        );

        $history = [];

        // =====================================================
        // BUILD HISTORY RESPONSE
        // =====================================================

        foreach ($matches as $match) {

            $job = $match->getJob();

            $analysis = null;

            if ($match->getAnalysis()) {
                $analysis = json_decode(
                    $match->getAnalysis(),
                    true
                );
            }

            $history[] = [
                'id' => $match->getId(),

                'score' => $match->getScore(),

                'createdAt' => $match->getCreatedAt()
                    ?->format('Y-m-d H:i:s'),

                'job' => [
                    'id' => $job->getId(),
                    'title' => $job->getTitle(),
                    'company' => $job->getCompany(),
                    'location' => $job->getLocation(),
                    'category' => $job->getCategory(),
                    'salaryMin' => $job->getSalaryMin(),
                    'salaryMax' => $job->getSalaryMax(),
                    'sourceUrl' => $job->getSourceUrl(),
                ],

                'analysis' => $analysis,
            ];
        }

        // =====================================================
        // RESPONSE
        // =====================================================

        return $this->json([
            'cvId' => $cv->getId(),
            'total' => count($history),
            'matches' => $history,
        ]);
    }
}