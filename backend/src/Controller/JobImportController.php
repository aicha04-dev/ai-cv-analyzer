<?php

namespace App\Controller;

use App\Entity\Job;
use App\Service\AdzunaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class JobImportController
{
#[Route(
    '/api/jobs/import',
    name: 'api_jobs_import',
    methods: ['GET'],
    priority: 10
)]    public function importJobs(
        AdzunaService $adzunaService,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            // Get jobs from Adzuna
            $data = $adzunaService->searchJobs(
                'gb',
                'software developer',
                1
            );

            $imported = 0;
            $skipped = 0;

            foreach ($data['results'] ?? [] as $jobData) {
                // Adzuna job ID
                $externalId = $jobData['id'] ?? null;

                if (!$externalId) {
                    continue;
                }

                // Check if this Adzuna job already exists
                $existingJob = $entityManager
                    ->getRepository(Job::class)
                    ->findOneBy([
                        'externalId' => (string) $externalId
                    ]);

                if ($existingJob) {
                    $skipped++;
                    continue;
                }

                // Create new Job
                $job = new Job();

                $job->setExternalId((string) $externalId);

                $job->setTitle(
                    $jobData['title'] ?? 'Untitled job'
                );

                $job->setCompany(
                    $jobData['company']['display_name']
                    ?? 'Unknown company'
                );

                $job->setDescription(
                    $jobData['description']
                    ?? 'No description available.'
                );

                $job->setLocation(
                    $jobData['location']['display_name']
                    ?? 'Unknown location'
                );

                /*
                 * Adzuna does not provide a simple "skills" field
                 * in every job result.
                 *
                 * For now, we extract common technical skills
                 * from the title and description.
                 */
                $text = strtolower(
                    ($jobData['title'] ?? '') . ' ' .
                    ($jobData['description'] ?? '')
                );

                $possibleSkills = [
                    'PHP',
                    'Symfony',
                    'Laravel',
                    'Java',
                    'Spring Boot',
                    'Python',
                    'Django',
                    'JavaScript',
                    'TypeScript',
                    'React',
                    'Angular',
                    'Vue.js',
                    'Node.js',
                    'Express.js',
                    'C',
                    'C++',
                    'C#',
                    '.NET',
                    'SQL',
                    'MySQL',
                    'PostgreSQL',
                    'MongoDB',
                    'Docker',
                    'Kubernetes',
                    'Git',
                    'REST API',
                    'API',
                    'AWS',
                    'Azure',
                    'GCP',
                    'Flutter',
                    'HTML',
                    'CSS',
                ];

                $foundSkills = [];

                foreach ($possibleSkills as $skill) {
                    if (str_contains($text, strtolower($skill))) {
                        $foundSkills[] = $skill;
                    }
                }

                $job->setSkills(
                    implode(', ', $foundSkills)
                );

                // Official Adzuna application URL
                $job->setSourceUrl(
                    $jobData['redirect_url'] ?? null
                );

                $job->setCreateAt(
                    new \DateTimeImmutable()
                );

                $entityManager->persist($job);

                $imported++;
            }

            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Jobs imported successfully.',
                'imported' => $imported,
                'skipped' => $skipped,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Job import failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}