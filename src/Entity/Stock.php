<?php

namespace App\Entity;

use App\Repository\StockRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockRepository::class)]
class Stock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stocks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]  // 👈 ADD onDelete: 'CASCADE' here!
    private ?Product $product = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    private ?int $reorderLevel = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $lastUpdated = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

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

    public function getReorderLevel(): ?int
    {
        return $this->reorderLevel;
    }

    public function setReorderLevel(int $reorderLevel): static
    {
        $this->reorderLevel = $reorderLevel;

        return $this;
    }

    public function getLastUpdated(): ?\DateTime
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(?\DateTime $lastUpdated): static
    {
        $this->lastUpdated = $lastUpdated;

        return $this;
    }

    public function getStatus(): string
    {
        if ($this->quantity > $this->reorderLevel) {
            return '✅ In Stock ('.$this->quantity.' > '.$this->reorderLevel.', okay pa!)';
        }

        if ($this->quantity == 0) {
            return '❌ Out of Stock';
        }

        return '⚠️ Low Stock (Reorder now!)';
    }
}