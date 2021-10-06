<?php

namespace App\Entity;

use App\Repository\WalletAddressRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass=WalletAddressRepository::class)
 */
class WalletAddress
{
    /**
     * @Serializer\Groups({"wallet_address"})
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Serializer\Groups({"wallet_address"})
     * @ORM\ManyToOne(targetEntity=Wallet::class, inversedBy="walletAddresses")
     * @ORM\JoinColumn(nullable=false)
     */
    private $wallet;

    /**
     * @Serializer\Groups({"wallet_address"})
     * @ORM\Column(type="string", length=500)
     */
    private $walletAddressId;

    /**
     * @Serializer\Groups({"wallet_address"})
     * @ORM\Column(type="string", length=255)
     */
    private $state;

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

    public function getWalletAddressId(): ?string
    {
        return $this->walletAddressId;
    }

    public function setWalletAddressId(string $walletAddressId): self
    {
        $this->walletAddressId = $walletAddressId;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }
}
