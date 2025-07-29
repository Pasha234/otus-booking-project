<?php

namespace App\Booking\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Entity\Resource;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use App\Booking\Infrastructure\Repository\BookedResourceRepository;

#[ORM\Entity(repositoryClass: BookedResourceRepository::class)]
class BookedResource
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?string $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookedResources')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Booking $booking = null;

    #[ORM\ManyToOne(inversedBy: 'bookedResources')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Resource $resource = null;

    #[ORM\Column]
    private ?int $quantity = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): static
    {
        $this->booking = $booking;

        return $this;
    }

    public function getResource(): ?Resource
    {
        return $this->resource;
    }

    public function setResource(?Resource $resource): static
    {
        $this->resource = $resource;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }
}
