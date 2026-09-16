<?php

namespace App\Repository;

use App\Entity\Job;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class JobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Job::class);
    }

    /**
     * Search jobs using optional filters.
     */
    public function searchJobs(
        ?string $keyword = null,
        ?string $category = null,
        ?string $country = null,
        ?string $experience = null,
        ?float $salaryMin = null,
        ?float $salaryMax = null
    ): array {
        $qb = $this->createQueryBuilder('j');

        /*
         * Keyword search
         * Searches title, company and description.
         */
        if ($keyword !== null && trim($keyword) !== '') {
            $qb
                ->andWhere(
                    'LOWER(j.title) LIKE LOWER(:keyword)
                    OR LOWER(j.company) LIKE LOWER(:keyword)
                    OR LOWER(j.description) LIKE LOWER(:keyword)'
                )
                ->setParameter(
                    'keyword',
                    '%' . trim($keyword) . '%'
                );
        }

        /*
         * Category filter.
         */
        if ($category !== null && trim($category) !== '') {
            $qb
                ->andWhere('j.category = :category')
                ->setParameter('category', trim($category));
        }

        /*
         * Country filter.
         */
        if ($country !== null && trim($country) !== '') {
            $qb
                ->andWhere('j.country = :country')
                ->setParameter('country', trim($country));
        }

        /*
         * Experience filter.
         */
        if ($experience !== null && trim($experience) !== '') {
            $qb
                ->andWhere('j.experienceLevel = :experience')
                ->setParameter('experience', trim($experience));
        }

        /*
         * Minimum salary.
         *
         * Job salaryMax must be >= requested minimum.
         */
        if ($salaryMin !== null) {
            $qb
                ->andWhere(
                    'j.salaryMax IS NOT NULL AND j.salaryMax >= :salaryMin'
                )
                ->setParameter('salaryMin', $salaryMin);
        }

        /*
         * Maximum salary.
         *
         * Job salaryMin must be <= requested maximum.
         */
        if ($salaryMax !== null) {
            $qb
                ->andWhere(
                    'j.salaryMin IS NOT NULL AND j.salaryMin <= :salaryMax'
                )
                ->setParameter('salaryMax', $salaryMax);
        }

        /*
         * Newest jobs first.
         */
        $qb->orderBy('j.createAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

public function findSalaryRange(): array
{
    $result = $this->createQueryBuilder('j')
        ->select(
            'MIN(j.salaryMin) AS minSalary',
            'MAX(j.salaryMax) AS maxSalary'
        )
        ->where(
            'j.salaryMin IS NOT NULL OR j.salaryMax IS NOT NULL'
        )
        ->getQuery()
        ->getSingleResult();

    $minSalary = $result['minSalary'] !== null
        ? (float) $result['minSalary']
        : 0;

    $maxSalary = $result['maxSalary'] !== null
        ? (float) $result['maxSalary']
        : 0;

    return [
        'minSalary' => $minSalary,
        'maxSalary' => $maxSalary,
        'step' => 10000,
    ];
}

}
