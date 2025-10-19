<?php

namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateJoined = null;

    #[ORM\Column]
    private ?int $loyaltyPoints = null;

    #[ORM\Column]
    private ?float $totalPurchases = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $lastPurchaseDate = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    public function getId(): ?int
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getDateJoined(): ?\DateTime
    {
        return $this->dateJoined;
    }

    public function setDateJoined(\DateTime $dateJoined): static
    {
        $this->dateJoined = $dateJoined;

        return $this;
    }

    public function getLoyaltyPoints(): ?int
    {
        return $this->loyaltyPoints;
    }

    public function setLoyaltyPoints(int $loyaltyPoints): static
    {
        $this->loyaltyPoints = $loyaltyPoints;

        return $this;
    }

    public function getTotalPurchases(): ?float
    {
        return $this->totalPurchases;
    }

    public function setTotalPurchases(float $totalPurchases): static
    {
        $this->totalPurchases = $totalPurchases;

        return $this;
    }

    public function getLastPurchaseDate(): ?\DateTime
    {
        return $this->lastPurchaseDate;
    }

    public function setLastPurchaseDate(?\DateTime $lastPurchaseDate): static
    {
        $this->lastPurchaseDate = $lastPurchaseDate;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
