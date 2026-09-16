<?php

namespace App\Command;

use App\Entity\Job;
use App\Service\AdzunaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:jobs:import',
    description: 'Import jobs from multiple industries using Adzuna.'
)]
class ImportJobsCommand extends Command
{
    public function __construct(
        private AdzunaService $adzunaService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $output->writeln('');
        $output->writeln('<info>Starting Adzuna job import...</info>');
        $output->writeln('');

        /*
         * Countries to import.
         *
         * For now we start with the UK because
         * your existing Adzuna integration already
         * uses the "gb" endpoint.
         */
      $countries = [
    // Europe
    'gb' => 'United Kingdom',
    'fr' => 'France',
    'de' => 'Germany',
    'nl' => 'Netherlands',
    'ie' => 'Ireland',
    'es' => 'Spain',
    'it' => 'Italy',
    'pl' => 'Poland',

    // North Africa
    'tn' => 'Tunisia',
    'ma' => 'Morocco',
    'eg' => 'Egypt',

    // North America
    'us' => 'United States',
    'ca' => 'Canada',

    // South America
    'br' => 'Brazil',
    'ar' => 'Argentina',
    'cl' => 'Chile',

    // Asia
    'in' => 'India',
    'sg' => 'Singapore',
    'jp' => 'Japan',
    'my' => 'Malaysia',

    // Middle East
    'ae' => 'United Arab Emirates',
    'sa' => 'Saudi Arabia',
    'qa' => 'Qatar',
];

        /*
         * Multiple job categories and keywords.
         */
        $jobSearches = [

            'IT' => [
                'software developer',
                'web developer',
                'PHP developer',
                'Java developer',
                'Python developer',
                'full stack developer',
            ],

            'Industry' => [
                'factory worker',
                'production worker',
                'manufacturing worker',
                'machine operator',
            ],

            'Restaurant & Hospitality' => [
                'chef',
                'cook',
                'waiter',
                'restaurant worker',
                'hotel receptionist',
            ],

            'Construction' => [
                'construction worker',
                'electrician',
                'plumber',
                'maintenance worker',
            ],

            'Administration' => [
                'administrative assistant',
                'office assistant',
                'data entry',
                'receptionist',
            ],

            'Marketing' => [
                'marketing assistant',
                'digital marketing',
                'social media manager',
                'marketing specialist',
            ],

            'Sales' => [
                'sales assistant',
                'sales representative',
                'sales consultant',
            ],

            'Logistics' => [
                'warehouse worker',
                'warehouse assistant',
                'logistics assistant',
                'delivery driver',
            ],
        ];

        /*
         * Skills that we can detect from title
         * and description.
         */
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

            'Excel',
            'Microsoft Office',
            'Customer Service',
            'Communication',
            'Sales',
            'Marketing',
            'Social Media',
            'Management',
            'Leadership',
            'Cooking',
            'Food Preparation',
            'Warehouse',
            'Forklift',
            'Driving',
            'Maintenance',
            'Electrician',
            'Plumbing',
        ];

        /*
         * IDs already processed during this import.
         */
        $processedIds = [];

        $totalImported = 0;
        $totalSkipped = 0;

