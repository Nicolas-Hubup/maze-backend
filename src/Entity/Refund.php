<?php

namespace App\Entity;

use App\Repository\RefundRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RefundRepository::class)
 */
class Refund
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $refundedAt;

    /**
     * @ORM\Column(type="string", length=500)
     */
    private $outputAddress;

    /**
     * @ORM\Column(type="float")
     */
    private $adaAmountPostFees;

    /**
     * @ORM\Column(type="float")
     */
    private $adaAmount;

    /**
     * @ORM\OneToOne(targetEntity=Transaction::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $transaction;

    /**
     * @ORM\Column(type="text")
     */
    private $curlResponse;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRefundedAt(): ?\DateTimeInterface
    {
        return $this->refundedAt;
    }

    public function setRefundedAt(\DateTimeInterface $refundedAt): self
    {
        $this->refundedAt = $refundedAt;

        return $this;
    }

    public function getOutputAddress(): ?string
    {
        return $this->outputAddress;
    }

    public function setOutputAddress(string $outputAddress): self
    {
        $this->outputAddress = $outputAddress;

        return $this;
    }

    public function getAdaAmountPostFees(): ?float
    {
        return $this->adaAmountPostFees;
    }

    public function setAdaAmountPostFees(float $adaAmountPostFees): self
    {
        $this->adaAmountPostFees = $adaAmountPostFees;

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

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(Transaction $transaction): self
    {
        $this->transaction = $transaction;

        return $this;
    }

    public function getCurlResponse(): ?string
    {
        return $this->curlResponse;
    }

    public function setCurlResponse(string $curlResponse): self
    {
        $this->curlResponse = $curlResponse;

        return $this;
    }
}
