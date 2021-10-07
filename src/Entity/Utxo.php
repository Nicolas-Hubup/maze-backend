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
     * @ORM\ManyToOne(targetEntity=Wallet::class, inversedBy="utxos")
     * @ORM\JoinColumn(nullable=false)
     */
    private $wallet;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $adaBalance;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWallet(): ?Wallet
    {
        return $this->wallet;
    }

    public function setWallet(?Wallet $wallet): self
    {
        $this->wallet = $wallet;

        return $this;
    }

    public function getAdaBalance(): ?int
    {
        return $this->adaBalance;
    }

    public function setAdaBalance(?int $adaBalance): self
    {
        $this->adaBalance = $adaBalance;

        return $this;
    }
}
