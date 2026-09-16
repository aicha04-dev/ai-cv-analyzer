<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ApiResource]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastName = null;

    /**
     * @var Collection<int, CV>
     */
    #[ORM\OneToMany(targetEntity: CV::class, mappedBy: 'user')]
    private Collection $cVs;

    public function __construct()
    {
        $this->cVs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Symfony uses this as the user's identifier.
     */
    public function getUserIdentifier(): string
    {
        return $this->email ?? '';
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Symfony roles.
     */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    /**
     * Required by UserInterface.
     */
    public function eraseCredentials(): void
    {
        // Nothing to erase.
    }

    /**
     * @return Collection<int, CV>
     */
    public function getCVs(): Collection
    {
        return $this->cVs;
    }

    public function addCV(CV $cV): static
    {
        if (!$this->cVs->contains($cV)) {
            $this->cVs->add($cV);
            $cV->setUser($this);
        }

        return $this;
    }

    public function removeCV(CV $cV): static
    {
        if ($this->cVs->removeElement($cV)) {
            if ($cV->getUser() === $this) {
                $cV->setUser(null);
            }
        }

        return $this;
    }
}