<?php

namespace App\Entity;

use App\Repository\FigureRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FigureRepository::class)
 */
class Figure
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $randomNumber;

    /**
     * @ORM\Column(type="boolean")
     */
    private $available;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $reservedAt;

    public function getRandomNumber(): ?int
    {
        return $this->randomNumber;
    }

    public function setRandomNumber(int $randomNumber): self
    {
        $this->randomNumber = $randomNumber;

        return $this;
    }

    public function getAvailable(): ?bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): self
    {
        $this->available = $available;

        return $this;
    }

    public function getReservedAt(): ?\DateTimeInterface
    {
        return $this->reservedAt;
    }

    public function setReservedAt(?\DateTimeInterface $reservedAt): self
    {
        $this->reservedAt = $reservedAt;

        return $this;
    }


}
