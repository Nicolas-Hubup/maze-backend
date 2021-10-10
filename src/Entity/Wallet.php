<?php

namespace App\Entity;

use App\Repository\WalletRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass=WalletRepository::class)
 */
class Wallet
{
    /**
     * @Serializer\Groups({"wallet_address", "wallet"})
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $passphrase;

    /**
     * @ORM\Column(type="text")
     */
    private $mnemonicSentence;

    /**
     * @Serializer\Groups({"wallet_address", "wallet"})
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @Serializer\Groups({"wallet_address", "wallet"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastUpdatePassPhrase;

    /**
     * @Serializer\Groups({"wallet_address", "wallet"})
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $lovelaceBalance;

    /**
     * @Serializer\Groups({"wallet_address", "wallet"})
     * @ORM\Column(type="float", nullable=true)
     */
    private $adaBalance;

    /**
     * @Serializer\Groups({"wallet_address", "wallet"})
     * @ORM\Column(type="string", length=500)
     */
    private $walletId;

    /**
     * @ORM\OneToMany(targetEntity=WalletAddress::class, mappedBy="wallet")
     */
    private $walletAddresses;

    /**
     * @Serializer\Groups({"wallet_address", "wallet"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastUpdated;

    /**
     * @ORM\OneToMany(targetEntity=Utxo::class, mappedBy="wallet")
     */
    private $utxos;

    /**
     * @Serializer\Groups({"wallet_address", "wallet"})
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastUpdatedUtxos;

    /**
     * @ORM\OneToMany(targetEntity=Transaction::class, mappedBy="wallet")
     */
    private $transactions;

    public function __construct()
    {
        $this->walletAddresses = new ArrayCollection();
        $this->utxos = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPassphrase(): ?string
    {
        return $this->passphrase;
    }

    public function setPassphrase(string $passphrase): self
    {
        $this->passphrase = $passphrase;

        return $this;
    }

    public function getMnemonicSentence(): ?string
    {
        return $this->mnemonicSentence;
    }

    public function setMnemonicSentence(string $mnemonicSentence): self
    {
        $this->mnemonicSentence = $mnemonicSentence;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLastUpdatePassPhrase(): ?\DateTimeInterface
    {
        return $this->lastUpdatePassPhrase;
    }

    public function setLastUpdatePassPhrase(?\DateTimeInterface $lastUpdatePassPhrase): self
    {
        $this->lastUpdatePassPhrase = $lastUpdatePassPhrase;

        return $this;
    }

    public function getLovelaceBalance(): ?string
    {
        return $this->lovelaceBalance;
    }

    public function setLovelaceBalance(?string $lovelaceBalance): self
    {
        $this->lovelaceBalance = $lovelaceBalance;

        return $this;
    }

    public function getAdaBalance(): ?int
    {
        return $this->adaBalance;
    }

    public function setAdaBalance(?float $adaBalance): self
    {
        $this->adaBalance = $adaBalance;

        return $this;
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

    /**
     * @return Collection|WalletAddress[]
     */
    public function getWalletAddresses(): Collection
    {
        return $this->walletAddresses;
    }

    public function addWalletAddress(WalletAddress $walletAddress): self
    {
        if (!$this->walletAddresses->contains($walletAddress)) {
            $this->walletAddresses[] = $walletAddress;
            $walletAddress->setWallet($this);
        }

        return $this;
    }

    public function removeWalletAddress(WalletAddress $walletAddress): self
    {
        if ($this->walletAddresses->removeElement($walletAddress)) {
            // set the owning side to null (unless already changed)
            if ($walletAddress->getWallet() === $this) {
                $walletAddress->setWallet(null);
            }
        }

        return $this;
    }

    public function getLastUpdated(): ?\DateTimeInterface
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(?\DateTimeInterface $lastUpdated): self
    {
        $this->lastUpdated = $lastUpdated;

        return $this;
    }

    /**
     * @return Collection|Utxo[]
     */
    public function getUtxos(): Collection
    {
        return $this->utxos;
    }

    public function addUtxo(Utxo $utxo): self
    {
        if (!$this->utxos->contains($utxo)) {
            $this->utxos[] = $utxo;
            $utxo->setWallet($this);
        }

        return $this;
    }

    public function removeUtxo(Utxo $utxo): self
    {
        if ($this->utxos->removeElement($utxo)) {
            // set the owning side to null (unless already changed)
            if ($utxo->getWallet() === $this) {
                $utxo->setWallet(null);
            }
        }

        return $this;
    }

    public function getLastUpdatedUtxos(): ?\DateTimeInterface
    {
        return $this->lastUpdatedUtxos;
    }

    public function setLastUpdatedUtxos(?\DateTimeInterface $lastUpdatedUtxos): self
    {
        $this->lastUpdatedUtxos = $lastUpdatedUtxos;

        return $this;
    }

    /**
     * @return Collection|Transaction[]
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
            $transaction->setWallet($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getWallet() === $this) {
                $transaction->setWallet(null);
            }
        }

        return $this;
    }
}
