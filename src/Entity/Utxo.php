<?php

namespace App\Entity;

use App\Repository\UtxoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UtxoRepository::class)
 */
class Utxo
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $txHash;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $txIx;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTxHash(): ?string
    {
        return $this->txHash;
    }

    public function setTxHash(string $txHash): self
    {
        $this->txHash = $txHash;

        return $this;
    }

    public function getTxIx(): ?int
    {
        return $this->txIx;
    }

    public function setTxIx(?int $txIx): self
    {
        $this->txIx = $txIx;

        return $this;
    }

}
