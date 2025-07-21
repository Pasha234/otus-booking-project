<?php

namespace App\Booking\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use App\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use App\User\Domain\Entity\Invitation;
use Doctrine\Common\Collections\Collection;
use Gedmo\Mapping\Annotation\Timestampable;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use App\Booking\Infrastructure\Repository\GroupRepository;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: '`group`')]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?array $settings = null;

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'group', orphanRemoval: true)]
    private Collection $bookings;

    #[ORM\ManyToOne(inversedBy: 'groups')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    /**
     * @var Collection<int, GroupParticipant>
     */
    #[ORM\OneToMany(targetEntity: GroupParticipant::class, mappedBy: 'group', orphanRemoval: true, cascade: ['persist'])]
    private Collection $groupParticipants;

    /**
     * @var Collection<int, Invitation>
     */
    #[ORM\OneToMany(targetEntity: Invitation::class, mappedBy: 'group', orphanRemoval: true)]
    private Collection $invitations;

    /**
     * @var Collection<int, Resource>
     */
    #[ORM\OneToMany(targetEntity: Resource::class, mappedBy: 'group', orphanRemoval: true)]
    private Collection $resources;

    #[ORM\Column(nullable: true)]
    #[Timestampable(on: 'create')]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    #[Timestampable(on: 'update')]
    private ?\DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->groupParticipants = new ArrayCollection();
        $this->invitations = new ArrayCollection();
        $this->resources = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSettings(): ?array
    {
        return $this->settings;
    }

    public function setSettings(?array $settings): static
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setGroup($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getGroup() === $this) {
                $booking->setGroup(null);
            }
        }

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, GroupParticipant>
     */
    public function getGroupParticipants(): Collection
    {
        return $this->groupParticipants;
    }

    public function addGroupParticipant(GroupParticipant $groupParticipant): static
    {
        if (!$this->groupParticipants->contains($groupParticipant)) {
            $this->groupParticipants->add($groupParticipant);
            $groupParticipant->setGroup($this);
        }

        return $this;
    }

    public function removeGroupParticipant(GroupParticipant $groupParticipant): static
    {
        if ($this->groupParticipants->removeElement($groupParticipant)) {
            // set the owning side to null (unless already changed)
            if ($groupParticipant->getGroup() === $this) {
                $groupParticipant->setGroup(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, GroupParticipant>
     */
    public function getMembers(): Collection
    {
        // The owner is persisted as a GroupParticipant via the createOwnerAsParticipant
        // lifecycle callback, so we can just return the complete list of participants.
        return $this->getGroupParticipants();
    }
    /**
     * @return Collection<int, Invitation>
     */
    public function getInvitations(): Collection
    {
        return $this->invitations;
    }

    public function addInvitation(Invitation $invitation): static
    {
        if (!$this->invitations->contains($invitation)) {
            $this->invitations->add($invitation);
            $invitation->setGroup($this);
        }

        return $this;
    }

    public function removeInvitation(Invitation $invitation): static
    {
        if ($this->invitations->removeElement($invitation)) {
            // set the owning side to null (unless already changed)
            if ($invitation->getGroup() === $this) {
                $invitation->setGroup(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Resource>
     */
    public function getResources(): Collection
    {
        return $this->resources;
    }

    public function addResource(Resource $resource): static
    {
        if (!$this->resources->contains($resource)) {
            $this->resources->add($resource);
            $resource->setGroup($this);
        }

        return $this;
    }

    public function removeResource(Resource $resource): static
    {
        if ($this->resources->removeElement($resource)) {
            // set the owning side to null (unless already changed)
            if ($resource->getGroup() === $this) {
                $resource->setGroup(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    #[ORM\PrePersist]
    public function createOwnerAsParticipant(): void
    {
        if (null === $this->getOwner()) {
            return;
        }

        $isOwnerParticipant = $this->groupParticipants->exists(
            fn($key, GroupParticipant $participant) => $participant->getUser() === $this->getOwner()
        );

        if (!$isOwnerParticipant) {
            $ownerParticipant = new GroupParticipant();
            $ownerParticipant->setUser($this->getOwner());
            $ownerParticipant->setJoinedAt(new \DateTimeImmutable());
            $this->addGroupParticipant($ownerParticipant);
        }
    }

    public function checkUserIsInGroup(User $user): bool
    {
        return $this->getMembers()->exists(
            fn($key, $participant) => $participant->getUser() === $user
        );
    }
}
