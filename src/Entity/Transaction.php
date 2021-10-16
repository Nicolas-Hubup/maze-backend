<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TransactionRepository::class)
 */
class Transaction
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Wallet::class, inversedBy="transactions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $wallet;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $transactionId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $direction;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;


    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $senderOutputAddress;

    /**
     * @ORM\Column(type="integer")
     */
    private $lovelaceAmount;

    /**
     * @ORM\Column(type="float")
     */
    private $adaAmount;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isValid;

    /**
     * @ORM\Column(type="boolean")
     */
    private $refundProceeded;

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

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(string $transactionId): self
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function getDirection(): ?string
    {
        return $this->direction;
    }

    public function setDirection(?string $direction): self
    {
        $this->direction = $direction;

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


    public function getSenderOutputAddress(): ?string
    {
        return $this->senderOutputAddress;
    }

    public function setSenderOutputAddress(?string $senderOutputAddress): self
    {
        $this->senderOutputAddress = $senderOutputAddress;

        return $this;
    }

    public function getLovelaceAmount(): ?int
    {
        return $this->lovelaceAmount;
    }

    public function setLovelaceAmount(int $lovelaceAmount): self
    {
        $this->lovelaceAmount = $lovelaceAmount;

        return $this;
    }

    public function getAdaAmount(): ?float
    {
        return $this->adaAmount;
    }

    public function setAdaAmount(float $adaAmount): self
    {
        $this->adaAmount = $adaAmount;

        return $this;
    }

    public function getIsValid(): ?bool
    {
        return $this->isValid;
    }

    public function setIsValid(?bool $isValid): self
    {
        $this->isValid = $isValid;

        return $this;
    }

    public function getRefundProceeded(): ?bool
    {
        return $this->refundProceeded;
    }

    public function setRefundProceeded(bool $refundProceeded): self
    {
        $this->refundProceeded = $refundProceeded;

        return $this;
    }

}
