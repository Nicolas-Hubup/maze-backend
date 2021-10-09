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

    public function getRandomNumber(): ?int
    {
        return $this->randomNumber;
    }

    public function setRandomNumber(int $randomNumber): self
    {
        $this->randomNumber = $randomNumber;

        return $this;
    }


}
