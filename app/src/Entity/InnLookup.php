<?php

namespace App\Entity;

use App\Repository\InnLookupRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InnLookupRepository::class)]
#[ORM\Table(name: 'inn_lookups')]
class InnLookup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 12, unique: true)]
    private string $inn;

    #[ORM\Column(length: 512)]
    private string $name;

    #[ORM\Column]
    private bool $isActive;

    #[ORM\Column(length: 20)]
    private string $okved;

    #[ORM\Column(length: 512)]
    private string $okvedName;

    #[ORM\Column(type: Types::JSON)]
    private array $rawResponse = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getInn(): string
    {
        return $this->inn;
    }

    public function setInn(string $inn): static
    {
        $this->inn = $inn;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getOkved(): string
    {
        return $this->okved;
    }

    public function setOkved(string $okved): static
    {
        $this->okved = $okved;

        return $this;
    }

    public function getOkvedName(): string
    {
        return $this->okvedName;
    }

    public function setOkvedName(string $okvedName): static
    {
        $this->okvedName = $okvedName;

        return $this;
    }

    public function getRawResponse(): array
    {
        return $this->rawResponse;
    }

    public function setRawResponse(array $rawResponse): static
    {
        $this->rawResponse = $rawResponse;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isStale(int $ttlSeconds = 3600): bool
    {
        return $this->updatedAt < new \DateTimeImmutable("-{$ttlSeconds} seconds");
    }
}
