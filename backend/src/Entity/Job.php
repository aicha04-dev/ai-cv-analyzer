<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\JobRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: JobRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['job:read']],
    denormalizationContext: ['groups' => ['job:write']]
)]
class Job
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['job:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    #[Groups(['job:read', 'job:write'])]
    private ?string $externalId = null;

    #[ORM\Column(length: 255)]
    #[Groups(['job:read', 'job:write'])]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Groups(['job:read', 'job:write'])]
    private ?string $company = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['job:read', 'job:write'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['job:read', 'job:write'])]
    private ?string $location = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['job:read', 'job:write'])]
    private ?string $country = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['job:read', 'job:write'])]
    private ?string $category = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['job:read', 'job:write'])]
    private ?string $skills = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['job:read', 'job:write'])]
    private ?float $salaryMin = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Groups(['job:read', 'job:write'])]
    private ?float $salaryMax = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['job:read', 'job:write'])]
    private ?string $experienceLevel = null;

    #[ORM\Column(length: 2048, nullable: true)]
    #[Groups(['job:read', 'job:write'])]
    private ?string $sourceUrl = null;

    #[ORM\Column]
    #[Groups(['job:read'])]
    private ?\DateTimeImmutable $createAt = null;

    /**
     * @var Collection<int, JobMatch>
     */
    #[ORM\OneToMany(
        targetEntity: JobMatch::class,
        mappedBy: 'job',
        orphanRemoval: true
    )]
    private Collection $jobMatches;

    public function __construct()
    {
        $this->jobMatches = new ArrayCollection();
        $this->createAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): static
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(string $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getSkills(): ?string
    {
        return $this->skills;
    }

    public function setSkills(?string $skills): static
    {
        $this->skills = $skills;

        return $this;
    }

    public function getSalaryMin(): ?float
    {
        return $this->salaryMin;
    }

    public function setSalaryMin(?float $salaryMin): static
    {
        $this->salaryMin = $salaryMin;

        return $this;
    }

    public function getSalaryMax(): ?float
    {
        return $this->salaryMax;
    }

    public function setSalaryMax(?float $salaryMax): static
    {
        $this->salaryMax = $salaryMax;

        return $this;
    }

    public function getExperienceLevel(): ?string
    {
        return $this->experienceLevel;
    }

    public function setExperienceLevel(?string $experienceLevel): static
    {
        $this->experienceLevel = $experienceLevel;

        return $this;
    }

    public function getSourceUrl(): ?string
    {
        return $this->sourceUrl;
    }

    public function setSourceUrl(?string $sourceUrl): static
    {
        $this->sourceUrl = $sourceUrl;

        return $this;
    }

    public function getCreateAt(): ?\DateTimeImmutable
    {
        return $this->createAt;
    }

    public function setCreateAt(\DateTimeImmutable $createAt): static
    {
        $this->createAt = $createAt;

        return $this;
    }

    /**
     * @return Collection<int, JobMatch>
     */
    public function getJobMatches(): Collection
    {
        return $this->jobMatches;
    }

    public function addJobMatch(JobMatch $jobMatch): static
    {
        if (!$this->jobMatches->contains($jobMatch)) {
            $this->jobMatches->add($jobMatch);
            $jobMatch->setJob($this);
        }

        return $this;
    }

    public function removeJobMatch(JobMatch $jobMatch): static
    {
        if ($this->jobMatches->removeElement($jobMatch)) {
            if ($jobMatch->getJob() === $this) {
                $jobMatch->setJob(null);
            }
        }

        return $this;
    }
}