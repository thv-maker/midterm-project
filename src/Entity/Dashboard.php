<?php

namespace App\Entity;

use App\Repository\DashboardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DashboardRepository::class)]
class Dashboard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $products = null;

    #[ORM\Column(length: 255)]
    private ?string $stocks = null;

    #[ORM\Column(length: 255)]
    private ?string $orders = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProducts(): ?string
    {
        return $this->products;
    }

    public function setProducts(string $products): static
    {
        $this->products = $products;

        return $this;
    }

    public function getStocks(): ?string
    {
        return $this->stocks;
    }

    public function setStocks(string $stocks): static
    {
        $this->stocks = $stocks;

        return $this;
    }

    public function getOrders(): ?string
    {
        return $this->orders;
    }

    public function setOrders(string $orders): static
    {
        $this->orders = $orders;

        return $this;
    }
}
