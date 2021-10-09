<?php

namespace App\Entity;

use JMS\Serializer\Annotation as Serializer;
use App\Repository\TemporaryWalletAddressRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TemporaryWalletAddressRepository::class)
 */
class TemporaryWalletAddress
{
    /**
     * @Serializer\Groups({"temporary_wallet"})
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Serializer\Groups({"temporary_wallet"})
     * @ORM\Column(type="string", length=255)
     */
    private $walletId;

    /**
     * @Serializer\Groups({"temporary_wallet"})
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWalletId(): ?string
    {
        return $this->walletId;
    }

    public function setWalletId(string $walletId): self
    {
        $this->walletId = $walletId;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
