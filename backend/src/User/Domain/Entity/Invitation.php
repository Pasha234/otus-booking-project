<?php

namespace App\User\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Booking\Domain\Entity\Group;
use App\User\Domain\Enum\InvitationStatus;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use App\User\Infrastructure\Repository\InvitationRepository;

#[ORM\Entity(repositoryClass: InvitationRepository::class)]
class Invitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    private ?string $invited_email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $token = null;

    #[ORM\Column(enumType: InvitationStatus::class)]
    private ?InvitationStatus $status = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expires_at = null;

    #[ORM\ManyToOne(inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $invitee = null;

    #[ORM\ManyToOne(inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Group $group = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $accepted_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $declined_at = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getInvitedEmail(): ?string
    {
        return $this->invited_email;
    }

    public function setInvitedEmail(string $invited_email): static
    {
        $this->invited_email = $invited_email;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getStatus(): ?InvitationStatus
    {
        return $this->status;
    }

    public function setStatus(InvitationStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expires_at;
    }

    public function setExpiresAt(?\DateTimeImmutable $expires_at): static
    {
        $this->expires_at = $expires_at;

        return $this;
    }

    public function getInvitee(): ?User
    {
        return $this->invitee;
    }

    public function setInvitee(?User $invitee): static
    {
        $this->invitee = $invitee;

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

    public function getAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->accepted_at;
    }

    public function setAcceptedAt(?\DateTimeImmutable $accepted_at): static
    {
        $this->accepted_at = $accepted_at;

        return $this;
    }

    public function getDeclinedAt(): ?\DateTimeImmutable
    {
        return $this->declined_at;
    }

    public function setDeclinedAt(?\DateTimeImmutable $declined_at): static
    {
        $this->declined_at = $declined_at;

        return $this;
    }
}
