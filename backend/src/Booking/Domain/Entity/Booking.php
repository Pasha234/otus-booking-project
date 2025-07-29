<?php

namespace App\Booking\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Symfony\Component\Uid\Uuid;
use App\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Gedmo\Mapping\Annotation\Timestampable;
use Symfony\Bridge\Doctrine\Types\UuidType;
use App\Booking\Domain\Entity\BookedResource;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use App\Booking\Infrastructure\Repository\BookingRepository;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $end_at = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $start_at = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'bookings')]
    private Collection $users;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Group $group = null;

    /**
     * @var Collection<int, BookedResource>
     */
    #[ORM\OneToMany(targetEntity: BookedResource::class, mappedBy: 'booking', orphanRemoval: true, cascade: ['persist'])]
    private Collection $bookedResources;

    #[ORM\Column(nullable: true)]
    #[Timestampable(on: 'create')]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(nullable: true)]
    #[Timestampable(on: 'update')]
    private ?\DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->bookedResources = new ArrayCollection();
    }

    public function getId(): ?Uuid
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->end_at;
    }

    public function setEndAt(\DateTimeImmutable $end_at): static
    {
        $this->end_at = $end_at;

        return $this;
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->start_at;
    }

    public function setStartAt(\DateTimeImmutable $start_at): static
    {
        $this->start_at = $start_at;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        $this->users->removeElement($user);

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

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

    /**
     * @return Collection<int, BookedResource>
     */
    public function getBookedResources(): Collection
    {
        return $this->bookedResources;
    }

    public function addBookedResource(BookedResource $bookedResource): static
    {
        if (!$this->bookedResources->contains($bookedResource)) {
            $this->bookedResources->add($bookedResource);
            $bookedResource->setBooking($this);
        }

        return $this;
    }

    public function removeBookedResource(BookedResource $bookedResource): static
    {
        if ($this->bookedResources->removeElement($bookedResource)) {
            // set the owning side to null (unless already changed)
            if ($bookedResource->getBooking() === $this) {
                $bookedResource->setBooking(null);
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
}
