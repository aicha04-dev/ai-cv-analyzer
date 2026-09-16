<?php

namespace App\Entity;

use App\Repository\JobMatchRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobMatchRepository::class)]
class JobMatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $score = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $analysis = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'jobMatches')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CV $cv = null;

    #[ORM\ManyToOne(inversedBy: 'jobMatches')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Job $job = null;


    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }


    public function getId(): ?int
    {
        return $this->id;
    }


    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;

        return $this;
    }


    public function getAnalysis(): ?string
    {
        return $this->analysis;
    }

    public function setAnalysis(?string $analysis): static
    {
        $this->analysis = $analysis;

        return $this;
    }


    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(
        \DateTimeImmutable $createdAt
    ): static {
        $this->createdAt = $createdAt;

        return $this;
    }


    public function getCv(): ?CV
    {
        return $this->cv;
    }

    public function setCv(?CV $cv): static
    {
        $this->cv = $cv;

        return $this;
    }


    public function getJob(): ?Job
    {
        return $this->job;
    }

    public function setJob(?Job $job): static
    {
        $this->job = $job;

        return $this;
    }
}