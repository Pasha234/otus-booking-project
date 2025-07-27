<?php

namespace App\Booking\Domain\Entity;

use App\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use App\Booking\Infrastructure\Repository\GroupParticipantRepository;

#[ORM\Entity(repositoryClass: GroupParticipantRepository::class)]
class GroupParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?string $id = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $joined_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $banned_at = null;

    #[ORM\ManyToOne(inversedBy: 'groupParticipants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Group $group = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joined_at;
    }

    public function setJoinedAt(?\DateTimeImmutable $joined_at): static
    {
        $this->joined_at = $joined_at;

        return $this;
    }

    public function getBannedAt(): ?\DateTimeImmutable
    {
        return $this->banned_at;
    }

    public function setBannedAt(?\DateTimeImmutable $banned_at): static
    {
        $this->banned_at = $banned_at;

        return $this;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(?Group $group): static
    {
        $this->group = $group;

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

    public function isAdmin(): bool
    {
        return $this->user->getId() == $this->group->getOwner()->getId();
    }
}