        try {

            foreach ($countries as $countryCode => $countryName) {

                $output->writeln(
                    sprintf(
                        '<info>========================================</info>'
                    )
                );

                $output->writeln(
                    sprintf(
                        '<info>Country: %s</info>',
                        $countryName
                    )
                );

                $output->writeln(
                    '<info>========================================</info>'
                );

                foreach ($jobSearches as $category => $keywords) {

                    $output->writeln('');
                    $output->writeln(
                        sprintf(
                            '<comment>Category: %s</comment>',
                            $category
                        )
                    );

                    foreach ($keywords as $keyword) {

                        $output->writeln(
                            sprintf(
                                '  Searching: <comment>%s</comment>',
                                $keyword
                            )
                        );

                       try {
    $data = $this->adzunaService->searchJobs(
        $countryCode,
        $keyword,
        1,
        20
    );
} catch (\Throwable $e) {
    $output->writeln(
        sprintf(
            '    <error>Adzuna request failed for %s: %s</error>',
            $countryName,
            $e->getMessage()
        )
    );

    $output->writeln(
        '    <comment>Skipping this search and continuing...</comment>'
    );

    continue;
}

                        $imported = 0;
                        $skipped = 0;

                        foreach ($data['results'] ?? [] as $jobData) {

                            $externalId = $jobData['id'] ?? null;

                            if (!$externalId) {
                                continue;
                            }

                            $externalId = (string) $externalId;

                            /*
                             * Prevent duplicate during
                             * this import.
                             */
                            if (isset($processedIds[$externalId])) {

                                $skipped++;
                                $totalSkipped++;

                                continue;
                            }

                            $processedIds[$externalId] = true;

                            /*
                             * Check database.
                             */
                            $existingJob = $this->entityManager
                                ->getRepository(Job::class)
                                ->findOneBy([
                                    'externalId' => $externalId
                                ]);

                            if ($existingJob) {

                                $skipped++;
                                $totalSkipped++;

                                continue;
                            }

                            /*
                             * Create Job.
                             */
                            $job = new Job();

                            $job->setExternalId($externalId);

                            $job->setTitle(
                                $jobData['title']
                                ?? 'Untitled job'
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
                             * Country.
                             */
                            $job->setCountry($countryName);

                            /*
                             * Category.
                             */
                            $job->setCategory($category);

                            /*
                             * Salary.
                             */
                            $salaryMin = $jobData['salary_min'] ?? null;
                            $salaryMax = $jobData['salary_max'] ?? null;

                            if (is_numeric($salaryMin)) {
                                $job->setSalaryMin(
                                    (float) $salaryMin
                                );
                            }

                            if (is_numeric($salaryMax)) {
                                $job->setSalaryMax(
                                    (float) $salaryMax
                                );
                            }

                            /*
                             * Combine title + description
                             * for skill and experience detection.
                             */
                            $text = strtolower(
                                ($jobData['title'] ?? '') . ' ' .
                                ($jobData['description'] ?? '')
                            );

                            /*
                             * Detect skills.
                             */
                            $foundSkills = [];

                            foreach ($possibleSkills as $skill) {

                                if (
                                    str_contains(
                                        $text,
                                        strtolower($skill)
                                    )
                                ) {
                                    $foundSkills[] = $skill;
                                }
                            }

                            $job->setSkills(
                                implode(', ', array_unique($foundSkills))
                            );

                            /*
                             * Detect experience level.
                             */
                            $experienceLevel = $this->detectExperienceLevel(
                                $text
                            );

                            $job->setExperienceLevel(
                                $experienceLevel
                            );

                            /*
                             * Adzuna application URL.
                             */
                            $job->setSourceUrl(
                                $jobData['redirect_url'] ?? null
                            );

                            /*
                             * Import date.
                             */
                            $job->setCreateAt(
                                new \DateTimeImmutable()
                            );

                            /*
                             * Persist.
                             */
                            $this->entityManager->persist($job);

                            $imported++;
                            $totalImported++;

                            $output->writeln(
                                sprintf(
                                    '    <info>+ %s</info>',
                                    $job->getTitle()
                                )
                            );
                        }

                        $output->writeln(
                            sprintf(
                                '    New: <info>%d</info> | Skipped: <comment>%d</comment>',
                                $imported,
                                $skipped
                            )
                        );
                    }
                }
            }

            /*
             * Save all jobs.
             */
            $this->entityManager->flush();

            $output->writeln('');
            $output->writeln(
                '<info>========================================</info>'
            );

            $output->writeln(
                '<info>Import completed successfully!</info>'
            );

            $output->writeln(
                sprintf(
                    'Total new jobs: <info>%d</info>',
                    $totalImported
                )
            );

            $output->writeln(
                sprintf(
                    'Total skipped/duplicate jobs: <comment>%d</comment>',
                    $totalSkipped
                )
            );

            $output->writeln(
                '<info>========================================</info>'
            );

            $output->writeln('');

            return Command::SUCCESS;

        } catch (\Throwable $e) {

            $output->writeln('');
            $output->writeln(
                '<error>Job import failed.</error>'
            );

            $output->writeln(
                '<error>' . $e->getMessage() . '</error>'
            );

            $output->writeln('');

            return Command::FAILURE;
        }
    }

    /**
     * Detect a basic experience level from the job text.
     */
    private function detectExperienceLevel(string $text): ?string
    {
        if (
            str_contains($text, 'no experience') ||
            str_contains($text, 'no previous experience') ||
            str_contains($text, 'entry level') ||
            str_contains($text, 'graduate')
        ) {
            return 'Entry Level';
        }

        if (
            str_contains($text, 'junior') ||
            str_contains($text, 'júnior')
        ) {
            return 'Junior';
        }

        if (
            str_contains($text, 'mid-level') ||
            str_contains($text, 'mid level') ||
            str_contains($text, 'intermediate')
        ) {
            return 'Mid Level';
        }

        if (
            str_contains($text, 'senior') ||
            str_contains($text, 'lead developer') ||
            str_contains($text, 'team lead')
        ) {
            return 'Senior';
        }

        if (
            str_contains($text, 'manager') ||
            str_contains($text, 'director')
        ) {
            return 'Manager';
        }

        return null;
    }
}
