<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'product')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column]
    private ?int $calories = null;

    #[ORM\Column]
    private ?int $sugarGrams = null;

    #[ORM\Column]
    private ?int $caffeineMg = null;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCalories(): ?int
    {
        return $this->calories;
    }

    public function setCalories(int $calories): static
    {
        $this->calories = $calories;

        return $this;
    }

    public function getSugarGrams(): ?int
    {
        return $this->sugarGrams;
    }

    public function setSugarGrams(int $sugarGrams): static
    {
        $this->sugarGrams = $sugarGrams;

        return $this;
    }

    public function getCaffeineMg(): ?int
    {
        return $this->caffeineMg;
    }

    public function setCaffeineMg(int $caffeineMg): static
    {
        $this->caffeineMg = $caffeineMg;

        return $this;
    }
}
