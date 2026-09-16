<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\CVRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CVRepository::class)]
#[ApiResource]
class CV
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $fileName = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $uploadAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $analysis = null;

    #[ORM\ManyToOne(inversedBy: 'cVs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, JobMatch>
     */
    #[ORM\OneToMany(
    targetEntity: JobMatch::class,
    mappedBy: 'cv'
)]
private Collection $jobMatches;

    public function __construct()
    {
        $this->jobMatches = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getUploadAt(): ?\DateTimeInterface
    {
        return $this->uploadAt;
    }

    public function setUploadAt(\DateTimeInterface $uploadAt): static
    {
        $this->uploadAt = $uploadAt;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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
            $jobMatch->setCv($this);
        }

        return $this;
    }

    public function removeJobMatch(JobMatch $jobMatch): static
    {
        if ($this->jobMatches->removeElement($jobMatch)) {
            if ($jobMatch->getCv() === $this) {
                $jobMatch->setCv(null);
            }
        }

        return $this;
    }
}