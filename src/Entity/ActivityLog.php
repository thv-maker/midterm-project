<?php
// src/Entity/ActivityLog.php

namespace App\Entity;

use App\Repository\ActivityLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityLogRepository::class)]
#[ORM\Table(name: 'activity_log')]
#[ORM\Index(columns: ['date_time'], name: 'idx_date_time')]
#[ORM\Index(columns: ['action'], name: 'idx_action')]
#[ORM\Index(columns: ['user_id'], name: 'idx_user_id')]
class ActivityLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // User ID - Required (nullable for deleted users)
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $userId = null;

    // Username - Required
    #[ORM\Column(length: 180)]
    private ?string $username = null;

    // Role - Required
    #[ORM\Column(length: 50)]
    private ?string $role = null;

    // Action - Required (LOGIN, LOGOUT, CREATE, UPDATE, DELETE)
    #[ORM\Column(length: 50)]
    private ?string $action = null;

    // Target Data - Required (e.g., "User: staff05 (ID: 9)")
    #[ORM\Column(type: Types::TEXT)]
    private ?string $targetData = null;

    // Date & Time - Required
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateTime = null;

    // Optional: Keep relationship for easy querying
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    // Optional: Additional context
    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent = null;

    public function __construct()
    {
        $this->dateTime = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getTargetData(): ?string
    {
        return $this->targetData;
    }

    public function setTargetData(string $targetData): static
    {
        $this->targetData = $targetData;
        return $this;
    }

    public function getDateTime(): ?\DateTimeInterface
    {
        return $this->dateTime;
    }

    public function setDateTime(\DateTimeInterface $dateTime): static
    {
        $this->dateTime = $dateTime;
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

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    // Helper method for badge display
    public function getActionBadge(): string
    {
        return match($this->action) {
            'LOGIN' => 'success',
            'LOGOUT' => 'secondary',
            'CREATE' => 'primary',
            'UPDATE' => 'warning',
            'DELETE' => 'danger',
            default => 'dark'
        };
    }

    // Helper to get formatted role name
    public function getFormattedRole(): string
    {
        return match($this->role) {
            'ROLE_ADMIN' => 'Admin',
            'ROLE_STAFF' => 'Staff',
            'ROLE_USER' => 'User',
            default => $this->role
        };
    }
}